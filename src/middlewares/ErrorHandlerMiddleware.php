<?php

namespace App\Middlewares;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpException;
use Throwable;
use Psr\Log\LoggerInterface;

class ErrorHandlerMiddleware
{
  private bool $displayErrorDetails;
  private ?LoggerInterface $logger;

  public function __construct(bool $displayErrorDetails = false, ?LoggerInterface $logger = null)
  {
    $this->displayErrorDetails = $displayErrorDetails;
    $this->logger = $logger;
  }

  public function __invoke(Request $request, RequestHandler $handler): Response
  {
    try {
      return $handler->handle($request);
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  private function handleException(Throwable $e): Response
  {
    //* Tentukan HTTP status code
    $statusCode = $this->resolveStatusCode($e);

    //* Lakukan logging jika logger tersedia (terutama untuk error 5xx)
    if ($this->logger && $statusCode >= 500) {
      $this->logger->error($e->getMessage(), [
        'exception' => $e,
        'file'      => $e->getFile(),
        'line'      => $e->getLine()
      ]);
    }

    //* Tentukan pesan error
    $message = $this->resolveMessage($e, $statusCode);

    //* Susun body response
    $body = [
      'error' => [
        'code'    => $statusCode,
        'message' => $message,
      ]
    ];

    //* Tampilkan detail error hanya di mode development
    if ($this->displayErrorDetails) {
      $body['error']['type']  = get_class($e);
      $body['error']['file']  = $e->getFile() . ':' . $e->getLine();
      $body['error']['trace'] = array_slice(
        array_map(fn ($frame) => [
          'file'     => ($frame['file'] ?? '?') . ':' . ($frame['line'] ?? '?'),
          'function' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? ''),
        ], $e->getTrace()),
        0,
        10 // Batasi 10 frame agar response tidak terlalu besar
      );
    }

    //* Buat dan kirim response JSON
    $response = new Response();
    $response->getBody()->write(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return $response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus($statusCode);
  }

  /**
   * Resolve HTTP status code dari exception.
   * - HttpException bawaan Slim sudah punya getCode() yang benar.
   * - Exception custom juga bisa lempar code 4xx/5xx via constructor.
   * - Fallback ke 500 jika code tidak valid.
   */
  private function resolveStatusCode(Throwable $e): int
  {
    //* Slim's HttpException punya method getCode() yang pasti valid
    if ($e instanceof HttpException) {
      return $e->getCode();
    }

    //* Exception custom — ambil code jika valid HTTP status
    $code = $e->getCode();
    if (is_int($code) && $code >= 400 && $code <= 599) {
      return $code;
    }

    //* Fallback
    return 500;
  }

  /**
   * Resolve pesan error yang aman untuk ditampilkan.
   * - Di production, error 500 tidak menampilkan pesan internal.
   * - Error 4xx tetap menampilkan pesan karena biasanya disengaja.
   */
  private function resolveMessage(Throwable $e, int $statusCode): string
  {
    //* HttpNotFoundException -> pesan khusus
    if ($e instanceof HttpNotFoundException) {
      return 'Endpoint yang diminta tidak ditemukan.';
    }

    //* HttpMethodNotAllowedException -> pesan khusus
    if ($e instanceof HttpMethodNotAllowedException) {
      return 'HTTP method tidak diizinkan untuk endpoint ini.';
    }

    //* Error 4xx — tampilkan pesan dari exception (biasanya pesan validasi/bisnis)
    if ($statusCode >= 400 && $statusCode < 500) {
      return $e->getMessage() ?: 'Bad Request';
    }

    //* Error 5xx — di production, sembunyikan detail internal
    if ($this->displayErrorDetails) {
      return $e->getMessage() ?: 'Internal Server Error';
    }

    return 'Terjadi kesalahan pada server. Silakan coba lagi nanti.';
  }
}
