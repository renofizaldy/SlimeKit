<?php

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response as SlimResponse;

class GzipDecoderMiddleware implements MiddlewareInterface
{
  public function process(Request $request, Handler $handler): Response
  {
    $contentEncoding = $request->getHeaderLine('Content-Encoding');

    if ($contentEncoding === 'gzip') {
      $rawBody = file_get_contents('php://input');
      $uncompressed = gzdecode($rawBody);

      if ($uncompressed === false) {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode(['message' => 'Invalid GZIP data'], JSON_UNESCAPED_UNICODE));
        return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(400);
      }

      $parsed = json_decode($uncompressed, true);
      if ($parsed === null) {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode(['message' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE));
        return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(400);
      }

      $request = $request->withParsedBody($parsed);
    }

    return $handler->handle($request);
  }
}
