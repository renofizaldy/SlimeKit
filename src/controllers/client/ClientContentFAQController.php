<?php

namespace App\Controllers\Client;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use App\Helpers\General;
use App\Services\Client\ClientContentFAQService;
use App\Validators\Client\ClientContentFAQValidator;

class ClientContentFAQController
{
  private $helper;
  private $service;
  private $validator;

  public function __construct()
  {
    $this->helper = new General;
    $this->service = new ClientContentFAQService;
    $this->validator = new ClientContentFAQValidator;
  }

  public function list(Request $request, Response $response)
  {
    $input = $request->getQueryParams();
    try {
      //* VALIDATOR
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
}