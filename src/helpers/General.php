<?php

namespace App\Helpers;

use Exception;
use DateTime;
use Doctrine\DBAL\Connection;
use App\Lib\Cloudinary;
use App\Lib\Mailer;
use App\Lib\Cronhooks;

class General
{
  public function __construct()
  {}

  public function addLog(Connection $db, array $user, string $table_name, int $id_record, string $action, array $changes = []): void
  {
    $db->createQueryBuilder()
      ->insert('tb_log')
      ->values([
        'id_user'    => ':id_user',
        'table_name' => ':table_name',
        'id_record'  => ':id_record',
        'action'     => ':action',
        'changes'    => ':changes',
        'ip_address' => ':ip_address',
        'user_agent' => ':user_agent',
        'created_at' => ':created_at',
        'updated_at' => ':updated_at'
      ])
      ->setParameters([
        'id_user'    => $user['user']->id,
        'table_name' => $table_name,
        'id_record'  => $id_record,
        'action'     => $action,
        'changes'    => json_encode($changes, JSON_UNESCAPED_UNICODE),
        'ip_address' => $user['ip_address'],
        'user_agent' => $user['user_agent'],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
      ])
      ->executeStatement();
  }

  public function pictureUpload(Connection $db, Cloudinary $cloudinary, $file, $caption=null): ?array
  {
    if (empty($file)) {
      return null;
    }

    // Upload to Cloudinary
    $upload = $cloudinary->upload($file);
    if (!$upload) {
      throw new Exception('Failed to upload photo', 409);
    }

    // Generate thumbnail URL
    $thumbnail = substr_replace(
      $upload['secure_url'],
      "c_thumb,w_600/",
      strpos($upload['secure_url'], "/v") + 1,
      0
    );

    // Save to database
    $db->createQueryBuilder()
      ->insert('tb_picture')
      ->values([
        'id_cloud'   => ':id_cloud',
        'original'   => ':original',
        'thumbnail'  => ':thumbnail',
        'caption'    => ':caption',
        'created_at' => ':created_at'
      ])
      ->setParameter('id_cloud', $upload['public_id'])
      ->setParameter('original', $upload['secure_url'])
      ->setParameter('thumbnail', $thumbnail)
      ->setParameter('caption', $caption)
      ->setParameter('created_at', date('Y-m-d H:i:s'))
      ->executeStatement();

    return [
      'id'  => $db->lastInsertId(),
      'url' => $upload['secure_url']
    ];
  }

  public function handleCronjob(Connection $db, Cronhooks $cronhooks, int $articleId, string $slug, string $publish, ?string $existingCronId = null): bool
  {
    try {
      // Hapus cron lama kalau ada
      if (!empty($existingCronId)) {
        try {
          // hapus dari Cronhooks
          $cronhooks->deleteSchedule($existingCronId);

          // hapus dari database
          $db->createQueryBuilder()
            ->delete('tb_cronhooks')
            ->where('id_parent = :id_parent')
            ->andWhere('type = :type')
            ->setParameters([
              'id_parent' => $articleId,
              'type'      => 'article'
            ])
            ->executeStatement();
        }
        catch (\Throwable $e) {
          // diamkan saja, biar tidak ganggu flow
        }
      }

      // Hanya buat cron kalau publish time di masa depan
      $publishTime = date('Y-m-d H:i:s', strtotime($publish));
      if (strtotime($publishTime) > time()) {
        // Buat cron baru
        $cron = $cronhooks->createSchedule(
          $publish,
          [
            'title'    => 'Publish Artikel #'.$articleId,
            'timezone' => 'Asia/Jakarta',
            'method'   => 'POST',
            'payload'  => [
              'slug' => $slug
            ],
          ]
        );

        if (!empty($cron['id'])) {
          // Simpan ke DB
          $db->createQueryBuilder()
            ->insert('tb_cronhooks')
            ->values([
              'id_parent'    => ':id_parent',
              'type'         => ':type',
              'id_cronhooks' => ':id_cronhooks',
            ])
            ->setParameters([
              'id_parent'    => $articleId,
              'type'         => 'article',
              'id_cronhooks' => $cron['id']
            ])
            ->executeStatement();
        }
      }

      return true;
    }
    catch (\Throwable $e) {
      // diamkan juga
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
