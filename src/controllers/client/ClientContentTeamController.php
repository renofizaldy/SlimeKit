<?php

namespace App\Controllers\Client;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use App\Helpers\General;
use App\Helpers\ApiResponse;
use App\Services\Client\ClientContentTeamService;
use App\Validators\Client\ClientContentTeamValidator;

class ClientContentTeamController
{
  public function __construct(
    private General $helper,
    private ClientContentTeamService $service,
    private ClientContentTeamValidator $validator,
    private ApiResponse $apiResponse
  ) {}

  public function list(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getQueryParams();

      //* VALIDATOR
      $this->validator->validate('list', $input);

      //* SERVICE
      $data = $this->service->list($input);

      return $this->apiResponse->success($response, $data);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }
}