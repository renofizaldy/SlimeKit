<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Admin\AdminArticleAgentService;
use Exception;

class AdminArticleAgentTest extends TestCase
{
  //* =================== 1. STEP 1: INPUT TOPIC ===================
  public function test_step1_input_topic_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/article_agent/step1_input_topic');
    $request = $request->withParsedBody([]);
    
    $response = $app->handle($request);
    
    $this->assertEquals(422, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
    $this->assertEquals('Validasi gagal', $body['message']);
  }

  public function test_step1_input_topic_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleAgentService::class);
    $mockService->method('step1_inputTopic')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleAgentService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_agent/step1_input_topic');
    $request = $request->withParsedBody(['topic' => 'AI']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
    $this->assertEquals('Service Error', $body['message']);
  }

  public function test_step1_input_topic_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleAgentService::class);
    $mockService->method('step1_inputTopic')->willReturn(['result' => 'ok']);
    $container->set(AdminArticleAgentService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_agent/step1_input_topic');
    $request = $request->withParsedBody(['topic' => 'AI']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('Ok', $body['message']);
  }

  //* =================== 2. STEP 1: INPUT SELECTION ===================
  public function test_step1_input_selection_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/article_agent/step1_input_selection');
    $request = $request->withParsedBody([]);
    
    $response = $app->handle($request);
    
    $this->assertEquals(422, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
  }

  public function test_step1_input_selection_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleAgentService::class);
    $mockService->method('step1_inputSelection')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleAgentService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_agent/step1_input_selection');
    $request = $request->withParsedBody([
      'selection' => [
        'title' => 'Test',
        'keyword' => 'AI'
      ]
    ]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_step1_input_selection_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleAgentService::class);
    $mockService->method('step1_inputSelection')->willReturn(['result' => 'ok']);
    $container->set(AdminArticleAgentService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_agent/step1_input_selection');
    $request = $request->withParsedBody([
      'selection' => [
        'title' => 'Test',
        'keyword' => 'AI'
      ]
    ]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 3. STEP 2: DO RESEARCH ===================
  public function test_step2_do_research_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/article_agent/step2_do_research');
    $request = $request->withParsedBody([]);
    
    $response = $app->handle($request);
    
    $this->assertEquals(422, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
  }

  public function test_step2_do_research_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleAgentService::class);
    $mockService->method('step2_doResearch')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleAgentService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_agent/step2_do_research');
    $request = $request->withParsedBody(['uuid' => '1234']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_step2_do_research_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleAgentService::class);
    $mockService->method('step2_doResearch')->willReturn(['result' => 'ok']);
    $container->set(AdminArticleAgentService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_agent/step2_do_research');
    $request = $request->withParsedBody(['uuid' => '1234']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 4. STEP 3: DO WRITE ===================
  public function test_step3_do_write_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('POST', '/admin/article_agent/step3_do_write');
    $request = $request->withParsedBody([]);
    
    $response = $app->handle($request);
    
    $this->assertEquals(422, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
  }

  public function test_step3_do_write_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleAgentService::class);
    $mockService->method('step3_doWrite')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleAgentService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_agent/step3_do_write');
    $request = $request->withParsedBody(['uuid' => '1234', 'research_summary' => 'Some research']);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_step3_do_write_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleAgentService::class);
    $mockService->method('step3_doWrite')->willReturn(['result' => 'ok']);
    $container->set(AdminArticleAgentService::class, $mockService);

    $request = $this->createRequest('POST', '/admin/article_agent/step3_do_write');
    $request = $request->withParsedBody(['uuid' => '1234', 'research_summary' => 'Some research']);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }

  //* =================== 5. DETAIL ===================
  public function test_detail_fails_validation()
  {
    $app = $this->getAppInstance();
    $request = $this->createRequest('GET', '/admin/article_agent/detail');
    
    $response = $app->handle($request);
    
    $this->assertEquals(422, $response->getStatusCode());
    $body = $this->parseJson($response);
    $this->assertEquals('error', $body['status']);
  }

  public function test_detail_fails_service()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleAgentService::class);
    $mockService->method('detail')->willThrowException(new Exception('Service Error', 500));
    $container->set(AdminArticleAgentService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/article_agent/detail')->withQueryParams(['id_article' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(500, $response->getStatusCode());
  }

  public function test_detail_success()
  {
    $app = $this->getAppInstance();
    $container = $app->getContainer();

    $mockService = $this->createMock(AdminArticleAgentService::class);
    $mockService->method('detail')->willReturn(['id' => 1]);
    $container->set(AdminArticleAgentService::class, $mockService);

    $request = $this->createRequest('GET', '/admin/article_agent/detail')->withQueryParams(['id_article' => 1]);
    $response = $app->handle($request);

    $this->assertEquals(200, $response->getStatusCode());
  }
}
