<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\JobService;
use Exception;

class JobTest extends TestCase
{
  public function test_article_publish_fails_validation_when_empty()
  {
    $app = $this->getAppInstance();

    $request = $this->createRequest('POST', '/job/article_publish');
    $request = $request->withParsedBody([]);

    $response = $app->handle($request);

    $this->assertEquals(422, $response->getStatusCode());

    $body = $this->parseJson($response);
    $this->assertArrayHasKey('status', $body);
    $this->assertEquals('error', $body['status']);
    $this->assertEquals('Validasi gagal', $body['message']);
    $this->assertArrayHasKey('errors', $body);
  }

  public function test_article_publish_fails_when_service_throws_exception()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(JobService::class);
    $mockService->method('articlePublish')
      ->willThrowException(new Exception('Article Not Found', 404));

    $container->set(JobService::class, $mockService);

    $request = $this->createRequest('POST', '/job/article_publish');
    $request = $request->withParsedBody([
      'site' => 'example.com',
      'slug' => 'my-article'
    ]);

    $response = $app->handle($request);

    $this->assertEquals(404, $response->getStatusCode());

    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
    $this->assertEquals('Article Not Found', $body['message']);
  }

  public function test_article_publish_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(JobService::class);
    $mockService->method('articlePublish')
      ->willReturn(true);

    $container->set(JobService::class, $mockService);

    $request = $this->createRequest('POST', '/job/article_publish');
    $request = $request->withParsedBody([
      'site' => 'example.com',
      'slug' => 'my-article'
    ]);

    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());

    $body = $this->parseJson($response);
    $this->assertEquals('Ok', $body['message']);
    $this->assertTrue($body['data']);
  }

  public function test_article_sync_stats_fails_when_service_throws_exception()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(JobService::class);
    $mockService->method('articleSyncStats')
      ->willThrowException(new Exception('Sync Failed', 500));

    $container->set(JobService::class, $mockService);

    $request = $this->createRequest('POST', '/job/article_sync_stats');

    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());

    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
    $this->assertEquals('Sync Failed', $body['message']);
  }

  public function test_article_sync_stats_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(JobService::class);
    $mockService->method('articleSyncStats')
      ->willReturn(['synced' => 5]);

    $container->set(JobService::class, $mockService);

    $request = $this->createRequest('POST', '/job/article_sync_stats');

    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());

    $body = $this->parseJson($response);
    $this->assertEquals('Ok', $body['message']);
    $this->assertArrayHasKey('synced', $body['data']);
  }

  public function test_article_track_stats_fails_validation_when_empty()
  {
    $app = $this->getAppInstance();

    $request = $this->createRequest('POST', '/job/article_track_stats');
    $request = $request->withParsedBody([]);

    $response = $app->handle($request);

    $this->assertEquals(422, $response->getStatusCode());

    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
    $this->assertEquals('Validasi gagal', $body['message']);
  }

  public function test_article_track_stats_fails_when_service_throws_exception()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(JobService::class);
    $mockService->method('articleTrackStats')
      ->willThrowException(new Exception('Tracker Error', 500));

    $container->set(JobService::class, $mockService);

    $request = $this->createRequest('POST', '/job/article_track_stats');
    $request = $request->withParsedBody([
      'site' => 'example.com',
      'slug' => 'track-slug'
    ]);

    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());

    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
    $this->assertEquals('Tracker Error', $body['message']);
  }

  public function test_article_track_stats_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(JobService::class);
    $mockService->method('articleTrackStats')
      ->willReturn(true);

    $container->set(JobService::class, $mockService);

    $request = $this->createRequest('POST', '/job/article_track_stats');
    $request = $request->withParsedBody([
      'site' => 'example.com',
      'slug' => 'track-slug'
    ]);

    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());

    $body = $this->parseJson($response);
    $this->assertEquals('Ok', $body['message']);
    $this->assertTrue($body['data']);
  }
}
