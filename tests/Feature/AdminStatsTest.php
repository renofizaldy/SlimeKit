<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Admin\AdminStatsService;
use Exception;

class AdminStatsTest extends TestCase
{
  //* =================== 1. LIST LOG ===================
  public function test_listLog_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/admin/stats/log')->withQueryParams(['page' => 'invalid']);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_listLog_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminStatsService::class);
    $mockService->method('listLog')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminStatsService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/stats/log');
    $request = $request->withQueryParams(['limit' => 10]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_listLog_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminStatsService::class);
    $mockService->method('listLog')->willReturn([
      'items' => [
        ['title' => 'Article', 'user' => 'Admin', 'action' => 'create'],
      ],
      'pagination' => [
        'page'       => 1,
        'pageSize'   => 10,
        'total'      => 1,
        'totalPages' => 1,
      ],
    ]);
    $container->set(AdminStatsService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/stats/log');
    $request = $request->withQueryParams(['limit' => 10]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('Ok', $body['message']);
    $this->assertArrayHasKey('items', $body['data']);
    $this->assertArrayHasKey('pagination', $body['data']);
  }

  public function test_listLog_with_pagination_params_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminStatsService::class);
    $mockService->expects($this->once())
      ->method('listLog')
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
    $container->set(AdminStatsService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/stats/log')->withQueryParams([
      'page'     => '2',
      'pageSize' => '5',
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals(2, $body['data']['pagination']['page']);
  }

  //* =================== 2. DROP LOG ===================
  public function test_dropLog_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/stats/log_drop');
    $request = $request->withParsedBody([]);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_dropLog_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminStatsService::class);
    $mockService->method('dropLog')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminStatsService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/stats/log_drop');
    $request = $request->withParsedBody(['id' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_dropLog_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminStatsService::class);
    $mockService->method('dropLog')->willReturn(true);
    $container->set(AdminStatsService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/stats/log_drop');
    $request = $request->withParsedBody(['id' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 3. ARTICLE COUNT ===================
  public function test_articleCount_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/admin/stats/count_article');
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_articleCount_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminStatsService::class);
    $mockService->method('totalArticle')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminStatsService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/stats/count_article');
    $request = $request->withQueryParams([
      'status' => 'publish'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_articleCount_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminStatsService::class);
    $mockService->method('totalArticle')->willReturn(['total' => 10]);
    $container->set(AdminStatsService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/stats/count_article');
    $request = $request->withQueryParams([
      'status' => 'publish'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('Ok', $body['message']);
  }

  //* =================== 4. LIST CRON ARTICLE ===================
  public function test_listCronArticle_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/admin/stats/cron')->withQueryParams(['page' => 'invalid']);
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_listCronArticle_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminStatsService::class);
    $mockService->method('listCronArticle')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminStatsService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/stats/cron');
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_listCronArticle_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminStatsService::class);
    $mockService->method('listCronArticle')->willReturn([
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
    $container->set(AdminStatsService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/stats/cron');
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('Ok', $body['message']);
    $this->assertArrayHasKey('items', $body['data']);
    $this->assertArrayHasKey('pagination', $body['data']);
  }

  public function test_listCronArticle_with_pagination_params_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminStatsService::class);
    $mockService->expects($this->once())
      ->method('listCronArticle')
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
    $container->set(AdminStatsService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/stats/cron')->withQueryParams([
      'page'     => '2',
      'pageSize' => '5',
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals(2, $body['data']['pagination']['page']);
  }

  //* =================== 5. LIST ARTICLE VISITS ===================
  public function test_listArticleVisits_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/admin/stats/article_visits');
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_listArticleVisits_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminStatsService::class);
    $mockService->method('listArticleVisits')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminStatsService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/stats/article_visits');
    $request = $request->withQueryParams([
      'start' => '2023-01-01',
      'end'   => '2023-01-31'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_listArticleVisits_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminStatsService::class);
    $mockService->method('listArticleVisits')->willReturn(['data' => []]);
    $container->set(AdminStatsService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/stats/article_visits');
    $request = $request->withQueryParams([
      'start' => '2023-01-01',
      'end'   => '2023-01-31'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 6. LIST ARTICLE VISITS BY SLUG ===================
  public function test_listArticleVisitsBySlug_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/admin/stats/article_visits_by_slug');
    $response = $app->handle($request);
    $this->assertEquals(422, $response->getStatusCode());
  }

  public function test_listArticleVisitsBySlug_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminStatsService::class);
    $mockService->method('listArticleVisitsBySlug')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminStatsService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/stats/article_visits_by_slug');
    $request = $request->withQueryParams([
      'start' => '2023-01-01',
      'end'   => '2023-01-31'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_listArticleVisitsBySlug_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminStatsService::class);
    $mockService->method('listArticleVisitsBySlug')->willReturn(['data' => []]);
    $container->set(AdminStatsService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/stats/article_visits_by_slug');
    $request = $request->withQueryParams([
      'start' => '2023-01-01',
      'end'   => '2023-01-31'
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }
}
