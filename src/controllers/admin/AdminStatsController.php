<?php

namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use App\Helpers\General;
use App\Helpers\ApiResponse;
use App\Services\Admin\AdminStatsService;
use App\Validators\Admin\AdminStatsValidator;

class AdminStatsController
{
  public function __construct(
    private General $helper,
    private AdminStatsService $service,
    private AdminStatsValidator $validator,
    private ApiResponse $apiResponse
  ) {}

  public function listLog(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getQueryParams();

      //* VALIDATOR
      $this->validator->validate('list_log', $input);

      //* SERVICE
      $data = $this->service->listLog($input);

      return $this->apiResponse->success($response, $data);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function dropLog(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getParsedBody();

      //* VALIDATOR
      $this->validator->validate('drop_log', $input);

      //* SERVICE
      $data = $this->service->dropLog($input);

      return $this->apiResponse->success($response, $data);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function articleCount(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getQueryParams();

      //* VALIDATOR
      $this->validator->validate('total_article', $input);

      //* SERVICE
      $data = $this->service->totalArticle($input);

      return $this->apiResponse->success($response, $data);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function listCronArticle(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getQueryParams();

      //* VALIDATOR
      $this->validator->validate('list_cron', $input);

      //* SERVICE
      $data = $this->service->listCronArticle($input);

      return $this->apiResponse->success($response, $data);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function listArticleVisits(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getQueryParams();

      //* VALIDATOR
      $this->validator->validate('list_visits', $input);

      //* SERVICE
      $data = $this->service->listArticleVisits($input);

      return $this->apiResponse->success($response, $data);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function listArticleVisitsBySlug(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getQueryParams();

      //* VALIDATOR
      $this->validator->validate('list_visits_slug', $input);

      //* SERVICE
      $data = $this->service->listArticleVisitsBySlug($input);

      return $this->apiResponse->success($response, $data);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }
}