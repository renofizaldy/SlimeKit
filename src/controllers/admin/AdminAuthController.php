<?php

namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use App\Lib\Tokenize;
use App\Helpers\General;
use App\Services\Admin\AdminAuthService;
use App\Validators\Admin\AdminAuthValidator;

class AdminAuthController
{
  private $helper;
  private $service;
  private $validator;
  private $jwt;

  public function __construct()
  {
    $this->helper = new General;
    $this->service = new AdminAuthService;
    $this->validator = new AdminAuthValidator;
    $this->jwt = new Tokenize;
  }

  public function verify(Request $request, Response $response)
  {
    $result = [
      'message' => 'Ok',
      'status'  => 200
    ];
    $response->getBody()->write(json_encode($result));
    return $response
      ->withHeader('Content-type', 'application/json')
      ->withStatus($result['status']);
  }

  public function login(Request $request, Response $response)
  {
    $input = $request->getParsedBody();
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

      $result = [
        'data'    => $data,
        'message' => 'Ok',
        'status'  => 200
      ];
    }
    catch (Exception $e) {
      $result = [
        'message' => 'Error: ' . $e->getMessage(),
        'status'  => $this->helper->normalizeHttpStatus($e->getCode())
      ];
    }

    $response->getBody()->write(json_encode($result));
    return $response
      ->withHeader('Content-type', 'application/json')
      ->withStatus(200);
  }
}