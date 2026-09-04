<?php

namespace App\Lib;

use Google_Client;
use Exception;

class GoogleApps
{
  private $client;

  public function __construct(array $config)
  {
    $this->client = new Google_Client();
    $this->client->setClientId($config['client_id']);
    $this->client->setClientSecret($config['client_secret']);
    $this->client->setRedirectUri($config['redirect_uri']);
    $this->client->setAccessType('offline'); // Penting untuk mendapatkan refresh_token
    $this->client->setPrompt('consent'); // Memastikan refresh_token selalu diberikan
    $this->client->setScopes([
      'https://www.googleapis.com/auth/youtube.upload',
      'https://www.googleapis.com/auth/youtube',
      'https://www.googleapis.com/auth/drive.file',
      'https://www.googleapis.com/auth/userinfo.email',
      'https://www.googleapis.com/auth/userinfo.profile'
    ]);
  }

  /**
   * Membuat URL otentikasi untuk diakses oleh admin.
   * @return string
   */
  public function createAuthenticationUrl(): string
  {
    return $this->client->createAuthUrl();
  }

  /**
   * Mengambil access & refresh token menggunakan authorization code.
   * @param string $authCode
   * @return array
   * @throws Exception
   */
  public function fetchTokensWithAuthCode(string $authCode): array
  {
    $tokenPayload = $this->client->fetchAccessTokenWithAuthCode($authCode);

    if (isset($tokenPayload['error'])) {
      throw new Exception('Failed to retrieve access token: ' . $tokenPayload['error_description']);
    }

    if (empty($tokenPayload['refresh_token'])) {
      throw new Exception('Refresh token not received. Ensure you are using setPrompt(\'consent\') and the user has not previously authorized the app without it.');
    }

    return $tokenPayload;
  }

  /**
   * Mendapatkan access token yang baru menggunakan refresh token.
   * @param string $refreshToken
   * @return string
   * @throws Exception
   */
  public function getFreshAccessToken(string $refreshToken): string
  {
    if (empty($refreshToken)) {
      throw new Exception('Refresh token is missing.', 401);
    }
    
    // Langsung gunakan refresh token untuk mengambil access token baru.
    // Metode ini akan mengembalikan payload token yang baru & lengkap.
    $newAccessTokenPayload = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);

    // Periksa jika ada error saat proses refresh
    if (isset($newAccessTokenPayload['error'])) {
        throw new Exception('Failed to refresh access token: ' . ($newAccessTokenPayload['error_description'] ?? 'Unknown error'));
    }
    
    // Kembalikan hanya string access_token-nya
    return $newAccessTokenPayload['access_token'];
  }
}