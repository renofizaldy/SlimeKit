<?php

namespace App\Middlewares;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Exception;
use App\Helpers\General;
use App\Services\Admin\AdminAuthService;

class AuthMiddleware
{
  private $helper;
  private $service;
  private $permission;

  public function __construct($admin = 'client', $permission = null) {
    $this->helper = new General;
    $this->service = $admin == 'admin' ? new AdminAuthService() : null;
    $this->permission = $permission;
  }

  public function __invoke(Request $request, RequestHandler $handler): Response
  {
    $user_id = (int) $request->getAttribute('user_id');

    try {
      //* IS AUTHENTICATED & ACTIVE
      $users = $this->service->auth(['user_id' => $user_id]);

      //* IS PERMISSION
      if ($this->permission) {
        $userPermissions = json_decode($users['role'], true) ?? [];
        if (!in_array($this->permission, $userPermissions)) {
          throw new Exception('Unauthorized', 401);
        }
      }

      $request = $request->withAttribute('user', (object) $users);
      return $handler->handle($request);
    }
    catch (Exception $e) {
      $result = [
        'message' => $e->getMessage(),
        'status'  => $this->helper->normalizeHttpStatus($e->getCode())
      ];

      $response = new Response();
      $response->getBody()->write(json_encode($result));
      return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus($result['status']);
    }
  }
}
