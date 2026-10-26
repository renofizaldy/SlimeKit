<?php

namespace App\Services;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Valkey;
use App\Lib\Cronhooks;
use App\Lib\NextRevalidator;
use App\Lib\CloudflareR2;
use App\Lib\GoogleCloudStorage;

use App\Models\Article;
use App\Models\ArticleStats;

class JobService
{
  public function __construct(
    private General $helper,
    private Valkey $valkey,
    private Cronhooks $cronhooks,
    private NextRevalidator $nextRevalidator,
    private ?CloudflareR2 $r2 = null,
    private ?GoogleCloudStorage $gcs = null,
    private array $servicesConfig = []
  ) {}

  private function checkArticleExist(array $input)
  {
    $check = Article::where([
      'slug' => $input['slug'],
      'site' => $input['site']
    ])->first();
    if (!$check) {
      throw new Exception('Not Found', 404);
    }
    return $check;
  }

  private function invalidateArticleCache(string $site): void
  {
    $cacheKey = 'article';
    $this->valkey->deleteByPrefix(sprintf("{$cacheKey}:list:site=%s:", $site));
    $this->valkey->deleteByPrefix(sprintf("{$cacheKey}:detail:site=%s:", $site));
    $this->valkey->deleteByPrefix(sprintf("{$cacheKey}:similar:site=%s:", $site));
    $this->valkey->deleteByPrefix(sprintf("{$cacheKey}:admin"));
  }

