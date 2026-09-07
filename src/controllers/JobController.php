<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use App\Helpers\General;
use App\Lib\Valkey;
use App\Helpers\ApiResponse;
use App\Validators\JobValidator;
use App\Services\JobService;

use App\Models\ArticleStats;

class JobController
{
  public function __construct(
    private General $helper,
    private Valkey $valkey,
    private JobValidator $validator,
    private JobService $service,
    private ApiResponse $apiResponse
  ) {}

  public function articlePublish(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getParsedBody();
      $user  = $this->helper->getUserContext($request);

      //* VALIDATOR
      $this->validator->validate('article_publish', $input);

      //* SERVICE
      $data = $this->service->articlePublish($input, $user);

      return $this->apiResponse->success($response, $data);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function articleSyncStats(Request $request, Response $response)
  {
    try {
      //* SERVICE
      $data   = $this->service->articleSyncStats();
      return $this->apiResponse->success($response, $data);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function articleTrackStats(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getParsedBody();
      $user  = $this->helper->getUserContext($request);

      //* VALIDATOR
      $this->validator->validate('article_track_stats', $input);

      //* SERVICE
      $data = $this->service->articleTrackStats($input, $user);

      return $this->apiResponse->success($response, $data);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }
}
