<?php

namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use App\Helpers\General;
use App\Services\Admin\AdminArticleCategoryService;
use App\Validators\Admin\AdminArticleCategoryValidator;

class AdminArticleCategoryController
{
  private $helper;
  private $service;
  private $validator;

  public function __construct()
  {
    $this->helper = new General;
    $this->service = new AdminArticleCategoryService;
    $this->validator = new AdminArticleCategoryValidator;
  }

  public function list(Request $request, Response $response)
  {
    $input = $request->getQueryParams();
    try {
      //* VALIDATION
      $this->validator->validate('list', $input);
      //* SERVICES
      $data = $this->service->list($input);

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

    $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
    return $response
      ->withHeader('Content-type', 'application/json')
      ->withStatus($result['status']);
  }

  public function detail(Request $request, Response $response)
  {
    $input = $request->getQueryParams();
    try {
      //* VALIDATION
      $this->validator->validate('detail', $input);
      //* SERVICES
      $data = $this->service->detail($input);

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

    $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
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
      $data = $this->service->add($input, $user);

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

    $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
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
      $data = $this->service->edit($input, $user);

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

    $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
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
      $data = $this->service->drop($input, $user);

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

    $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
    return $response
      ->withHeader('Content-type', 'application/json')
      ->withStatus($result['status']);
  }

  public function checkSlug(Request $request, Response $response)
  {
    $input = $request->getParsedBody();
    $user  = [
      'user'       => $request->getAttribute('user'),
      'user_agent' => $request->getHeaderLine('User-Agent'),
      'ip_address' => $this->helper->getClientIp($request),
    ];
    try {
      //* VALIDATION
      $this->validator->validate('check_slug', $input);
      //* SERVICES
      $data = $this->service->checkSlug($input, $user);

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

    $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
    return $response
      ->withHeader('Content-type', 'application/json')
      ->withStatus($result['status']);
  }
}