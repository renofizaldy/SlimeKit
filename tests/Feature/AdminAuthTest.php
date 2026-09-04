<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Admin\AdminAuthService;
use Exception;

class AdminAuthTest extends TestCase
{
  public function test_login_validation_fails_when_empty()
  {
    $app = $this->getAppInstance();

    $request = $this->createRequest('POST', '/admin/auth/login');
    $request = $request->withParsedBody([]);

    $response = $app->handle($request);

    $this->assertEquals(422, $response->getStatusCode());

    $body = $this->parseJson($response);
    $this->assertArrayHasKey('status', $body);
    $this->assertEquals('error', $body['status']);
    $this->assertEquals('Validasi gagal', $body['message']);
    $this->assertArrayHasKey('errors', $body);
    $this->assertEquals('VALIDATION_ERROR', $body['code']);
  }

  public function test_login_fails_with_invalid_credentials()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminAuthService::class);
    $mockService->method('login')
      ->willThrowException(new Exception('Username atau password salah', 401));

    $container->set(AdminAuthService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/auth/login');
    $request = $request->withParsedBody([
      'username' => 'wrong',
      'password' => 'wrong'
    ]);

    $response = $app->handle($request);

    $this->assertEquals(401, $response->getStatusCode());

    $body = $this->parseJson($response);
    $this->assertArrayHasKey('status', $body);
    $this->assertEquals('error', $body['status']);
    $this->assertEquals('Username atau password salah', $body['message']);
  }

  public function test_login_success_returns_jwt()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminAuthService::class);
    $mockService->method('login')
      ->willReturn([
        'user' => [
          'id'           => 1,
          'name'         => 'Admin Test',
          'email'        => 'admin@test.com',
          'role'         => 'Super Admin',
          'id_user_role' => 1
        ],
        'role' => 'super_admin'
      ]);

    $container->set(AdminAuthService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/auth/login');
    $request = $request->withParsedBody([
      'username' => 'admin',
      'password' => 'password'
    ]);

    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());

    $body = $this->parseJson($response);
    $this->assertEquals('Ok', $body['message']);
    $this->assertArrayHasKey('data', $body);
    $this->assertArrayHasKey('token', $body['data']);
    $this->assertEquals('Admin Test', $body['data']['nama']);
  }
}
