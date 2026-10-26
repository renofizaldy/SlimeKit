<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Admin\AdminArticleAgentService;
use App\Helpers\General;
use App\Lib\GeminiClient;
use App\Lib\TavilyClient;
use App\Lib\OpenAIClient;
use App\Models\ArticleAgent;
use Exception;

class AdminArticleAgentServiceBrainstormTest extends TestCase
{
  private array $createdUuids = [];

  protected function setUp(): void
  {
    parent::setUp();
    $this->getAppInstance();
  }

  protected function tearDown(): void
  {
    foreach ($this->createdUuids as $uuid) {
      try {
        ArticleAgent::destroy($uuid);
      } catch (\Throwable $e) {
        // Ignore cleanup error
      }
    }
    parent::tearDown();
  }

  public function test_step1_input_topic_auto_free_injects_negative_context_dynamic_angles_and_options()
  {
    $mockHelper = $this->createMock(General::class);
    $mockGemini = $this->createMock(GeminiClient::class);
    $mockTavily = $this->createMock(TavilyClient::class);
    $mockOpenai = $this->createMock(OpenAIClient::class);

    $mockHelper->expects($this->once())
      ->method('getCategories')
      ->willReturn([
        ['title' => 'Teknologi', 'description' => 'Seputar gadget dan software'],
        ['title' => 'Bisnis', 'description' => 'Strategi dan tips bisnis']
      ]);

    $mockHelper->expects($this->once())
      ->method('getRecentArticles')
      ->with(75)
      ->willReturn([
        ['title' => 'Panduan Belajar PHP 8 Modern', 'keyword' => 'belajar php 8'],
        ['title' => 'Review Framework AI Terbaik', 'keyword' => 'framework ai']
      ]);

    $capturedPrompt = null;
    $capturedOptions = null;

    $dummyOptions = [
      ['title' => 'Ide Topik Baru 1', 'keyword' => 'topik baru 1', 'summary' => 'Ringkasan singkat ide 1.'],
      ['title' => 'Ide Topik Baru 2', 'keyword' => 'topik baru 2', 'summary' => 'Ringkasan singkat ide 2.']
    ];

    $mockOpenai->expects($this->once())
      ->method('generateContent')
      ->willReturnCallback(function ($sysPrompt, $prompt, $model, $options) use (&$capturedPrompt, &$capturedOptions, $dummyOptions) {
        $capturedPrompt = $prompt;
        $capturedOptions = $options;
        return json_encode($dummyOptions);
      });

    $service = new AdminArticleAgentService($mockHelper, $mockGemini, $mockTavily, $mockOpenai);

    $result = $service->step1_inputTopic([
      'topic_mode' => 'auto',
      'strategy'   => 'free'
    ]);

    // 1. Verifikasi hasil dan penyimpanan DB
    $this->assertArrayHasKey('uuid', $result);
    $this->assertArrayHasKey('options', $result);
    $this->assertCount(2, $result['options']);
    $this->createdUuids[] = $result['uuid'];

    $savedAgent = ArticleAgent::find($result['uuid']);
    $this->assertNotNull($savedAgent);
    $this->assertEquals('Auto (Free)', $savedAgent->user_input_topic);

    // 2. Verifikasi Injeksi Negative Context
    $this->assertStringContainsString('DAFTAR ARTIKEL YANG SUDAH DITULIS DI SITUS INI (DILARANG KERAS MEREKOMENDASIKAN ULANG):', $capturedPrompt);
    $this->assertStringContainsString('1. Panduan Belajar PHP 8 Modern | Keyword: belajar php 8', $capturedPrompt);
    $this->assertStringContainsString('2. Review Framework AI Terbaik | Keyword: framework ai', $capturedPrompt);
    $this->assertStringContainsString('DILARANG KERAS membuat ide topik yang mengangkat tema, judul, atau target kata kunci yang serupa', $capturedPrompt);

    // 3. Verifikasi Dynamic Angles & Categories
    $this->assertStringContainsString('DAFTAR KATEGORI ARTIKEL:', $capturedPrompt);
    $this->assertStringContainsString('Teknologi — Seputar gadget dan software', $capturedPrompt);
    $this->assertStringContainsString('EKSPLORASI SUDUT PANDANG (DYNAMIC ANGLES):', $capturedPrompt);

    // 4. Verifikasi Options LLM
    $this->assertIsArray($capturedOptions);
    $this->assertEmpty($capturedOptions);
  }

  public function test_step1_input_topic_auto_free_fallback_when_recent_articles_empty()
  {
    $mockHelper = $this->createMock(General::class);
    $mockGemini = $this->createMock(GeminiClient::class);
    $mockTavily = $this->createMock(TavilyClient::class);
    $mockOpenai = $this->createMock(OpenAIClient::class);

    $mockHelper->expects($this->once())
      ->method('getCategories')
      ->willReturn([
        ['title' => 'Gaya Hidup', 'description' => '-']
      ]);

    // Database artikel masih kosong untuk situs ini
    $mockHelper->expects($this->once())
      ->method('getRecentArticles')
      ->with(75)
      ->willReturn([]);

    $capturedPrompt = null;
    $capturedOptions = null;

    $mockOpenai->expects($this->once())
      ->method('generateContent')
      ->willReturnCallback(function ($sysPrompt, $prompt, $model, $options) use (&$capturedPrompt, &$capturedOptions) {
        $capturedPrompt = $prompt;
        $capturedOptions = $options;
        return json_encode([
          ['title' => 'Ide Topik Segar', 'keyword' => 'ide segar', 'summary' => 'Ringkasan singkat ide segar.']
        ]);
      });

    $service = new AdminArticleAgentService($mockHelper, $mockGemini, $mockTavily, $mockOpenai);

    $result = $service->step1_inputTopic([
      'topic_mode' => 'auto',
      'strategy'   => 'free'
    ]);

    $this->assertArrayHasKey('uuid', $result);
    $this->createdUuids[] = $result['uuid'];

    // Negatif context tidak boleh muncul jika artikel kosong
    $this->assertStringNotContainsString('DAFTAR ARTIKEL YANG SUDAH DITULIS DI SITUS INI', $capturedPrompt);

    // Kategori dan dynamic angles tetap harus ada
    $this->assertStringContainsString('DAFTAR KATEGORI ARTIKEL:', $capturedPrompt);
    $this->assertStringContainsString('EKSPLORASI SUDUT PANDANG (DYNAMIC ANGLES):', $capturedPrompt);

    // Options kosong sesuai batasan model
    $this->assertIsArray($capturedOptions);
    $this->assertEmpty($capturedOptions);
  }

  public function test_step1_input_topic_throws_exception_when_openai_returns_invalid_json()
  {
    $mockHelper = $this->createMock(General::class);
    $mockGemini = $this->createMock(GeminiClient::class);
    $mockTavily = $this->createMock(TavilyClient::class);
    $mockOpenai = $this->createMock(OpenAIClient::class);

    $mockHelper->method('getCategories')->willReturn([]);
    $mockHelper->method('getRecentArticles')->willReturn([]);

    $mockOpenai->expects($this->once())
      ->method('generateContent')
      ->willReturn("Maaf, ini bukan JSON.");

    $service = new AdminArticleAgentService($mockHelper, $mockGemini, $mockTavily, $mockOpenai);

    $this->expectException(Exception::class);
    $this->expectExceptionMessageMatches('/Output bukan JSON valid/');

    $service->step1_inputTopic([
      'topic_mode' => 'auto',
      'strategy'   => 'free'
    ]);
  }
}
