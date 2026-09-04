<?php

namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use App\Helpers\General;
use App\Helpers\ApiResponse;
use App\Services\Admin\AdminSettingUserService;
use App\Validators\Admin\AdminSettingUserValidator;

class AdminSettingUserController
{
  public function __construct(
    private General $helper,
    private AdminSettingUserService $service,
    private AdminSettingUserValidator $validator,
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

      return $this->apiResponse->success($response, $data, (!empty($data['items'] ?? $data)) ? 'Ok' : 'Data masih kosong');
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
      $this->service->add($input, $this->helper->getUserContext($request));

      return $this->apiResponse->success($response, []);
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
      $this->service->edit($input, $this->helper->getUserContext($request));

      return $this->apiResponse->success($response, []);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function pass(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getParsedBody();

      //* VALIDATOR
      $this->validator->validate('pass', $input);

      //* SERVICE
      $this->service->pass($input, $this->helper->getUserContext($request));

      return $this->apiResponse->success($response, []);
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
      $this->service->drop($input, $this->helper->getUserContext($request));

      return $this->apiResponse->success($response, []);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function roles(Request $request, Response $response)
  {
    try {
      //* SERVICE
      $data = $this->service->roles();

      return $this->apiResponse->success($response, $data, (!empty($data)) ? 'Ok' : 'Data masih kosong');
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function addRoles(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getParsedBody();

      //* VALIDATOR
      $this->validator->validate('roles_add', $input);

      //* SERVICE
      $this->service->addRoles($input, $this->helper->getUserContext($request));

      return $this->apiResponse->success($response, []);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function editRoles(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getParsedBody();

      //* VALIDATOR
      $this->validator->validate('roles_edit', $input);

      //* SERVICE
      $this->service->editRoles($input, $this->helper->getUserContext($request));

      return $this->apiResponse->success($response, []);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }

  public function dropRoles(Request $request, Response $response)
  {
    try {
      //* INPUT
      $input = (array) $request->getParsedBody();

      //* VALIDATOR
      $this->validator->validate('roles_drop', $input);

      //* SERVICE
      $this->service->dropRoles($input, $this->helper->getUserContext($request));

      return $this->apiResponse->success($response, []);
    }
    catch (Exception $e) {
      return $this->apiResponse->handleException($response, $e);
    }
  }
}