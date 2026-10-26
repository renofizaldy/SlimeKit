<?php

namespace App\Helpers;

use Exception;
use DateTime;
use DateTimeInterface;
use Illuminate\Database\Capsule\Manager as DB;
use App\Lib\Cloudinary;
use App\Lib\Mailer;
use App\Lib\Cronhooks;
use App\Lib\Valkey;
use App\Lib\WappflyClient;
use App\Lib\WapitoClient;
use Throwable;

use App\Models\Log;
use App\Models\Picture;
use App\Models\Cronhooks as CronhooksModel;
use App\Models\Article;
use App\Models\ArticleStats;
use App\Models\ArticleCategory;
use App\Models\SeoMeta;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;

class General
{
  private $valkey;
  private ?WapitoClient $wapito;
  private ?WappflyClient $wappfly;

  public function __construct(
    Valkey $valkey,
    ?WapitoClient $wapito = null,
    ?WappflyClient $wappfly = null
  )
  {
    $this->valkey  = $valkey;
    $this->wapito  = $wapito;
    $this->wappfly = $wappfly;
  }

  public function addLog(array $user, string $table_name, int $id_record, string $action, array $changes = []): void
  {
    $userObj = $user['user'] ?? null;
    $userId  = is_object($userObj) ? ($userObj->id ?? null) : (is_array($userObj) ? ($userObj['id'] ?? null) : null);
    Log::create([
      'id_user'    => $userId,
      'table_name' => $table_name,
      'id_record'  => $id_record,
      'action'     => $action,
      'changes'    => $changes,
      'ip_address' => $user['ip_address'] ?? null,
      'user_agent' => $user['user_agent'] ?? null,
    ]);
  }

  /**
   * Helper pengiriman WhatsApp dengan automatic failover (Wapito -> Wappfly) + log jika gagal
   */
  public function sendWhatsApp(
    string $phone,
    string $message,
    ?array $user = null,
    ?string $tableName = null,
    ?int $recordId = null
  ): array {
    $providers = [];
    if ($this->wapito !== null) {
      $providers['wapito'] = $this->wapito;
    }
    if ($this->wappfly !== null) {
      $providers['wappfly'] = $this->wappfly;
    }

    $errors    = [];
    $attempted = 0;

    foreach ($providers as $providerName => $providerClient) {
      try {
        $result = $providerClient->sendTextMessage($phone, $message);

        // Jika skipped karena token atau nomor tujuan belum diatur
        if (($result['status'] ?? '') === 'skipped') {
          continue;
        }

        $attempted++;

        if (($result['status'] ?? '') === 'success') {
          return [
            'status'   => 'success',
            'provider' => $providerName,
            'response' => $result,
          ];
        }

        $errors[$providerName] = $result;
      } catch (Throwable $e) {
        $attempted++;
        $errors[$providerName] = [
          'status'  => 'error',
          'message' => $e->getMessage(),
        ];
      }
    }

    if ($tableName && $recordId && $attempted > 0) {
      $this->addLog($user ?? [], $tableName, $recordId, 'INSERT', [
        'event'   => 'WA_NOTIF_FAILED',
        'status'  => 'FAILED',
        'reason'  => 'All configured WhatsApp providers failed',
        'errors'  => $errors,
      ]);
    }

    return [
      'status'  => 'failed',
      'reason'  => $attempted > 0 ? 'All WhatsApp providers failed' : 'No WhatsApp provider active or configured',
      'errors'  => $errors,
    ];
  }

  private function normalizeLogValue($value)
  {
    if ($value instanceof DateTimeInterface) {
      return $value->format('Y-m-d H:i:s');
    }

    if ($value instanceof Model) {
      return $this->normalizeLogRecord($value);
    }

    if (is_array($value)) {
      return array_map(fn ($item) => $this->normalizeLogValue($item), $value);
    }

    return $value;
  }

  public function normalizeLogRecord($record, array $except = []): array
  {
    $except = array_merge(['password'], $except);

    if ($record instanceof Model) {
      $record = $record->attributesToArray();
    }
    elseif (is_object($record) && method_exists($record, 'toArray')) {
      $record = $record->toArray();
    }
    elseif (is_object($record)) {
      $record = get_object_vars($record);
    }

    if (!is_array($record)) {
      return [];
    }

    $data = [];
    foreach ($record as $key => $value) {
      if (in_array($key, $except, true)) {
        continue;
      }
      $data[$key] = $this->normalizeLogValue($value);
    }

    return $data;
  }

