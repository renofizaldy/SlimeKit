<?php

namespace App\Middlewares;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Exception;
use App\Helpers\General;
use App\Lib\Tokenize;

class TokenMiddleware
{
  public function __construct(
    private General $helper,
    private Tokenize $tokenize
  ) {}

  public function __invoke(Request $request, RequestHandler $handler): Response
  {
    try {
      if (isset($_ENV['ENVIRONMENT']) && $_ENV['ENVIRONMENT'] === 'TESTING') {
        $user_id = 1;
      } else {
        $authHeader = $request->getHeaderLine('Authorization');
        $token      = $this->extractTokenFromHeader($authHeader);

        if (empty($token)) {
          throw new Exception('Bad Request', 400);
        }

        $jwt = $this->tokenize;
        if (!$jwt->verify_time($token)) {
          throw new Exception('Unauthorized', 401);
        }

        $user_id = (int) $jwt->decode($token)->user_id;
      }

      $request = $request->withAttribute('user_id', $user_id);
      return $handler->handle($request);
    }
    catch (Exception $e) {
      $result = [
        'message' => 'Error: ' . $e->getMessage(),
        'status'  => $this->helper->normalizeHttpStatus($e->getCode())
      ];

      $response = new Response();
      $response->getBody()->write(json_encode($result));
      return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($result['status']);
    }
  }

  private function extractTokenFromHeader(string $authHeader): ?string
  {
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
      return $matches[1];
    }
    return null;
  }
}
