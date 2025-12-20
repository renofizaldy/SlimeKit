<?php

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response as SlimResponse;

class RateLimitMiddleware implements MiddlewareInterface
{
  protected int $limit; // limit
  protected int $timeWindow; // in seconds

  public function __construct(int $limit = 60, int $timeWindow = 60)
  {
    $this->limit      = $limit;
    $this->timeWindow = $timeWindow;
  }

  public function process(Request $request, Handler $handler): Response
  {
    $serverParams = $request->getServerParams();

    // Prioritaskan IP asli user (Cloudflare > Proxy > Direct)
    if (isset($serverParams['HTTP_CF_CONNECTING_IP'])) {
      $ip = $serverParams['HTTP_CF_CONNECTING_IP'];
    } elseif (isset($serverParams['HTTP_X_FORWARDED_FOR'])) {
      $ip = explode(',', $serverParams['HTTP_X_FORWARDED_FOR'])[0];
    } else {
      $ip = $serverParams['REMOTE_ADDR'] ?? 'unknown';
    }

    $key = 'rate_limit_' . md5($ip);
    $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $key;

    $data = [
      'count' => 0,
      'start' => time()
    ];

    if (file_exists($cacheFile)) {
      $content = file_get_contents($cacheFile);
      if ($content !== false) {
        $decoded = json_decode($content, true);
        if (is_array($decoded) && isset($decoded['count'], $decoded['start'])) {
          $data = $decoded;
        }
      }
    }

    if ((time() - $data['start']) > $this->timeWindow) {
      //! Reset counter after time window expired
      $data = [
        'count' => 0,
        'start' => time()
      ];
    }

    $data['count']++;

    if ($data['count'] > $this->limit) {
      $response = new SlimResponse();
      $payload = [
        'error'   => true,
        'message' => 'Rate limit exceeded. Please wait and try again later.',
        'meta'    => [
          'limit'     => $this->limit,
          'remaining' => 0,
          'reset_in'  => max(0, ($data['start'] + $this->timeWindow) - time())
        ]
      ];

      $response->getBody()->write(json_encode($payload));

      return $response
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('X-RateLimit-Limit', (string)$this->limit)
        ->withHeader('X-RateLimit-Remaining', '0')
        ->withHeader('X-RateLimit-Reset', (string)($data['start'] + $this->timeWindow))
        ->withStatus(429);
    }

    file_put_contents($cacheFile, json_encode($data), LOCK_EX);

    $response = $handler->handle($request);

    return $response
      ->withHeader('X-RateLimit-Limit', (string)$this->limit)
      ->withHeader('X-RateLimit-Remaining', (string)max(0, $this->limit - $data['count']))
      ->withHeader('X-RateLimit-Reset', (string)($data['start'] + $this->timeWindow));
  }
}
