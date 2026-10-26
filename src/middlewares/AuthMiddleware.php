<?php

namespace App\Middlewares;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Exception;
use App\Services\Admin\AdminAuthService;

class AuthMiddleware
{
  private $service;
  private $permission;
  private string $role;

  public function __construct($admin = 'client', $permission = null) {
    $this->service = $admin == 'admin' ? new AdminAuthService() : null;
    $this->permission = $permission;
    $this->role = (string) $admin;
  }

  public static function admin(string $permission): self
  {
    return new self('admin', $permission);
  }

  public static function client(?string $permission = null): self
  {
    return new self('client', $permission);
  }

  public static function hasRole(string $role, ?string $permission = null): self
  {
    return new self($role, $permission);
  }

  public function getPermission(): ?string
  {
    return $this->permission;
  }

  public function getRole(): string
  {
    return $this->role;
  }

  public function __invoke(Request $request, RequestHandler $handler): Response
  {
    $user_id = (int) $request->getAttribute('user_id');

    try {
      if (isset($_ENV['ENVIRONMENT']) && $_ENV['ENVIRONMENT'] === 'TESTING') {
        $users = [
          'id' => 1,
          'name' => 'Testing User',
          'role' => json_encode(['article:view', 'article:crud'])
        ];
      } else {
        //* IS AUTHENTICATED & ACTIVE
        $users = $this->service->auth(['user_id' => $user_id]);

        //* IS PERMISSION
        if ($this->permission) {
          $userPermissions = json_decode($users['role'], true) ?? [];
          if (!in_array($this->permission, $userPermissions)) {
            throw new Exception('Unauthorized', 401);
          }
        }
      }

      $request = $request->withAttribute('user', (object) $users);
      return $handler->handle($request);
    }
    catch (Exception $e) {
      $code = $e->getCode();
      $status = (is_int($code) && $code >= 100 && $code <= 599) ? $code : 500;
      
      $result = [
        'message' => $e->getMessage(),
        'status'  => $status
      ];

      $response = new Response();
      $response->getBody()->write(json_encode($result));
      return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus($result['status']);
    }
  }
}