  public function logRecordChanges($record, array $except = []): array
  {
    return [
      'record' => $this->normalizeLogRecord($record, $except),
    ];
  }

  public function logUpdateChanges($before, array $after, array $except = []): array
  {
    $before = $this->normalizeLogRecord($before, $except);
    $after  = $this->normalizeLogRecord($after, $except);

    $changes = [
      'before' => [],
      'after'  => [],
    ];

    foreach ($after as $key => $newValue) {
      $oldValue = $before[$key] ?? null;
      if ($oldValue === $newValue) {
        continue;
      }

      $changes['before'][$key] = $oldValue;
      $changes['after'][$key]  = $newValue;
    }

    return $changes;
  }

  public function formatCloudinaryUrl(?string $url, string $transformation): ?string
  {
    if (empty($url) || !is_string($url) || !str_contains($url, 'res.cloudinary.com') || !str_contains($url, '/image/upload/')) {
      return $url;
    }

    $transformation = trim($transformation, '/');
    if ($transformation === '') {
      return $url;
    }

    // Pattern 1: URL with version segment (/image/upload/[existing_transforms/]v\d+/...)
    if (preg_match('#^(https?://res\.cloudinary\.com/[^/]+/image/upload/)(?:[^/]+/)*(v\d+/.*)$#i', $url, $matches)) {
      return $matches[1] . $transformation . '/' . $matches[2];
    }

    // Pattern 2: URL without version segment, optionally having existing transformations
    if (preg_match('#^(https?://res\.cloudinary\.com/[^/]+/image/upload/)(?:[a-z]{1,4}_[^/]+/(?:[a-z]{1,4}_[^/]+/)*)?(.*)$#i', $url, $matches)) {
      return $matches[1] . $transformation . '/' . $matches[2];
    }

    return $url;
  }

  public function uploadImage(Cloudinary $cloudinary, $file): ?array
  {
    if (empty($file)) return null;

    $upload = $cloudinary->upload($file);
    if (!$upload) {
      throw new Exception('Failed to upload photo', 409);
    }

    $original  = $upload['secure_url'];
    $large     = $this->formatCloudinaryUrl($original, 'c_fill,w_1200,h_675,g_auto,q_auto,f_auto');
    $thumbnail = $this->formatCloudinaryUrl($original, 'c_thumb,w_600');

    return [
      'public_id' => $upload['public_id'],
      'original'  => $original,
      'large'     => $large,
      'thumbnail' => $thumbnail,
    ];
  }

  public function savePicture(array $uploadData, ?string $caption = null): Picture
  {
    return Picture::create([
      'id_cloud'  => $uploadData['public_id'],
      'original'  => $uploadData['original'],
      'thumbnail' => $uploadData['thumbnail'],
      'caption'   => !empty($caption) ? $caption : null,
    ]);
  }

  public function pictureUpload(Cloudinary $cloudinary, $file, $caption = null): ?array
  {
    $uploaded = $this->uploadImage($cloudinary, $file);
    if (!$uploaded) return null;

    $picture = $this->savePicture($uploaded, $caption);

    return [
      'id'        => $picture->id,
      'url'       => $uploaded['original'],
      'large'     => $uploaded['large'] ?? null,
      'thumbnail' => $uploaded['thumbnail'] ?? null,
    ];
  }

