<?php
/**
 * Service Configuration Registry
 * 
 * Semua konfigurasi service pihak ke-3 dikumpulkan di sini.
 * Setiap key = nama service, value = array config.
 * Semua $_ENV dibaca DI SINI SAJA, bukan di dalam class.
 */

return [

  //* Infrastructure
  'database' => [
    'driver'    => $_ENV['DB_CLIENT'] ?? 'mysql',
    'host'      => $_ENV['DB_HOST'] ?? 'localhost',
    'port'      => $_ENV['DB_PORT'] ?? '3306',
    'database'  => $_ENV['DB_NAME'] ?? '',
    'username'  => $_ENV['DB_USER'] ?? '',
    'password'  => $_ENV['DB_PASS'] ?? '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'options'   => [
      \PDO::ATTR_EMULATE_PREPARES => true,
    ],
  ],

  'valkey' => [
    'scheme'   => $_ENV['VALKEY_SCHEME'] ?? 'tls',
    'host'     => $_ENV['VALKEY_HOST'] ?? '',
    'port'     => $_ENV['VALKEY_PORT'] ?? 6379,
    'username' => $_ENV['VALKEY_USERNAME'] ?? '',
    'password' => $_ENV['VALKEY_PASSWORD'] ?? '',
  ],

  //* Backup Providers
  'backup_provider' => [
    'provider' => $_ENV['BACKUP_PROVIDER'] ?? 'both', // 'r2', 'gcs', 'both'
    'folder'   => $_ENV['BACKUP_FOLDER'] ?? 'backup',
  ],

  //* Job & Background Workers
  'job' => [
    'token' => $_ENV['JOB_STATIC_TOKEN'] ?? '',
  ],

  //* Storage Providers
  'cloudflare_r2' => [
    'region'     => $_ENV['R2_REGION'] ?? 'auto',
    'endpoint'   => $_ENV['R2_ENDPOINT'] ?? '',
    'access_key' => $_ENV['R2_ACCESS_KEY_ID'] ?? '',
    'secret_key' => $_ENV['R2_SECRET_ACCESS_KEY'] ?? '',
    'bucket'     => $_ENV['R2_BUCKET'] ?? '',
  ],

  'cloudinary' => [
    'url'      => $_ENV['CLOUDINARY_URL'] ?? '',
    'app_name' => $_ENV['APP_NAME'] ?? '',
  ],

  'google_cloud_storage' => [
    'bucket'           => $_ENV['GCS_BUCKET'] ?? '',
    'credentials_path' => !empty($_ENV['GCS_CREDENTIALS_PATH'])
      ? (file_exists($_ENV['GCS_CREDENTIALS_PATH'])
          ? $_ENV['GCS_CREDENTIALS_PATH']
          : dirname(__DIR__, 2) . '/' . ltrim($_ENV['GCS_CREDENTIALS_PATH'], '/\\'))
      : dirname(__DIR__, 2) . '/assets/gcs-service-account.json',
  ],

  //* Google Services
  'google_analytics' => [
    'property_id' => $_ENV['GA_PROPERTY_ID'] ?? '',
    'credentials' => $_ENV['GOOGLE_APPLICATION_CREDENTIALS'] ?? '',
  ],

  'google_apps' => [
    'client_id'     => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
    'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
    'redirect_uri'  => $_ENV['GOOGLE_REDIRECT_URI'] ?? '',
  ],

  //* AI Clients
  'gemini' => [
    'api_key' => $_ENV['GEMINI_API_KEY'] ?? '',
  ],

  'openai' => [
    'api_key' => $_ENV['OPENAI_API_KEY'] ?? '',
  ],

  'tavily' => [
    'api_key' => $_ENV['TAVILY_API_KEY'] ?? '',
  ],

  //* Communication
  'mailer' => [
    'host'       => $_ENV['SMTP_HOST'] ?? '',
    'port'       => $_ENV['SMTP_PORT'] ?? 587,
    'username'   => $_ENV['SMTP_USER'] ?? '',
    'password'   => $_ENV['SMTP_PASS'] ?? '',
    'from_name'  => $_ENV['MAIL_FROM_NAME'] ?? '',
    'from_email' => $_ENV['MAIL_FROM_EMAIL'] ?? $_ENV['MAIL_FROM'] ?? 'noreply@example.com',
  ],

  'cronhooks' => [
    'api_key'      => $_ENV['CRONHOOKS_API_KEY'] ?? '',
    'base_url'     => $_ENV['CRONHOOKS_BASE_URL'] ?? 'https://api.cronhooks.io',
    'callback_url' => $_ENV['CRONHOOKS_CALLBACK'] ?? '',
  ],

  'next_revalidator' => [
    'secret'    => $_ENV['NEXT_REVALIDATE_SECRET'] ?? '',
    'endpoints' => $_ENV['NEXT_REVALIDATE_ENDPOINTS'] ?? '',
  ],

  'wapito' => [
    'api_token' => $_ENV['WAPITO_API_TOKEN'] ?? '',
    'base_url'  => $_ENV['WAPITO_BASE_URL'] ?? 'https://api.wapito.com',
  ],

  'wappfly' => [
    'api_token' => $_ENV['WAPPFLY_API_TOKEN'] ?? '',
    'base_url'  => $_ENV['WAPPFLY_BASE_URL'] ?? 'https://wappfly.com/api',
  ],

  //* Auth
  'tokenize' => [
    'secret_key' => $_ENV['SYM_KEY'] ?? '',
  ],
];
