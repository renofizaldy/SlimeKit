<?php

namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use App\Lib\Tokenize;
use App\Helpers\ApiResponse;
use App\Services\Admin\AdminAuthService;
use App\Validators\Admin\AdminAuthValidator;

class AdminAuthController
{
  public function __construct(
    private ApiResponse $apiResponse,
    private AdminAuthService $service,
    private AdminAuthValidator $validator,
    private Tokenize $jwt
  ) {}

  public function verify(Request $request, Response $response)
  {
    return $this->apiResponse->success($response, [], 'Ok', 200);
  }

  public function login(Request $request, Response $response)
  {
    $input = $request->getParsedBody();
    if (!is_array($input)) {
      $input = [];
    }

    try {
      //* VALIDATION
      $this->validator->validate('login', $input);

      //* SERVICE
      $getData = $this->service->login($input);

      //* TOKEN
      $data = [
        'id'    => $getData['user']['id'],
        'nama'  => $getData['user']['name'],
        'email' => $getData['user']['email'],
        'role'  => $getData['user']['role'],
        'token' => $this->jwt->encode([
          'keep_login' => $input['keepLogin'] ?? false,
          'user_id'    => $getData['user']['id'],
          'user_role'  => $getData['user']['id_user_role']
        ])
      ];

      return $this->apiResponse->success($response, $data, 'Ok', 200);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }
}