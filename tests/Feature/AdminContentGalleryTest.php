<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Admin\AdminContentGalleryService;
use Exception;

class AdminContentGalleryTest extends TestCase
{
  //* =================== 1. LIST ===================

  public function test_list_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminContentGalleryService::class);
    $mockService->method('list')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminContentGalleryService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/content_gallery/list');
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

    $mockService = $this->createMock(AdminContentGalleryService::class);
    $mockService->method('list')->willReturn([
      'items' => [
        ['id' => 1, 'title' => 'Gallery 1', 'description' => 'Desc 1'],
      ],
      'pagination' => [
        'page'       => 1,
        'pageSize'   => 10,
        'total'      => 1,
        'totalPages' => 1,
      ],
    ]);
    $container->set(AdminContentGalleryService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/content_gallery/list');
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

    $mockService = $this->createMock(AdminContentGalleryService::class);
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
    $container->set(AdminContentGalleryService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/content_gallery/list')->withQueryParams([
      'page'     => '2',
      'pageSize' => '5',
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals(2, $body['data']['pagination']['page']);
  }

  //* =================== 2. ADD ===================
  public function test_add_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/content_gallery/add');
    $request = $request->withParsedBody([]);
    
    $response = $app->handle($request);
    
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_add_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminContentGalleryService::class);
    $mockService->method('add')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminContentGalleryService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/content_gallery/add');
    $request = $request->withParsedBody(['title' => 'Image 1', 'picture' => 'base64str', 'description' => 'Desc 1']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_add_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminContentGalleryService::class);
    $mockService->method('add')->willReturn(true);
    $container->set(AdminContentGalleryService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/content_gallery/add');
    $request = $request->withParsedBody(['title' => 'Image 1', 'picture' => 'base64str', 'description' => 'Desc 1']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 3. EDIT ===================
  public function test_edit_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/content_gallery/edit');
    $request = $request->withParsedBody([]);
    
    $response = $app->handle($request);
    
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_edit_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminContentGalleryService::class);
    $mockService->method('edit')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminContentGalleryService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/content_gallery/edit');
    $request = $request->withParsedBody(['id' => 1, 'title' => 'Image 1', 'picture' => 'base64str', 'description' => 'Desc 1']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_edit_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminContentGalleryService::class);
    $mockService->method('edit')->willReturn(true);
    $container->set(AdminContentGalleryService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/content_gallery/edit');
    $request = $request->withParsedBody(['id' => 1, 'title' => 'Image 1', 'picture' => 'base64str', 'description' => 'Desc 1']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 4. DROP ===================
  public function test_drop_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/content_gallery/drop');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_drop_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminContentGalleryService::class);
    $mockService->method('drop')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminContentGalleryService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/content_gallery/drop');
    $request = $request->withParsedBody(['id' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_drop_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminContentGalleryService::class);
    $mockService->method('drop')->willReturn(true);
    $container->set(AdminContentGalleryService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/content_gallery/drop');
    $request = $request->withParsedBody(['id' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }
}
