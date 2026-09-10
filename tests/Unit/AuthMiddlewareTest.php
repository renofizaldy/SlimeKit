<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Middlewares\AuthMiddleware;
use Slim\Psr7\Factory\ServerRequestFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class AuthMiddlewareTest extends TestCase
{
  public function test_static_admin_factory_creates_correct_instance()
  {
    $middleware = AuthMiddleware::admin('article:view');

    $this->assertInstanceOf(AuthMiddleware::class, $middleware);
    $this->assertSame('admin', $middleware->getRole());
    $this->assertSame('article:view', $middleware->getPermission());
  }

  public function test_static_client_factory_creates_correct_instance()
  {
    $middlewareWithPerm = AuthMiddleware::client('article:view');
    $this->assertInstanceOf(AuthMiddleware::class, $middlewareWithPerm);
    $this->assertSame('client', $middlewareWithPerm->getRole());
    $this->assertSame('article:view', $middlewareWithPerm->getPermission());

    $middlewareWithoutPerm = AuthMiddleware::client();
    $this->assertInstanceOf(AuthMiddleware::class, $middlewareWithoutPerm);
    $this->assertSame('client', $middlewareWithoutPerm->getRole());
    $this->assertNull($middlewareWithoutPerm->getPermission());
  }

  public function test_static_has_role_factory_creates_correct_instance()
  {
    $middleware = AuthMiddleware::hasRole('manager', 'dashboard:view');

    $this->assertInstanceOf(AuthMiddleware::class, $middleware);
    $this->assertSame('manager', $middleware->getRole());
    $this->assertSame('dashboard:view', $middleware->getPermission());
  }

  public function test_middleware_invokes_and_bypasses_in_testing_environment()
  {
    $middleware = AuthMiddleware::admin('article:view');

    $request = (new ServerRequestFactory())->createServerRequest('GET', '/test');
    $handler = $this->createMock(RequestHandlerInterface::class);
    $handler->expects($this->once())
      ->method('handle')
      ->willReturnCallback(function ($req) {
        $user = $req->getAttribute('user');
        $this->assertNotNull($user);
        $this->assertSame(1, $user->id);
        return new Response();
      });

    $response = $middleware($request, $handler);
    $this->assertSame(200, $response->getStatusCode());
  }
}
