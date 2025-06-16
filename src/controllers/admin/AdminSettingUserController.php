<?php

namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use App\Helpers\General;
use App\Services\Admin\AdminSettingUserService;
use App\Validators\Admin\AdminSettingUserValidator;

class AdminSettingUserController
{
  private $helper;
  private $service;
  private $validator;

  public function __construct()
  {
    $this->helper = new General;
    $this->service = new AdminSettingUserService;
    $this->validator = new AdminSettingUserValidator;
  }

  public function list(Request $request, Response $response)
  {
    try {
      //* SERVICES
      $data = $this->service->list();

      $result = [
        'data'    => $data,
        'message' => (!empty($data)) ? 'Ok' : 'Data masih kosong',
        'status'  => 200 //! OK
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
      ->withStatus($result['status']);
  }

  public function add(Request $request, Response $response)
  {
    $input = $request->getParsedBody();
    $user  = [
      'user'       => $request->getAttribute('user'),
      'user_agent' => $request->getHeaderLine('User-Agent'),
      'ip_address' => $this->helper->getClientIp($request),
    ];
    try {
      //* VALIDATION
      $this->validator->validate('add', $input);
      //* SERVICES
      $this->service->add($input, $user);

      $result = [
        'message' => 'Ok',
        'status'  => 200 //! OK
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
      ->withStatus($result['status']);
  }

  public function edit(Request $request, Response $response)
  {
    $input = $request->getParsedBody();
    $user  = [
      'user'       => $request->getAttribute('user'),
      'user_agent' => $request->getHeaderLine('User-Agent'),
      'ip_address' => $this->helper->getClientIp($request),
    ];
    try {
      //* VALIDATION
      $this->validator->validate('edit', $input);
      //* SERVICES
      $this->service->edit($input, $user);

      $result = [
        'message' => 'Ok',
        'status'  => 200 //! OK
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
      ->withStatus($result['status']);
  }

  public function pass(Request $request, Response $response)
  {
    $input = $request->getParsedBody();
    $user  = [
      'user'       => $request->getAttribute('user'),
      'user_agent' => $request->getHeaderLine('User-Agent'),
      'ip_address' => $this->helper->getClientIp($request),
    ];
    try {
      //* VALIDATION
      $this->validator->validate('pass', $input);
      //* SERVICES
      $this->service->pass($input, $user);

      $result = [
        'message' => 'Ok',
        'status'  => 200 //! OK
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
      ->withStatus($result['status']);
  }

  public function drop(Request $request, Response $response)
  {
    $input = $request->getParsedBody();
    $user  = [
      'user'       => $request->getAttribute('user'),
      'user_agent' => $request->getHeaderLine('User-Agent'),
      'ip_address' => $this->helper->getClientIp($request),
    ];
    try {
      //* VALIDATION
      $this->validator->validate('drop', $input);
      //* SERVICES
      $this->service->drop($input, $user);

      $result = [
        'message' => 'Ok',
        'status'  => 200 //! OK
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
      ->withStatus($result['status']);
  }

  public function roles(Request $request, Response $response)
  {
    try {
      //* SERVICES
      $data = $this->service->roles();

      $result = [
        'data'    => $data,
        'message' => (!empty($data)) ? 'Ok' : 'Data masih kosong',
        'status'  => 200 //! OK
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
      ->withStatus($result['status']);
  }

  public function addRoles(Request $request, Response $response)
  {
    $input = $request->getParsedBody();
    $user  = [
      'user'       => $request->getAttribute('user'),
      'user_agent' => $request->getHeaderLine('User-Agent'),
      'ip_address' => $this->helper->getClientIp($request),
    ];
    try {
      //* VALIDATION
      $this->validator->validate('roles_add', $input);
      //* SERVICES
      $this->service->addRoles($input, $user);

      $result = [
        'message' => 'Ok',
        'status'  => 200 //! OK
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
      ->withStatus($result['status']);
  }

  public function editRoles(Request $request, Response $response)
  {
    $input = $request->getParsedBody();
    $user  = [
      'user'       => $request->getAttribute('user'),
      'user_agent' => $request->getHeaderLine('User-Agent'),
      'ip_address' => $this->helper->getClientIp($request),
    ];
    try {
      //* VALIDATION
      $this->validator->validate('roles_edit', $input);
      //* SERVICES
      $this->service->editRoles($input, $user);

      $result = [
        'message' => 'Ok',
        'status'  => 200 //! OK
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
      ->withStatus($result['status']);
  }

  public function dropRoles(Request $request, Response $response)
  {
    $input = $request->getParsedBody();
    $user  = [
      'user'       => $request->getAttribute('user'),
      'user_agent' => $request->getHeaderLine('User-Agent'),
      'ip_address' => $this->helper->getClientIp($request),
    ];
    try {
      //* VALIDATION
      $this->validator->validate('roles_drop', $input);
      //* SERVICES
      $this->service->dropRoles($input, $user);

      $result = [
        'message' => 'Ok',
        'status'  => 200 //! OK
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
      ->withStatus($result['status']);
  }
}