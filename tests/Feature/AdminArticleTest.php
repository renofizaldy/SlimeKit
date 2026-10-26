<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Admin\AdminArticleService;
use Exception;

class AdminArticleTest extends TestCase
{
  //* =================== 1. LIST ===================
  public function test_list_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/admin/article/list');
    
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

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('list')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/article/list')->withQueryParams(['status' => 'active', 'featured' => 1, 'category' => 1]);
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

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('list')->willReturn([
      'items' => [
        ['id' => 1, 'title' => 'Article 1'],
      ],
      'pagination' => [
        'page'       => 1,
        'pageSize'   => 10,
        'total'      => 1,
        'totalPages' => 1,
      ],
    ]);
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/article/list')->withQueryParams(['status' => 'active', 'featured' => 1, 'category' => 1]);
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

    $mockService = $this->createMock(AdminArticleService::class);
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
          'total'      => 12,
          'totalPages' => 3,
        ],
      ]);
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/article/list')->withQueryParams([
      'status'   => 'all',
      'featured' => 'all',
      'category' => 'all',
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
    $request = $this->createRequest('GET', '/admin/article/list')->withQueryParams([
      'status'   => 'all',
      'featured' => 'all',
      'category' => 'all',
      'page'     => -1,
    ]);

    $response = $app->handle($request);

    $this->assertEquals(422, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
    $this->assertEquals('VALIDATION_ERROR', $body['code']);
  }

  public function test_list_success_with_sorting()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('list')->willReturn([
      'items' => [
        ['id' => 1, 'title' => 'Article 1'],
      ],
      'pagination' => [
        'page'       => 1,
        'pageSize'   => 10,
        'total'      => 1,
        'totalPages' => 1,
      ],
    ]);
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/article/list')->withQueryParams([
      'status'   => 'all',
      'featured' => 'all',
      'category' => 'all',
      'page'     => 1,
      'pageSize' => 10,
      'orderBy'  => 'publish',
      'orderDir' => 'desc',
    ]);

    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('success', $body['status']);
    $this->assertArrayHasKey('items', $body['data']);
  }

  public function test_list_fails_invalid_sorting()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/admin/article/list')->withQueryParams([
      'status'   => 'all',
      'featured' => 'all',
      'category' => 'all',
      'orderBy'  => 'malicious_column; DROP TABLE articles;',
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
    $request = $this->createRequest('GET', '/admin/article/detail');
    
    $response = $app->handle($request);
    
    $this->assertEquals(422, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
  }

  public function test_detail_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('detail')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/article/detail')->withQueryParams(['id' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_detail_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('detail')->willReturn(['id' => 1]);
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/article/detail')->withQueryParams(['id' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 3. ADD ===================
  public function test_add_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/article/add');
    $request = $request->withParsedBody([]);
    
    $response = $app->handle($request);
    
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_add_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('add')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article/add');
    $request = $request->withParsedBody(['title' => 'Test', 'slug' => 'test', 'status' => 'draft']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_add_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('add')->willReturn(true);
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article/add');
    $request = $request->withParsedBody(['title' => 'Test', 'slug' => 'test', 'status' => 'draft']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 4. EDIT ===================
  public function test_edit_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/article/edit');
    $request = $request->withParsedBody([]);
    
    $response = $app->handle($request);
    
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_edit_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('edit')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article/edit');
    $request = $request->withParsedBody(['id' => 1, 'title' => 'Test', 'slug' => 'test', 'status' => 'draft']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_edit_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('edit')->willReturn(true);
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article/edit');
    $request = $request->withParsedBody(['id' => 1, 'title' => 'Test', 'slug' => 'test', 'status' => 'draft']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 5. DROP ===================
  public function test_drop_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/article/drop');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_drop_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('drop')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article/drop');
    $request = $request->withParsedBody(['id' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_drop_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('drop')->willReturn(true);
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article/drop');
    $request = $request->withParsedBody(['id' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 6. STATUS CHANGE ===================
  public function test_status_change_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/article/status_change');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_status_change_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('statusChange')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article/status_change');
    $request = $request->withParsedBody(['id' => 1, 'status' => 'active']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_status_change_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('statusChange')->willReturn(true);
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article/status_change');
    $request = $request->withParsedBody(['id' => 1, 'status' => 'active']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 7. PUBLISH CHANGE ===================
  public function test_publish_change_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/article/publish_change');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_publish_change_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('publishChange')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article/publish_change');
    $request = $request->withParsedBody(['id' => 1, 'publish' => 'yes']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_publish_change_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('publishChange')->willReturn(true);
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article/publish_change');
    $request = $request->withParsedBody(['id' => 1, 'publish' => 'yes']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 8. CHECK SLUG ===================
  public function test_check_slug_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/article/check_slug');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_check_slug_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('checkSlug')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article/check_slug');
    $request = $request->withParsedBody(['slug' => 'test-slug']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_check_slug_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('checkSlug')->willReturn(true);
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article/check_slug');
    $request = $request->withParsedBody(['slug' => 'test-slug']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 9. ADD PICTURE ===================
  public function test_add_picture_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/article/add_picture');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_add_picture_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('addPicture')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article/add_picture');
    $request = $request->withParsedBody(['picture' => 'base64str']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_add_picture_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleService::class);
    $mockService->method('addPicture')->willReturn(true);
    $container->set(AdminArticleService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article/add_picture');
    $request = $request->withParsedBody(['picture' => 'base64str']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }
}
