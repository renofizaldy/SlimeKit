<?php

namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use App\Helpers\General;
use App\Services\Admin\AdminStatsService;
use App\Validators\Admin\AdminStatsValidator;

class AdminStatsController
{
  private $helper;
  private $service;
  private $validator;

  public function __construct()
  {
    $this->helper = new General;
    $this->service = new AdminStatsService;
    $this->validator = new AdminStatsValidator;
  }

  public function listLog(Request $request, Response $response)
  {
    $input = $request->getQueryParams();
    try {
      //* VALIDATION
      $this->validator->validate('listLog', $input);
      //* SERVICES
      $data = $this->service->listLog($input);

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

  public function dropLog(Request $request, Response $response)
  {
    $input = $request->getParsedBody();
    try {
      //* VALIDATION
      $this->validator->validate('dropLog', $input);
      //* SERVICES
      $data = $this->service->dropLog($input);

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

  public function listCronArticle(Request $request, Response $response)
  {
    $input = $request->getQueryParams();
    try {
      //* SERVICES
      $data = $this->service->listCronArticle($input);

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