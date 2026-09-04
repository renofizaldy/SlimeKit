<?php

namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use App\Helpers\General;
use App\Helpers\ApiResponse;
use App\Services\Admin\AdminArticleCategoryService;
use App\Validators\Admin\AdminArticleCategoryValidator;

class AdminArticleCategoryController
{
  public function __construct(
    private General $helper,
    private AdminArticleCategoryService $service,
    private AdminArticleCategoryValidator $validator,
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

  public function detail(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getQueryParams();

      //* VALIDATOR
      $this->validator->validate('detail', $input);

      //* SERVICE
      $data = $this->service->detail($input);

      return $this->apiResponse->success($response, $data);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function add(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getParsedBody();

      //* VALIDATOR
      $this->validator->validate('add', $input);

      //* SERVICE
      $data = $this->service->add($input, $this->helper->getUserContext($request));

      return $this->apiResponse->success($response, $data);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function edit(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getParsedBody();

      //* VALIDATOR
      $this->validator->validate('edit', $input);

      //* SERVICE
      $data = $this->service->edit($input, $this->helper->getUserContext($request));

      return $this->apiResponse->success($response, $data);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function drop(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getParsedBody();

      //* VALIDATOR
      $this->validator->validate('drop', $input);

      //* SERVICE
      $data = $this->service->drop($input, $this->helper->getUserContext($request));

      return $this->apiResponse->success($response, $data);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function checkSlug(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getParsedBody();

      //* VALIDATOR
      $this->validator->validate('check_slug', $input);

      //* SERVICE
      $data = $this->service->checkSlug($input, $this->helper->getUserContext($request));

      return $this->apiResponse->success($response, $data);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }
}