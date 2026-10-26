<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Client\ClientArticleCategoryService;
use Exception;

class ClientArticleCategoryTest extends TestCase
{
  //* =================== 1. LIST ===================
  public function test_list_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/client/article_category/list')->withQueryParams(['page' => 'invalid']);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_list_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(ClientArticleCategoryService::class);
    $mockService->method('list')->willThrowException(new Exception('Service Error', 500));
    $container->set(ClientArticleCategoryService::class, $mockService);

    $request = $this->createRequest('GET', '/client/article_category/list');
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_list_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(ClientArticleCategoryService::class);
    $mockService->method('list')->willReturn([
      'items' => [
        ['slug' => 'test-cat', 'title' => 'Test Cat'],
      ],
      'pagination' => [
        'page'       => 1,
        'pageSize'   => 10,
        'total'      => 1,
        'totalPages' => 1,
      ],
    ]);
    $container->set(ClientArticleCategoryService::class, $mockService);

    $request = $this->createRequest('GET', '/client/article_category/list');
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

    $mockService = $this->createMock(ClientArticleCategoryService::class);
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
    $container->set(ClientArticleCategoryService::class, $mockService);

    $request = $this->createRequest('GET', '/client/article_category/list')->withQueryParams([
      'page'     => '2',
      'pageSize' => '5',
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals(2, $body['data']['pagination']['page']);
  }

  //* =================== 2. DETAIL ===================
  public function test_detail_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/client/article_category/detail');
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_detail_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(ClientArticleCategoryService::class);
    $mockService->method('detail')->willThrowException(new Exception('Service Error', 500));
    $container->set(ClientArticleCategoryService::class, $mockService);

    $request = $this->createRequest('GET', '/client/article_category/detail');
    $request = $request->withQueryParams(['slug' => 'test-category']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_detail_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(ClientArticleCategoryService::class);
    $mockService->method('detail')->willReturn(['slug' => 'test-category']);
    $container->set(ClientArticleCategoryService::class, $mockService);

    $request = $this->createRequest('GET', '/client/article_category/detail');
    $request = $request->withQueryParams(['slug' => 'test-category']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }
}
