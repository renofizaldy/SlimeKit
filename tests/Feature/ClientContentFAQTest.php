<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Client\ClientContentFAQService;
use Exception;

class ClientContentFAQTest extends TestCase
{
  //* =================== 1. LIST ===================
  public function test_list_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/client/content_faq/list');
    $request = $request->withQueryParams(['limit' => 'abc']);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_list_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(ClientContentFAQService::class);
    $mockService->method('list')->willThrowException(new Exception('Service Error', 500));
    $container->set(ClientContentFAQService::class, $mockService);

    $request = $this->createRequest('GET', '/client/content_faq/list');
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_list_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(ClientContentFAQService::class);
    $mockService->method('list')->willReturn([
      'items' => [
        ['title' => 'FAQ 1', 'description' => 'Desc 1'],
      ],
      'pagination' => [
        'page'       => 1,
        'pageSize'   => 10,
        'total'      => 1,
        'totalPages' => 1,
      ],
    ]);
    $container->set(ClientContentFAQService::class, $mockService);

    $request = $this->createRequest('GET', '/client/content_faq/list');
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('Ok', $body['message']);
    $this->assertArrayHasKey('items', $body['data']);
    $this->assertArrayHasKey('pagination', $body['data']);
  }

  public function test_list_with_pagination_params_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(ClientContentFAQService::class);
    $mockService->expects($this->once())
      ->method('list')
      ->with($this->callback(function ($input) {
        return $input['page'] === '2' && $input['pageSize'] === '5';
      }))
      ->willReturn([
        'items' => [],
        'pagination' => [
          'page'       => 2,
          'pageSize'   => 5,
          'total'      => 8,
          'totalPages' => 2,
        ],
      ]);
    $container->set(ClientContentFAQService::class, $mockService);

    $request = $this->createRequest('GET', '/client/content_faq/list')->withQueryParams([
      'page'     => '2',
      'pageSize' => '5',
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals(2, $body['data']['pagination']['page']);
  }
}
