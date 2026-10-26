#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Lib\Tokenize;

// Load .env
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$secretKey = $_ENV['SYM_KEY'] ?? '';

if (empty($secretKey)) {
  fwrite(STDERR, "❌ Error: SYM_KEY tidak ditemukan di environment.\n");
  exit(1);
}

$tokenize = new Tokenize(['secret_key' => $secretKey]);

// Payload virtual system user for background worker & Next.js BFF
$payload = [
  'user_id' => 0,
  'role'    => 'system_job',
  'scope'   => 'job_worker',
];

// 10 years expiration in seconds (10 * 365 * 24 * 60 * 60)
$tenYearsSeconds = 10 * 365 * 24 * 60 * 60;

$token = $tokenize->encode($payload, $tenYearsSeconds);

if (!$token) {
  fwrite(STDERR, "❌ Error: Gagal men-generate token JWT.\n");
  exit(1);
}

$isRaw = in_array('--raw', $argv, true);

if ($isRaw) {
  echo $token;
} else {
  echo "\n=======================================================\n";
  echo "🚀 SlimeNews Job Static Token (10 Years Expiration)\n";
  echo "=======================================================\n";
  echo "Payload : " . json_encode($payload) . "\n";
  echo "Expires : " . date('Y-m-d H:i:s', time() + $tenYearsSeconds) . "\n";
  echo "Token   :\n\n" . $token . "\n\n";
  echo "=======================================================\n";
  echo "Simpan token ini di variabel JOB_STATIC_TOKEN pada file .env\n\n";
}
