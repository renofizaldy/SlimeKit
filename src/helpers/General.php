<?php

namespace App\Helpers;

use Exception;
use DateTime;
use App\Lib\Cloudinary;
use App\Lib\Mailer;
use App\Lib\Cronhooks;

use App\Models\Log;
use App\Models\Picture;
use App\Models\Cronhooks as CronhooksModel;
use App\Models\Article;
use Illuminate\Support\Carbon;

class General
{
  public function __construct()
  {}

  public function addLog(array $user, string $table_name, int $id_record, string $action, array $changes = []): void
  {
    $userId = $user['user']->id ?? ($user['user']['id'] ?? null);
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

  public function pictureUpload(Cloudinary $cloudinary, $file, $caption=null): ?array
  {
    if (empty($file)) return null;

    $upload = $cloudinary->upload($file);
    if (!$upload) {
      throw new Exception('Failed to upload photo', 409);
    }

    $thumbnail = substr_replace(
      $upload['secure_url'],
      "c_thumb,w_600/",
      strpos($upload['secure_url'], "/v") + 1,
      0
    );

    $picture = Picture::create([
      'id_cloud'   => $upload['public_id'],
      'original'   => $upload['secure_url'],
      'thumbnail'  => $thumbnail,
      'caption'    => $caption,
    ]);

    return [
      'id'  => $picture->id,
      'url' => $upload['secure_url']
    ];
  }

  public function recomputeCron(Cronhooks $cronhooks): bool
  {
    try {
      $maxSlots     = 5;

      //* NEW
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
              'title'    => 'Publish Artikel #' . $article->id,
              'timezone' => 'Asia/Jakarta',
              'method'   => 'POST',
              'payload'  => [
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
    } catch (\Throwable $e) {
      error_log("recomputeCronjobs fatal: " . $e->getMessage());
      return true;
    }
    //* NEW
  }

  public function handleCronjob(Cronhooks $cronhooks, int $articleId, string $slug, string $publish, ?string $existingCronId = null): bool
  {
    try {
      //* 1. Hapus cron lama kalau ada
      if (!empty($existingCronId)) {
        try {
          //? Hapus dari Cronhooks Service
          $cronhooks->deleteSchedule($existingCronId);

          //? Hapus dari database lokal
          CronhooksModel::where('id_parent', $articleId)
            ->where('type', 'article')
            ->delete();
        } catch (\Throwable $e) {
        }
      }

      //* 2. Hanya buat cron kalau publish time di masa depan
      //? Gunakan Carbon untuk perbandingan waktu yang lebih robust
      $publishTime = Carbon::parse($publish);

      if ($publishTime->isFuture()) {
        //? Buat cron baru di Service
        $cron = $cronhooks->createSchedule(
          $publishTime->format('Y-m-d H:i:s'),
          [
            'title'    => 'Publish Artikel #' . $articleId,
            'timezone' => 'Asia/Jakarta',
            'method'   => 'POST',
            'payload'  => [
              'slug' => $slug
            ],
          ]
        );

        if (!empty($cron['id'])) {
          //? Simpan ke DB via Eloquent
          CronhooksModel::create([
            'id_parent'    => $articleId,
            'type'         => 'article',
            'id_cronhooks' => $cron['id']
          ]);
        }
      }

      return true;
    } catch (\Throwable $e) {
      return false;
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

  public function getClientIp($request): ?string
  {
    $xff = $request?->getHeaderLine('X-Forwarded-For');
    $remoteAddr = $request?->getServerParams()['REMOTE_ADDR'] ?? null;
    return $xff ? explode(',', $xff)[0] : $remoteAddr;
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
}
