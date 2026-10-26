<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Admin\AdminArticleCategoryService;
use Exception;

class AdminArticleCategoryTest extends TestCase
{
  //* =================== 1. LIST ===================
  public function test_list_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/admin/article_category/list');
    
    $response = $app->handle($request);
    
    $this->assertEquals(422, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
    $this->assertEquals('Validasi gagal', $body['message']);
  }

  public function test_list_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleCategoryService::class);
    $mockService->method('list')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleCategoryService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/article_category/list')->withQueryParams(['status' => 'active']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
    $this->assertEquals('Service Error', $body['message']);
  }

  public function test_list_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleCategoryService::class);
    $mockService->method('list')->willReturn([
      'items' => [
        ['id' => 1, 'title' => 'Category 1'],
      ],
      'pagination' => [
        'page'       => 1,
        'pageSize'   => 10,
        'total'      => 1,
        'totalPages' => 1,
      ],
    ]);
    $container->set(AdminArticleCategoryService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/article_category/list')->withQueryParams(['status' => 'active']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('Ok', $body['message']);
    $this->assertArrayHasKey('items', $body['data']);
    $this->assertArrayHasKey('pagination', $body['data']);
    $this->assertEquals(1, $body['data']['pagination']['page']);
    $this->assertEquals(10, $body['data']['pagination']['pageSize']);
  }

  public function test_list_with_pagination_params_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleCategoryService::class);
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
    $container->set(AdminArticleCategoryService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/article_category/list')->withQueryParams([
      'status'   => 'all',
      'page'     => '2',
      'pageSize' => '5',
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('Ok', $body['message']);
    $this->assertEquals(2, $body['data']['pagination']['page']);
    $this->assertEquals(5, $body['data']['pagination']['pageSize']);
  }

  public function test_list_fails_invalid_pagination()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/admin/article_category/list')->withQueryParams([
      'status'   => 'all',
      'page'     => 0,
    ]);

    $response = $app->handle($request);

    $this->assertEquals(422, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
    $this->assertEquals('VALIDATION_ERROR', $body['code']);
  }

  //* =================== 2. DETAIL ===================
  public function test_detail_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/admin/article_category/detail');
    
    $response = $app->handle($request);
    
    $this->assertEquals(422, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
  }

  public function test_detail_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleCategoryService::class);
    $mockService->method('detail')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleCategoryService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/article_category/detail')->withQueryParams(['id' => 1, 'field' => 'id']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_detail_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleCategoryService::class);
    $mockService->method('detail')->willReturn(['id' => 1]);
    $container->set(AdminArticleCategoryService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/article_category/detail')->withQueryParams(['id' => 1, 'field' => 'id']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 3. ADD ===================
  public function test_add_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/article_category/add');
    $request = $request->withParsedBody([]);
    
    $response = $app->handle($request);
    
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_add_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleCategoryService::class);
    $mockService->method('add')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleCategoryService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_category/add');
    $request = $request->withParsedBody([
      'slug' => 'test-category',
      'title' => 'Test Category',
      'description' => 'Test Description',
      'meta_title' => 'Test Meta',
      'meta_description' => 'Test Meta Desc',
      'meta_robots' => 'index,follow',
      'status' => 'active'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_add_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleCategoryService::class);
    $mockService->method('add')->willReturn(true);
    $container->set(AdminArticleCategoryService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_category/add');
    $request = $request->withParsedBody([
      'slug' => 'test-category',
      'title' => 'Test Category',
      'description' => 'Test Description',
      'meta_title' => 'Test Meta',
      'meta_description' => 'Test Meta Desc',
      'meta_robots' => 'index,follow',
      'status' => 'active'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 4. EDIT ===================
  public function test_edit_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/article_category/edit');
    $request = $request->withParsedBody([]);
    
    $response = $app->handle($request);
    
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_edit_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleCategoryService::class);
    $mockService->method('edit')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleCategoryService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_category/edit');
    $request = $request->withParsedBody([
      'id' => 1,
      'title' => 'Test Category',
      'slug' => 'test-category',
      'description' => 'Test Description',
      'meta_title' => 'Test Meta',
      'meta_description' => 'Test Meta Desc',
      'meta_robots' => 'index,follow',
      'status' => 'active'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_edit_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleCategoryService::class);
    $mockService->method('edit')->willReturn(true);
    $container->set(AdminArticleCategoryService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_category/edit');
    $request = $request->withParsedBody([
      'id' => 1,
      'title' => 'Test Category',
      'slug' => 'test-category',
      'description' => 'Test Description',
      'meta_title' => 'Test Meta',
      'meta_description' => 'Test Meta Desc',
      'meta_robots' => 'index,follow',
      'status' => 'active'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 5. DROP ===================
  public function test_drop_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/article_category/drop');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_drop_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleCategoryService::class);
    $mockService->method('drop')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleCategoryService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_category/drop');
    $request = $request->withParsedBody(['id' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_drop_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleCategoryService::class);
    $mockService->method('drop')->willReturn(true);
    $container->set(AdminArticleCategoryService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_category/drop');
    $request = $request->withParsedBody(['id' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 6. CHECK SLUG ===================
  public function test_check_slug_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/article_category/check_slug');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_check_slug_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleCategoryService::class);
    $mockService->method('checkSlug')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleCategoryService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_category/check_slug');
    $request = $request->withParsedBody(['slug' => 'test-slug']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_check_slug_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleCategoryService::class);
    $mockService->method('checkSlug')->willReturn(true);
    $container->set(AdminArticleCategoryService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_category/check_slug');
    $request = $request->withParsedBody(['slug' => 'test-slug']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }
}
