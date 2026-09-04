<?php

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class CorsMiddleware
{
  public function __invoke(Request $request, RequestHandler $handler): Response
  {
    // Ambil domain yang diizinkan dari .env, default '*' jika belum diatur
    $allowedOriginsStr = $_ENV['ALLOWED_ORIGINS'] ?? '*';
    $allowedOrigins = array_map('trim', explode(',', $allowedOriginsStr));

    $origin = $request->getHeaderLine('Origin');
    $allowOrigin = '';

    if (in_array('*', $allowedOrigins)) {
      $allowOrigin = '*';
    } elseif (in_array($origin, $allowedOrigins)) {
      $allowOrigin = $origin;
    }

    // Tangani preflight (OPTIONS) request sebelum masuk ke Router
    if ($request->getMethod() === 'OPTIONS') {
      $response = new SlimResponse();
      if ($allowOrigin) {
        $response = $response->withHeader('Access-Control-Allow-Origin', $allowOrigin)
          ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
          ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, Content-Encoding')
          ->withHeader('Access-Control-Allow-Credentials', 'true');
      }
      return $response->withStatus(200);
    }

    // Teruskan request ke controller
    $response = $handler->handle($request);

    // Tambahkan header CORS ke response normal (GET, POST, dll)
    if ($allowOrigin) {
      $response = $response->withHeader('Access-Control-Allow-Origin', $allowOrigin)
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, Content-Encoding')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
    }

    return $response;
  }
}