  public function reCron(Cronhooks $cronhooks): bool
  {
    try {
      $maxSlots = 1;

      //* EMPTY CRONJOB
        $crons = CronhooksModel::where('type', 'article')->get();
        foreach ($crons as $cron) {
          //? Hapus dari External Cronhooks Service
          if (!empty($cron->id_cronhooks)) {
            try {
              $cronhooks->deleteSchedule($cron->id_cronhooks);
            } catch (\Throwable $e) {
              error_log("recomputeCron: failed to delete cron " . $cron->id_cronhooks . ": " . $e->getMessage());
            }
          }
          //? Hapus dari database lokal (Eloquent delete)
          $cron->delete();
        }
      //* EMPTY CRONJOB

      //* RESCHEDULE ARTICLE
        //? Ambil artikel yang: publish > (NOW + 1 menit) AND status = inactive
        $articles = Article::where('publish', '>', Carbon::now()->addMinute())
          ->where('status', 'inactive')
          ->orderBy('publish', 'ASC')
          ->limit($maxSlots)
          ->get();

        foreach ($articles as $article) {
          $cronId = null;

          //? Create Schedule di External Service
          $cronData = $cronhooks->createSchedule(
            $article->publish,
            [
              'title'    => 'Publish Artikel #' . $article->site .':'. $article->id,
              'timezone' => 'Asia/Jakarta',
              'method'   => 'POST',
              'headers'  => [
                'Authorization' => 'Bearer ' . ($_ENV['JOB_STATIC_TOKEN'] ?? getenv('JOB_STATIC_TOKEN') ?? ''),
              ],
              'payload'  => [
                'site' => $article->site,
                'slug' => $article->slug
              ]
            ]
          );
          $cronId = $cronData['id'] ?? null;

          //? Simpan ke tb_cronhooks via Eloquent
          try {
            CronhooksModel::create([
              'id_parent'    => $article->id,
              'type'         => 'article',
              'id_cronhooks' => $cronId
            ]);
          } catch (\Throwable $e) {
            error_log("recomputeCron: failed to save cron for article #" . $article->id);
            continue;
          }
        }
      //* RESCHEDULE ARTICLE

      return true;
    }
    catch (\Throwable $e) {
      error_log("recomputeCronjobs fatal: " . $e->getMessage());
      return true;
    }
  }

  public function validateByRules(array $input, array $rules)
  {
    foreach ($rules as $field => $ruleString) {
      $rulesArray = explode('|', $ruleString);

      foreach ($rulesArray as $rule) {
        switch ($rule) {
          case 'required':
            if (!array_key_exists($field, $input)) {
              throw new \Exception("Field '{$field}' is required.", 400);
            }
            break;

          case 'not_empty':
            if (
              !isset($input[$field]) ||
              (is_string($input[$field]) && trim($input[$field]) === '') ||
              (is_array($input[$field]) && count($input[$field]) === 0)
            ) {
              throw new \Exception("Field '{$field}' cannot be empty.", 400);
            }
            break;

          case 'string':
            if (isset($input[$field]) && !is_string($input[$field])) {
              throw new \Exception("Field '{$field}' must be a string.", 400);
            }
            break;

          case 'integer':
            if (isset($input[$field]) && !filter_var($input[$field], FILTER_VALIDATE_INT)) {
              throw new \Exception("Field '{$field}' must be an integer.", 400);
            }
            break;

          case 'date':
            if (isset($input[$field]) && strtotime($input[$field]) === false) {
              throw new \Exception("Field '{$field}' must be a valid date.", 400);
            }
            break;

          case 'datetime':
            if (isset($input[$field]) && strtotime($input[$field]) === false) {
              throw new \Exception("Field '{$field}' must be a valid datetime.", 400);
            }
            break;

          case 'boolean':
            if (isset($input[$field]) && !is_bool($input[$field])) {
              throw new \Exception("Field '{$field}' must be a boolean.", 400);
            }
            break;

          default:
            throw new \Exception("Unknown validation rule '{$rule}' for field '{$field}'", 500);
        }
      }
    }
  }

  public function normalizeHttpStatus($code)
  {
    return (is_int($code) && $code >= 100 && $code <= 599) ? $code : 500;
  }

  public function slugify(string $string, int $id, int $length = 55): string
  {
    // Ganti karakter non huruf/angka jadi strip
    $slug = preg_replace('/[^A-Za-z0-9]+/', '-', $string);

    // Hilangin strip berlebihan
    $slug = trim($slug, '-');

    // Ubah ke huruf kecil
    $slug = strtolower($slug);

    // Siapkan tambahan ID
    $idPart = '-' . (string)$id;

    // Hitung sisa panjang untuk slug tanpa id
    $maxSlugLength = $length - strlen($idPart);

    // Potong slug kalau perlu
    if (strlen($slug) > $maxSlugLength) {
        $slug = substr($slug, 0, $maxSlugLength);
        $slug = rtrim($slug, '-'); // Jangan berakhir dengan strip sebelum tambah id
    }

    // Gabungkan slug dengan ID
    return $slug . $idPart;
  }

