<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Middlewares\TokenMiddleware;
use App\Helpers\General;
use App\Lib\Tokenize;
use App\Lib\Valkey;
use Slim\Psr7\Factory\ServerRequestFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class TokenMiddlewareTest extends TestCase
{
  private string $secretKey = 'test-secret-jwt-key-2026-minimum-32-bytes!';
  private Tokenize $tokenize;
  private TokenMiddleware $middleware;

  protected function setUp(): void
  {
    parent::setUp();
    $this->tokenize = new Tokenize(['secret_key' => $this->secretKey]);
    $valkey = $this->createMock(Valkey::class);
    $helper = new General($valkey);
    $this->middleware = new TokenMiddleware($helper, $this->tokenize);
  }

  protected function tearDown(): void
  {
    $_ENV['ENVIRONMENT'] = 'TESTING';
    parent::tearDown();
  }

  public function test_middleware_bypasses_in_testing_environment()
  {
    $_ENV['ENVIRONMENT'] = 'TESTING';

    $request = (new ServerRequestFactory())->createServerRequest('GET', '/test');
    $handler = $this->createMock(RequestHandlerInterface::class);
    $handler->expects($this->once())
      ->method('handle')
      ->willReturnCallback(function ($req) {
        $this->assertSame(1, $req->getAttribute('user_id'));
        return new Response();
      });

    $response = ($this->middleware)($request, $handler);
    $this->assertSame(200, $response->getStatusCode());
  }

  public function test_middleware_returns_400_when_authorization_header_missing()
  {
    $_ENV['ENVIRONMENT'] = 'PRODUCTION';

    $request = (new ServerRequestFactory())->createServerRequest('POST', '/job/article_sync_stats');
    $handler = $this->createMock(RequestHandlerInterface::class);
    $handler->expects($this->never())->method('handle');

    $response = ($this->middleware)($request, $handler);
    $this->assertSame(400, $response->getStatusCode());

    $body = json_decode((string) $response->getBody(), true);
    $this->assertSame(400, $body['status']);
    $this->assertStringContainsString('Bad Request', $body['message']);
  }

  public function test_middleware_returns_400_when_bearer_format_invalid()
  {
    $_ENV['ENVIRONMENT'] = 'PRODUCTION';

    $request = (new ServerRequestFactory())->createServerRequest('POST', '/job/article_sync_stats')
      ->withHeader('Authorization', 'Basic dXNlcjpwYXNz');
    $handler = $this->createMock(RequestHandlerInterface::class);
    $handler->expects($this->never())->method('handle');

    $response = ($this->middleware)($request, $handler);
    $this->assertSame(400, $response->getStatusCode());

    $body = json_decode((string) $response->getBody(), true);
    $this->assertSame(400, $body['status']);
  }

  public function test_middleware_returns_401_when_token_is_invalid()
  {
    $_ENV['ENVIRONMENT'] = 'PRODUCTION';

    $request = (new ServerRequestFactory())->createServerRequest('POST', '/job/article_sync_stats')
      ->withHeader('Authorization', 'Bearer invalid-token-string');
    $handler = $this->createMock(RequestHandlerInterface::class);
    $handler->expects($this->never())->method('handle');

    $response = ($this->middleware)($request, $handler);
    $this->assertSame(401, $response->getStatusCode());

    $body = json_decode((string) $response->getBody(), true);
    $this->assertSame(401, $body['status']);
    $this->assertStringContainsString('Unauthorized', $body['message']);
  }

  public function test_middleware_returns_401_when_token_is_expired()
  {
    $_ENV['ENVIRONMENT'] = 'PRODUCTION';

    // Encode token that expired 1 hour ago
    $expiredToken = $this->tokenize->encode(['user_id' => 0], -3600);

    $request = (new ServerRequestFactory())->createServerRequest('POST', '/job/article_sync_stats')
      ->withHeader('Authorization', 'Bearer ' . $expiredToken);
    $handler = $this->createMock(RequestHandlerInterface::class);
    $handler->expects($this->never())->method('handle');

    $response = ($this->middleware)($request, $handler);
    $this->assertSame(401, $response->getStatusCode());

    $body = json_decode((string) $response->getBody(), true);
    $this->assertSame(401, $body['status']);
    $this->assertStringContainsString('Unauthorized', $body['message']);
  }

  public function test_middleware_success_with_valid_static_token()
  {
    $_ENV['ENVIRONMENT'] = 'PRODUCTION';

    // 10 years static token
    $validToken = $this->tokenize->encode([
      'user_id' => 0,
      'role'    => 'system_job',
      'scope'   => 'job_worker',
    ], 10 * 365 * 24 * 3600);

    $request = (new ServerRequestFactory())->createServerRequest('POST', '/job/article_sync_stats')
      ->withHeader('Authorization', 'Bearer ' . $validToken);

    $handler = $this->createMock(RequestHandlerInterface::class);
    $handler->expects($this->once())
      ->method('handle')
      ->willReturnCallback(function ($req) {
        $this->assertSame(0, $req->getAttribute('user_id'));
        $res = new Response();
        $res->getBody()->write(json_encode(['status' => 'success']));
        return $res->withStatus(200);
      });

    $response = ($this->middleware)($request, $handler);
    $this->assertSame(200, $response->getStatusCode());
  }
}
