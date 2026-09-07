<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Client\ClientArticleService;
use Exception;

class ClientArticleTest extends TestCase
{
  //* =================== 1. LIST ===================
  public function test_list_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/client/article/list');
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_list_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(ClientArticleService::class);
    $mockService->method('list')->willThrowException(new Exception('Service Error', 500));
    $container->set(ClientArticleService::class, $mockService);

    $request = $this->createRequest('GET', '/client/article/list');
    $request = $request->withQueryParams(['site' => 'site1']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_list_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(ClientArticleService::class);
    $mockService->method('list')->willReturn([
      'items' => [
        ['slug' => 'test-article', 'title' => 'Test Article', 'site' => 'site1'],
      ],
      'pagination' => [
        'page'       => 1,
        'pageSize'   => 10,
        'total'      => 1,
        'totalPages' => 1,
      ],
    ]);
    $container->set(ClientArticleService::class, $mockService);

    $request = $this->createRequest('GET', '/client/article/list');
    $request = $request->withQueryParams(['site' => 'site1']);
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

    $mockService = $this->createMock(ClientArticleService::class);
    $mockService->expects($this->once())
      ->method('list')
      ->with($this->callback(function ($input) {
        return $input['site'] === 'site1' && $input['page'] === '2' && $input['pageSize'] === '5';
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
    $container->set(ClientArticleService::class, $mockService);

    $request = $this->createRequest('GET', '/client/article/list')->withQueryParams([
      'site'     => 'site1',
      'page'     => '2',
      'pageSize' => '5',
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals(2, $body['data']['pagination']['page']);
  }

  //* =================== 2. SIMILAR ===================
  public function test_similar_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/client/article/similar');
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_similar_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(ClientArticleService::class);
    $mockService->method('similar')->willThrowException(new Exception('Service Error', 500));
    $container->set(ClientArticleService::class, $mockService);

    $request = $this->createRequest('GET', '/client/article/similar');
    $request = $request->withQueryParams(['site' => 'site1', 'slug' => 'test-article']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_similar_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(ClientArticleService::class);
    $mockService->method('similar')->willReturn(['data' => []]);
    $container->set(ClientArticleService::class, $mockService);

    $request = $this->createRequest('GET', '/client/article/similar');
    $request = $request->withQueryParams(['site' => 'site1', 'slug' => 'test-article']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 3. DETAIL ===================
  public function test_detail_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/client/article/detail');
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_detail_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(ClientArticleService::class);
    $mockService->method('detail')->willThrowException(new Exception('Service Error', 500));
    $container->set(ClientArticleService::class, $mockService);

    $request = $this->createRequest('GET', '/client/article/detail');
    $request = $request->withQueryParams(['site' => 'site1', 'slug' => 'test-article']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_detail_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(ClientArticleService::class);
    $mockService->method('detail')->willReturn(['slug' => 'test-article']);
    $container->set(ClientArticleService::class, $mockService);

    $request = $this->createRequest('GET', '/client/article/detail');
    $request = $request->withQueryParams(['site' => 'site1', 'slug' => 'test-article']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }
}