  public function getGeoIp(string $ipAddress): ?array
  {
    if ($ipAddress === '127.0.0.1' || $ipAddress === '::1' || $ipAddress === 'unknown') {
      return null;
    }

    $cacheKey = "geoip:{$ipAddress}";
    
    //! 1. Cek di Redis dulu
    $cachedData = $this->valkey->get($cacheKey);
    if ($cachedData) {
      return json_decode($cachedData, true);
    }

    //! 2. Jika tidak ada di Redis, tembak ke ip-api.com
    try {
      $url = "http://ip-api.com/json/{$ipAddress}?fields=status,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,as";
      
      $context  = stream_context_create(['http' => ['timeout' => 3]]);  //? Timeout: 3 detik
      $response = @file_get_contents($url, false, $context);
      
      if ($response) {
        $geoData = json_decode($response, true);
        
        if (isset($geoData['status']) && $geoData['status'] === 'success') {
          //! 3. Simpan ke Redis selama 7 hari (604800 detik) agar hemat API call
          $this->valkey->set($cacheKey, json_encode($geoData), 604800);
          return $geoData;
        }
      }
    } catch (Exception $e) {}

    return null;
  }

  public function getClientIp($request): ?string
  {
    $headersToCheck = [
      'X-Proxy-Client-IP',    //! Header custom dari proxy internal (Next.js / Netlify)
      'CF-Connecting-IP',     //! Header Cloudflare (IP asli dari pengunjung)
      'True-Client-IP',       //! Header Cloudflare Enterprise / Akamai
      'X-Real-IP',            //! Header standar Nginx / Load Balancer
      'X-Forwarded-For',      //! Header standar industri (bisa berisi rantai IP proxy)
      'WL-Proxy-Client-IP',   //! Header spesifik WebLogic Server
      'HTTP_CLIENT_IP',       //! Alternatif header lama dari proxy tertentu
      'HTTP_X_FORWARDED_FOR'  //! Alternatif format X-Forwarded-For di beberapa server PHP
    ];

    foreach ($headersToCheck as $header) {
      $val = $request?->getHeaderLine($header);
      if (!empty($val)) {
        $ips = explode(',', $val);
        $ip  = trim($ips[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
          return $ip;
        }
      }
    }

    $remoteAddr = $request?->getServerParams()['REMOTE_ADDR'] ?? null;
    if ($remoteAddr && filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
      return $remoteAddr;
    }

    return null;
  }

  public function getUserContext($request): array
  {
    $pxCountry = $request->getHeaderLine('X-Proxy-Client-Country');
    $cfCountry = $request->getHeaderLine('CF-IPCountry');

    return [
      'user'       => $request->getAttribute('user'),
      'user_agent' => $request->getHeaderLine('User-Agent') ?: 'unknown',
      'ip_address' => $this->getClientIp($request) ?: 'unknown',
      'country'    => $pxCountry ?: ($cfCountry ?: 'unknown'),
    ];
  }

  public function filterSearchQuery($query, $options = []): string
  {
    // Default options
    $defaults = [
      'lowercase'     => true,
      'strip_tags'    => true,
      'allowed_chars' => '/[^a-zA-Z0-9\s]/',
      'max_length'    => 100
    ];

    // Gabungkan defaults dengan override dari $options
    $settings = array_merge($defaults, $options);

    // Step 1: Trim whitespace
    $filtered = trim($query ?? '');

    // Step 2: Remove HTML tags (if enabled)
    if ($settings['strip_tags']) {
      $filtered = strip_tags($filtered);
    }

    // Step 3: Remove disallowed characters
    if ($settings['allowed_chars']) {
      $filtered = preg_replace($settings['allowed_chars'], '', $filtered);
    }

    // Step 4: Convert to lowercase (if enabled)
    if ($settings['lowercase']) {
      $filtered = mb_strtolower($filtered);
    }

    // Step 5: Limit max length
    if ($settings['max_length']) {
      $filtered = mb_substr($filtered, 0, $settings['max_length']);
    }

    return $filtered !== '' ? $filtered : null;
  }

  public function maskName($fullName)
  {
    $parts = explode(' ', $fullName);
    $masked = array_map(function ($name) {
      if (strlen($name) <= 2) return $name[0] . '*';
      return $name[0] . str_repeat('*', strlen($name) - 2) . $name[strlen($name) - 1];
    }, $parts);
    return implode(' ', $masked);
  }

  public function maskEmail($email)
  {
    list($user, $domain) = explode('@', $email);

    // Mask user
    $maskedUser = substr($user, 0, 1) . str_repeat('*', max(strlen($user) - 1, 1));

    // Mask domain
    $domainParts = explode('.', $domain);
    $domainName = $domainParts[0];
    $domainExt = isset($domainParts[1]) ? '.' . $domainParts[1] : '';

    $maskedDomain = substr($domainName, 0, 1) . str_repeat('*', max(strlen($domainName) - 1, 1));

    return $maskedUser . '@' . $maskedDomain . $domainExt;
  }

  public function maskPhone($phone)
  {
    $len = strlen($phone);
    if ($len <= 4) return str_repeat('*', $len);
    return substr($phone, 0, 2) . str_repeat('*', $len - 5) . substr($phone, -3);
  }

  //* Create DateTime array from 'start_date' to 'end_date' (daily).
  public function generateDateRange($start, $end)
  {
    $dates = [];
    $current = new DateTime($start);
    $last = new DateTime($end);
    while ($current < $last) {
      $dates[] = $current->format('Y-m-d');
      $current->modify('+1 day');
    }
    return $dates;
  }

  public function clean_tavily_context(array $tavilyResults, int $limitPerArticle = 8000): string
  {
    $cleanContext = "";

    // Cek apakah ada hasil
    if (empty($tavilyResults)) {
      return "";
    }

    foreach ($tavilyResults as $res) {
      $url = $res['url'] ?? 'No URL';

      //! 1. Prioritas Data: Ambil Raw jika ada & cukup panjang, jika tidak ambil snippet
      $raw     = $res['raw_content'] ?? '';
      $snippet = $res['content'] ?? '';
      $contentToProcess = (strlen($raw) > 200) ? $raw : $snippet; // Valid jika panjang > 200 karakter

      //* --- TAHAP SANITASI (PEMBERSIHAN) ---

      //! A. Hapus Markdown Images & Links yang embed gambar
      // Menghapus format: ![alt](url) atau [!img](url)
      $contentToProcess = preg_replace('/!\[.*?\]\(.*?\)/s', '', $contentToProcess);

      //! B. Hapus Base64
      // Menghapus format: data:image/png;base64,....
      $contentToProcess = preg_replace('/data:image\/[^;]+;base64,[^\s"\')]+/', '', $contentToProcess);

      //! C. Hapus HTML Tags
      $contentToProcess = strip_tags($contentToProcess);

      //! D. Hapus Naked URLs
      // Menghapus "https://..." yang berdiri sendiri
      $contentToProcess = preg_replace('/\bhttps?:\/\/\S+/', '', $contentToProcess);

      // E. Hapus Kata-kata Sampah Navigasi (UI Garbage)
      $garbagePhrases = [
        'Posting terkait',
        'Lihat komentar',
        'Reply',
        'Menu',
        'Skip to content',
        'Advertisements',
        'BACA JUGA',
        'Lihat Lebih Lanjut',
        'Copyright',
        'All rights reserved'
      ];
      $contentToProcess = str_replace($garbagePhrases, '', $contentToProcess);

      //! F. Normalisasi Spasi (Ubah multi-newline jadi 2 enter saja)
      $contentToProcess = preg_replace("/\n\s*\n/", "\n\n", $contentToProcess);
      $contentToProcess = trim($contentToProcess);

      //* --- TAHAP LIMITASI ---

      // Potong karakter agar tidak boros token
      $finalContent = substr($contentToProcess, 0, $limitPerArticle);

      // Susun Output
      if (!empty($finalContent)) {
        $cleanContext .= "--- SOURCE: " . $url . " ---\n";
        $cleanContext .= $finalContent . "\n\n";
      }
    }

    return $cleanContext;
  }

  public function normalize_datetime(string $date): string
  {
    if (str_ends_with($date, 'Z')) {
      $dateObj = new \DateTime($date);
      $dateObj->setTimezone(new \DateTimeZone('Asia/Jakarta'));
      return $dateObj->format('Y-m-d H:i:s');
    } else {
      $dateObj = new \DateTime($date);
      return $dateObj->format('Y-m-d H:i:s');
    }
  }

  public function generateUuidV4(): string
  {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }

  public function getTopArticles($site = 'all', $limit = 50)
  {
    $query = ArticleStats::select([
        'slug',
        DB::raw('COUNT(id) as visits')
      ])
      ->where('country_code', 'ID')
      ->when($site !== 'all', function ($q) use ($site) {
        $q->where('site', $site);
      })
      ->groupBy('slug')
      ->orderBy('visits', 'DESC')
      ->limit($limit)
      ->get();

    $slugs = $query->pluck('slug')->toArray();

    if (empty($slugs)) {
      return [];
    }

    //* 1. Ambil id, slug, dan title dari Article
    $articles = Article::whereIn('slug', $slugs)
      ->when($site !== 'all', function ($q) use ($site) {
        $q->where('site', $site);
      })
      ->get(['id', 'slug', 'title']);

    $articleIds = $articles->pluck('id')->toArray();

    //* 2. Ambil keyword dari SeoMeta menggunakan id_parent
    $seoMetas = [];
    if (!empty($articleIds)) {
      $seoMetas = SeoMeta::whereIn('id_parent', $articleIds)
        ->pluck('seo_keyphrase', 'id_parent')
        ->toArray();
    }

    //* 3. Gabungkan data berdasarkan slug
    $articleDict = [];
    foreach ($articles as $article) {
      $articleDict[$article->slug] = [
        'title'   => $article->title,
        'keyword' => $seoMetas[$article->id] ?? '-' // Fallback jika tidak ada keyword
      ];
    }

    //* 4. Susun array final berurutan berdasarkan ranking trafik tertinggi
    $topArticles = [];
    foreach ($query as $row) {
      if (isset($articleDict[$row->slug])) {
        $topArticles[] = [
          'title'   => $articleDict[$row->slug]['title'],
          'keyword' => $articleDict[$row->slug]['keyword']
        ];
      }
    }

    return $topArticles;
  }

  /**
   * Ambil daftar kategori artikel berdasarkan site
   * Digunakan untuk referensi AI pada mode Auto + Free
   */
  public function getCategoriesBySite($site)
  {
    $query = ArticleCategory::where('status', 'active')
      ->when($site !== 'all', function ($q) use ($site) {
        $q->where('site', $site);
      })
      ->get(['title', 'description']);

    $categories = [];
    foreach ($query as $row) {
      $categories[] = [
        'title'       => $row->title,
        'description' => $row->description ?? '-',
      ];
    }

    return $categories;
  }

  /**
   * Ambil daftar artikel terbaru berdasarkan site untuk negative context (anti-duplikasi AI)
   *
   * @param string $site
   * @param int    $limit
   * @return array<int, array{title: string, keyword: string}>
   */
  public function getRecentArticlesBySite(string $site = 'all', int $limit = 75): array
  {
    $articles = Article::where('status', 'active')
      ->when($site !== 'all', function ($q) use ($site) {
        $q->where('site', $site);
      })
      ->orderBy('created_at', 'DESC')
      ->limit($limit)
      ->get(['id', 'title']);

    if ($articles->isEmpty()) {
      return [];
    }

    $articleIds = $articles->pluck('id')->toArray();
    $seoMetas = [];
    if (!empty($articleIds)) {
      $seoMetas = SeoMeta::whereIn('id_parent', $articleIds)
        ->where('type', 'article')
        ->pluck('seo_keyphrase', 'id_parent')
        ->toArray();
    }

    $recentArticles = [];
    foreach ($articles as $article) {
      $recentArticles[] = [
        'title'   => trim($article->title ?? ''),
        'keyword' => trim($seoMetas[$article->id] ?? '-'),
      ];
    }

    return $recentArticles;
  }

  /**
   * Normalisasi parameter paginasi (page, pageSize, offset)
   *
   * @param mixed $page
   * @param mixed $pageSize
   * @param int   $defaultPageSize
   * @param int   $maxPageSize
   * @return array{page: int, pageSize: int, offset: int}
   */
  public function normalizePagination(
    mixed $page = 1,
    mixed $pageSize = 10,
    int $defaultPageSize = 10,
    int $maxPageSize = 100
  ): array {
    $page     = max(1, (int) ($page ?: 1));
    $pageSize = (int) ($pageSize ?: $defaultPageSize);
    $pageSize = max(1, min($maxPageSize, $pageSize));
    $offset   = ($page - 1) * $pageSize;

    return [
      'page'     => $page,
      'pageSize' => $pageSize,
      'offset'   => $offset,
    ];
  }

  /**
   * Eksekusi paginasi pada Query Builder Eloquent
   *
   * @param mixed         $query      Instance Query Builder / Eloquent Model
   * @param mixed         $page       Halaman saat ini
   * @param mixed         $pageSize   Jumlah data per halaman
   * @param callable|null $transform  Callback transformasi baris record opsional
   * @return array{items: array, pagination: array{page: int, pageSize: int, total: int, totalPages: int}}
   */
  public function paginateQuery(
    $query,
    mixed $page = 1,
    mixed $pageSize = 10,
    ?callable $transform = null
  ): array {
    $pagination = $this->normalizePagination($page, $pageSize);
    $page       = $pagination['page'];
    $pageSize   = $pagination['pageSize'];
    $offset     = $pagination['offset'];
    $total      = (int) (clone $query)->count();
    $totalPages = $total > 0 ? (int) ceil($total / $pageSize) : 1;
    $results    = $query->skip($offset)->take($pageSize)->get();
    $items      = [];

    if ($results->isNotEmpty()) {
      if ($transform !== null) {
        foreach ($results as $row) {
          $items[] = $transform($row);
        }
      } else {
        $items = $results->toArray();
      }
    }

    return [
      'items'      => $items,
      'pagination' => [
        'page'       => $page,
        'pageSize'   => $pageSize,
        'total'      => $total,
        'totalPages' => $totalPages,
      ],
    ];
  }

  /**
   * Eksekusi paginasi pada array data di memori
   *
   * @param array $items
   * @param mixed $page
   * @param mixed $pageSize
   * @return array{items: array, pagination: array{page: int, pageSize: int, total: int, totalPages: int}}
   */
  public function paginateArray(
    array $items,
    mixed $page = 1,
    mixed $pageSize = 10
  ): array {
    $pagination = $this->normalizePagination($page, $pageSize);
    $page       = $pagination['page'];
    $pageSize   = $pagination['pageSize'];
    $offset     = $pagination['offset'];

    $total      = count($items);
    $totalPages = $total > 0 ? (int) ceil($total / $pageSize) : 1;
    $sliced     = array_slice($items, $offset, $pageSize);

    return [
      'items'      => array_values($sliced),
      'pagination' => [
        'page'       => $page,
        'pageSize'   => $pageSize,
        'total'      => $total,
        'totalPages' => $totalPages,
      ],
    ];
  }

  /**
   * Aturan validasi standar untuk parameter paginasi (page, pageSize)
   *
   * @param array $rules Aturan validasi spesifik modul
   * @return array
   */
  public function paginationRules(array $rules = []): array
  {
    return array_merge($rules, [
      'page'     => 'numeric|min:1',
      'pageSize' => 'numeric|min:1',
    ]);
  }

  /**
   * Mengambil daftar site yang diperbolehkan dari environment ALLOWED_SITES
   *
   * @return array<string>
   */
  public static function getAllowedSites(): array
  {
    $allowed = $_ENV['ALLOWED_SITES'] ?? getenv('ALLOWED_SITES') ?? '';
    if (empty($allowed)) {
      return [];
    }

    $sites = array_map('trim', explode(',', (string)$allowed));
    return array_values(array_filter($sites, fn ($site) => $site !== ''));
  }

  /**
   * Memvalidasi parameter site dari karakter berbahaya dan mencocokkan ke whitelist
   *
   * @param string|null $site
   * @return bool
   */
  public static function isValidSite(?string $site): bool
  {
    if ($site === null || trim($site) === '') {
      return false;
    }

    // Karakter aman: hanya alphanumeric, underscore, hyphen, dot
    // Mencegah colon (:), wildcard (*), spasi, dsb. yang dapat merusak pola cache key
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $site)) {
      return false;
    }

    $allowedSites = self::getAllowedSites();
    if (!empty($allowedSites)) {
      return in_array($site, $allowedSites, true);
    }

    return true;
  }
}
