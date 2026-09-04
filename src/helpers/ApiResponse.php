<?php

namespace App\Helpers;

use Psr\Http\Message\ResponseInterface as Response;

class ApiResponse
{
  /**
   * Format untuk response sukses (200, 201, dll)
   *
   * @param Response $response Objek PSR-7 Response bawaan Slim
   * @param mixed    $data     Data yang ingin dikembalikan
   * @param string   $message  Pesan sukses
   * @param int      $status   HTTP Status code (default 200)
   * @return Response
   */
  public function success(
    Response $response,
    $data = [],
    string $message = 'Ok',
    int $status = 200
  ): Response {
    $payload = [
      'status'  => 'success',
      'message' => $message,
      'data'    => $data,
    ];

    // JSON_UNESCAPED_UNICODE agar karakter khusus tidak di-escape
    // JSON_UNESCAPED_SLASHES agar url (garis miring) tidak di-escape
    $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return $response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus($status);
  }

  /**
   * Format untuk response sukses dengan paginasi
   *
   * @param Response $response   Objek PSR-7 Response bawaan Slim
   * @param array    $items      Daftar data hasil query halaman ini
   * @param int      $total      Total keseluruhan data
   * @param int      $page       Halaman saat ini (default 1)
   * @param int      $pageSize   Jumlah data per halaman (default 10)
   * @param string   $message    Pesan sukses
   * @param int      $status     HTTP Status code (default 200)
   * @return Response
   */
  public function paginated(
    Response $response,
    array $items = [],
    int $total = 0,
    int $page = 1,
    int $pageSize = 10,
    string $message = 'Ok',
    int $status = 200
  ): Response {
    $page = max(1, $page);
    $pageSize = max(1, $pageSize);
    $totalPages = $total > 0 ? (int) ceil($total / $pageSize) : 1;

    $payload = [
      'status'  => 'success',
      'message' => $message,
      'data'    => [
        'items'      => $items,
        'pagination' => [
          'page'       => $page,
          'pageSize'   => $pageSize,
          'total'      => $total,
          'totalPages' => $totalPages,
        ],
      ],
    ];

    $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return $response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus($status);
  }

  /**
   * Format untuk response error (400, 401, 404, 500, dll) - Format Flat
   *
   * @param Response $response Objek PSR-7 Response bawaan Slim
   * @param string   $message  Pesan error utama
   * @param int      $status   HTTP Status code (default 400)
   * @param mixed    $errors   Detail error tambahan (misal: error validasi tiap field)
   * @return Response
   */
  public function error(
    Response $response,
    string $message = 'Terjadi kesalahan',
    int $status = 400,
    $errors = null,
    ?string $errorCode = null
  ): Response {
    $payload = [
      'status'  => 'error',
      'message' => $message,
      'code'    => $errorCode ?? $this->errorCodeFromStatus($status),
    ];

    if ($errors !== null) {
      $payload['errors'] = $errors;
    }

    $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return $response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus($status);
  }

  /**
   * Format response untuk exception, otomatis mengecek validation errors
   *
   * @param Response  $response
   * @param \Exception $e
   * @return Response
   */
  public function handleException(Response $response, \Exception $e): Response
  {
    $code = $e->getCode();
    $status = (is_int($code) && $code >= 100 && $code <= 599) ? $code : 500;
    
    $errors    = null;
    $message   = $e->getMessage();
    $errorCode = null;

    $decoded = json_decode($message, true);
    if (is_array($decoded) && isset($decoded['validation_errors'])) {
      $errors    = $decoded['validation_errors'];
      $message   = 'Validasi gagal';
      $status    = 422;
      $errorCode = 'VALIDATION_ERROR';
    }

    return $this->error($response, $message, $status, $errors, $errorCode);
  }

  private function errorCodeFromStatus(int $status): string
  {
    return match ($status) {
      400 => 'BAD_REQUEST',
      401 => 'UNAUTHORIZED',
      403 => 'FORBIDDEN',
      404 => 'NOT_FOUND',
      409 => 'CONFLICT',
      422 => 'UNPROCESSABLE_ENTITY',
      default => $status >= 500 ? 'INTERNAL_SERVER_ERROR' : 'HTTP_ERROR',
    };
  }
}
