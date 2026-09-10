<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Middlewares\RateLimitMiddleware;
use Slim\Psr7\Factory\ServerRequestFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class RateLimitMiddlewareTest extends TestCase
{
  public function test_static_admin_factory_creates_correct_instance()
  {
    $middleware = RateLimitMiddleware::admin();

    $this->assertInstanceOf(RateLimitMiddleware::class, $middleware);
    $this->assertSame(100, $middleware->getLimit());
    $this->assertSame(60, $middleware->getTimeWindow());
  }

  public function test_static_client_factory_creates_correct_instance()
  {
    $middleware = RateLimitMiddleware::client();

    $this->assertInstanceOf(RateLimitMiddleware::class, $middleware);
    $this->assertSame(60, $middleware->getLimit());
    $this->assertSame(60, $middleware->getTimeWindow());
  }

  public function test_static_custom_factory_creates_correct_instance()
  {
    $middleware = RateLimitMiddleware::custom(250, 120);

    $this->assertInstanceOf(RateLimitMiddleware::class, $middleware);
    $this->assertSame(250, $middleware->getLimit());
    $this->assertSame(120, $middleware->getTimeWindow());
  }

  public function test_middleware_bypasses_in_testing_environment()
  {
    $middleware = RateLimitMiddleware::admin();

    $request = (new ServerRequestFactory())->createServerRequest('GET', '/test');
    $handler = $this->createMock(RequestHandlerInterface::class);
    $handler->expects($this->once())
      ->method('handle')
      ->willReturn(new Response());

    $response = $middleware->process($request, $handler);
    $this->assertSame(200, $response->getStatusCode());
  }
}