  public function articlePublish(array $input, array $user)
  {
    //? CHECK ARTICLE
    $check = $this->checkArticleExist($input);

    DB::beginTransaction();
    try {
      //? UPDATE STATUS
        Article::where('id', $check->id)
          ->update([
            'status' => 'active',
          ]);
      //? UPDATE STATUS

      //? LOG Record
        $this->helper->addLog($user, 'tb_article', $check->id, 'UPDATE', $this->helper->logUpdateChanges($check, [
          'status' => 'active',
        ]));
      //? LOG Record

      DB::commit();

      //? DELETE CACHE
        $site = $check->site ?? ($input['site'] ?? '');
        if (!empty($site)) {
          $this->invalidateArticleCache($site);
        }
      //? DELETE CACHE

      $this->nextRevalidator->revalidateArticle($check->site, $check->slug);

      //? CHANGE CRONJOB
        $this->helper->reCron($this->cronhooks);
      //? CHANGE CRONJOB

      return true;
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function articleSyncStats()
  {
    if (!$this->valkey->isConnected()) {
      throw new Exception("Redis is not connected.", 500);
    }

    $queueKeys      = $this->valkey->getKeysByPrefix('queue:article:visit:*');
    $totalProcessed = 0;
    $chunkSize      = 1000;
    $processedSites = [];

    foreach ($queueKeys as $queueKey) {
      $batchData = [];
      $keyParts  = explode(':', $queueKey);
      $site      = end($keyParts);
      if (!in_array($site, $processedSites)) {
        $processedSites[] = $site;
      }

      //* Kuras isi antrean menggunakan RPOP
      while ($item = $this->valkey->rpop($queueKey)) {
        $data = json_decode($item, true);
        if (!isset($data['slug'])) continue;

        $ipAddress = $data['ip_address'] ?? null;
        $geoIp     = $this->helper->getGeoIp($ipAddress);

        $batchData[] = [
          'site'         => $site,
          'slug'         => $data['slug'],
          'ip_address'   => $ipAddress,
          'user_agent'   => $data['user_agent'] ?? null,
          'country'      => $geoIp ? $geoIp['country'] : null,
          'country_code' => $geoIp ? $geoIp['countryCode'] : ($data['country'] ?? null),
          'region_code'  => $geoIp ? $geoIp['region'] : null,
          'region'       => $geoIp ? $geoIp['regionName'] : null,
          'city'         => $geoIp ? $geoIp['city'] : null,
          'zip'          => $geoIp ? (int) $geoIp['zip'] : null,
          'latitude'     => $geoIp ? (float) $geoIp['lat'] : null,
          'longitude'    => $geoIp ? (float) $geoIp['lon'] : null,
          'timezone'     => $geoIp ? $geoIp['timezone'] : null,
          'isp'          => $geoIp ? $geoIp['isp'] : null,
          'asn'          => $geoIp ? $geoIp['as'] : null,
          'created_at'   => $data['visited_at'],
          'updated_at'   => $data['visited_at'],
        ];

        //* Eksekusi Batch Insert jika sudah mencapai 1000 data
        if (count($batchData) >= $chunkSize) {
          ArticleStats::insert($batchData);
          $totalProcessed += count($batchData);
          $batchData = []; 
        }
      }

      //* Eksekusi sisa data (jika jumlahnya tidak bulat kelipatan 1000)
      if (!empty($batchData)) {
        ArticleStats::insert($batchData);
        $totalProcessed += count($batchData);
      }
    }

    return [
      'processed_sites' => $processedSites,
      'total_inserted'  => $totalProcessed
    ];
  }

  public function articleTrackStats(array $input, array $user)
  {
    $slug = $input['slug'] ?? null;
    $site = $input['site'] ?? null;
    if (!$slug || !$site) {
      throw new Exception('Invalid input.', 400);
    }

    //! 1. Menangkap IP Asli dari Cloudflare
    $ipAddress = $user['ip_address'];
    if (strpos($ipAddress, ',') !== false) {
      $ipList    = explode(',', $ipAddress);
      $ipAddress = trim($ipList[0]);
    }

    $timestamp = date('Y-m-d H:i:s');
    $visitData = [
      'slug'       => $slug,
      'ip_address' => $ipAddress,
      'user_agent' => $user['user_agent'],
      'country'    => $user['country'],
      'visited_at' => $timestamp
    ];

    if ($this->valkey->isConnected()) {
      $this->valkey->lpush("queue:article:visit:{$site}", json_encode($visitData));
    }

    return true;
  }

  /**
   * Eksekusi pencadangan database PostgreSQL dan upload ke Storage Provider.
   *
   * @param array $input Input parameter (opsional override 'provider')
   * @param array $user User context dari request
   * @return array Ringkasan hasil backup
   * @throws Exception
   */
  public function databaseBackup(array $input, array $user): array
  {
    $startTime    = microtime(true);
    $backupConfig = $this->servicesConfig['backup_provider'] ?? [];
    $provider     = strtolower($input['provider'] ?? ($backupConfig['provider'] ?? 'both'));
    if (!in_array($provider, ['r2', 'gcs', 'both'], true)) {
      $provider = 'both';
    }

    $folder      = trim($backupConfig['folder'] ?? 'backup', '/');
    $filename    = sprintf('backup_slimenews_%s.sql.gz', date('Y-m-d_His'));
    $destination = $folder ? "{$folder}/{$filename}" : $filename;

    $tempFile = null;
    try {
      // 1. Eksekusi pg_dump & kompresi Gzip
      $tempFile = $this->executePgDump();
      $fileSize = filesize($tempFile);

      // 2. Upload ke Provider (R2 / GCS / Both)
      $uploadResults = $this->uploadToProviders($tempFile, $destination, $provider);

      $duration = round(microtime(true) - $startTime, 2);

      //? LOG Record
        try {
          $this->helper->addLog($user, 'tb_job_backup', (int) time(), 'INSERT', [
            'status'              => 'SUCCESS',
            'provider'            => $provider,
            'destination'         => $destination,
            'file_size'           => $fileSize,
            'file_size_formatted' => $this->formatBytes($fileSize),
            'duration_seconds'    => $duration,
            'details'             => $uploadResults,
          ]);
        } catch (\Throwable $eLog) {
          error_log('Failed to log backup success: ' . $eLog->getMessage());
        }
      //? LOG Record

      return [
        'status'              => 'success',
        'provider'            => $provider,
        'destination'         => $destination,
        'file_size'           => $fileSize,
        'file_size_formatted' => $this->formatBytes($fileSize),
        'duration_seconds'    => $duration,
        'uploads'             => $uploadResults,
      ];
    } catch (Exception $e) {
      $duration = round(microtime(true) - $startTime, 2);

      //? LOG Record
        try {
          $this->helper->addLog($user, 'tb_job_backup', (int) time(), 'INSERT', [
            'status'           => 'FAILED',
            'provider'         => $provider,
            'destination'      => $destination,
            'duration_seconds' => $duration,
            'error'            => $e->getMessage(),
          ]);
        } catch (\Throwable $eLog) {
          error_log('Failed to log backup failure: ' . $eLog->getMessage());
        }
      //? LOG Record

      throw $e;
    } finally {
      // Pastikan file sementara selalu dibersihkan dari disk lokal
      if ($tempFile && file_exists($tempFile)) {
        @unlink($tempFile);
      }
    }
  }

  /**
   * Eksekusi utility pg_dump dan kompresi output ke file Gzip sementara.
   *
   * @return string Path file sementara .sql.gz
   * @throws Exception
   */
  private function executePgDump(): string
  {
    $dbConfig = $this->servicesConfig['database'] ?? [];
    $host     = $dbConfig['host'] ?? 'localhost';
    $port     = (string) ($dbConfig['port'] ?? '5432');
    $database = $dbConfig['database'] ?? '';
    $username = $dbConfig['username'] ?? '';
    $password = $dbConfig['password'] ?? '';

    if (empty($database) || empty($username)) {
      throw new Exception('Database configuration is incomplete in services.php', 500);
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'pg_dump_');
    if ($tempFile === false) {
      throw new Exception('Failed to create temporary file for backup', 500);
    }

    // Flag universal postgres: tanpa owner/acl agar bisa di-import ke PostgreSQL manapun
    $cmd = sprintf(
      'pg_dump -h %s -p %s -U %s -d %s --no-owner --no-acl --clean --if-exists --quote-all-identifiers',
      escapeshellarg($host),
      escapeshellarg($port),
      escapeshellarg($username),
      escapeshellarg($database)
    );

    $descriptors = [
      0 => ['pipe', 'r'], // stdin
      1 => ['pipe', 'w'], // stdout
      2 => ['pipe', 'w'], // stderr
    ];

    // Oper kredensial via environment array agar aman dari process listing
    $env = array_merge($_ENV, [
      'PGPASSWORD' => $password,
      'PGSSLMODE'  => 'require',
    ]);

    $process = proc_open($cmd, $descriptors, $pipes, null, $env);
    if (!is_resource($process)) {
      if (file_exists($tempFile)) @unlink($tempFile);
      throw new Exception('Failed to spawn pg_dump process. Please verify postgresql-client is installed.', 500);
    }

    fclose($pipes[0]);

    // Buka file sementara dengan kompresi gzip bawaan PHP (wb9 = kompresi maksimal)
    $gzHandle = gzopen($tempFile, 'wb9');
    if (!$gzHandle) {
      fclose($pipes[1]);
      fclose($pipes[2]);
      proc_close($process);
      if (file_exists($tempFile)) @unlink($tempFile);
      throw new Exception('Failed to open temporary file for gzip writing', 500);
    }

    // Stream output pg_dump ke file gzip dalam chunk 64KB
    while (!feof($pipes[1])) {
      $chunk = fread($pipes[1], 65536);
      if ($chunk !== false && strlen($chunk) > 0) {
        gzwrite($gzHandle, $chunk);
      }
    }

    gzclose($gzHandle);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
      if (file_exists($tempFile)) @unlink($tempFile);
      $errorMsg = trim($stderr) ?: "pg_dump failed with exit code {$exitCode}";
      throw new Exception("Database dump error: {$errorMsg}", 500);
    }

    if (!file_exists($tempFile) || filesize($tempFile) === 0) {
      if (file_exists($tempFile)) @unlink($tempFile);
      throw new Exception('Database dump generated an empty file', 500);
    }

    return $tempFile;
  }

  /**
   * Unggah file dump ke Cloudflare R2 dan/atau Google Cloud Storage.
   *
   * @param string $tempFile Path file lokal .sql.gz
   * @param string $destination Path tujuan pada bucket
   * @param string $provider Target provider ('r2', 'gcs', 'both')
   * @return array Status upload per provider
   * @throws Exception
   */
  private function uploadToProviders(string $tempFile, string $destination, string $provider): array
  {
    $results = [];
    $errors  = [];

    // 1. Cloudflare R2
    if ($provider === 'r2' || $provider === 'both') {
      try {
        if (!$this->r2) {
          throw new Exception('Cloudflare R2 service is not configured');
        }
        $uploadedKey   = $this->r2->put($destination, $tempFile, 'application/gzip');
        $results['r2'] = [
          'status' => 'SUCCESS',
          'key'    => $uploadedKey,
        ];
      } catch (Exception $e) {
        $errors['r2'] = $e->getMessage();
        $results['r2'] = [
          'status' => 'FAILED',
          'error'  => $e->getMessage(),
        ];
      }
    }

    // 2. Google Cloud Storage
    if ($provider === 'gcs' || $provider === 'both') {
      try {
        if (!$this->gcs) {
          throw new Exception('Google Cloud Storage service is not configured');
        }
        $gcsConfig    = $this->servicesConfig['google_cloud_storage'] ?? [];
        $bucketName   = $gcsConfig['bucket'] ?? '';
        $uploadResult = $this->gcs->storage('upload', $bucketName, $tempFile, $destination);

        // Jika storage mengembalikan pesan error (bukan $destination)
        if ($uploadResult !== $destination) {
          throw new Exception($uploadResult ?: 'Unknown GCS upload error');
        }

        $results['gcs'] = [
          'status'      => 'SUCCESS',
          'destination' => $destination,
        ];
      } catch (Exception $e) {
        $errors['gcs'] = $e->getMessage();
        $results['gcs'] = [
          'status' => 'FAILED',
          'error'  => $e->getMessage(),
        ];
      }
    }

    // Jika seluruh provider target gagal, lempar Exception
    if (!empty($errors)) {
      if (($provider === 'both' && count($errors) === 2) || ($provider !== 'both' && count($errors) === 1)) {
        throw new Exception('All target storage providers failed: ' . json_encode($errors), 500);
      }
    }

    return $results;
  }

  /**
   * Format ukuran byte ke representasi human-readable.
   *
   * @param int $bytes
   * @param int $precision
   * @return string
   */
  private function formatBytes(int $bytes, int $precision = 2): string
  {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow   = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
  }
}
