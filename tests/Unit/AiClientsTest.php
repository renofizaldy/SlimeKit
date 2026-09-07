<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Lib\GeminiClient;
use App\Lib\OpenAIClient;
use App\Lib\TavilyClient;
use DI\ContainerBuilder;
use Exception;

class AiClientsTest extends TestCase
{
  public function test_gemini_client_can_be_instantiated_directly()
  {
    $client = new GeminiClient('test-gemini-key');
    $this->assertInstanceOf(GeminiClient::class, $client);
  }

  public function test_openai_client_can_be_instantiated_directly()
  {
    $client = new OpenAIClient('test-openai-key');
    $this->assertInstanceOf(OpenAIClient::class, $client);
  }

  public function test_tavily_client_can_be_instantiated_directly()
  {
    $client = new TavilyClient('test-tavily-key');
    $this->assertInstanceOf(TavilyClient::class, $client);
  }

  public function test_container_instantiates_ai_clients_with_keys()
  {
    $previousGemini = $_ENV['GEMINI_API_KEY'] ?? null;
    $previousOpenai = $_ENV['OPENAI_API_KEY'] ?? null;
    $previousTavily = $_ENV['TAVILY_API_KEY'] ?? null;

    $_ENV['GEMINI_API_KEY'] = 'fake-gemini-key';
    $_ENV['OPENAI_API_KEY'] = 'fake-openai-key';
    $_ENV['TAVILY_API_KEY'] = 'fake-tavily-key';

    try {
      $builder = new ContainerBuilder();
      $registerDependencies = require __DIR__ . '/../../src/config/dependencies.php';
      $registerDependencies($builder);
      $container = $builder->build();

      $gemini = $container->get(GeminiClient::class);
      $openai = $container->get(OpenAIClient::class);
      $tavily = $container->get(TavilyClient::class);

      $this->assertInstanceOf(GeminiClient::class, $gemini);
      $this->assertInstanceOf(OpenAIClient::class, $openai);
      $this->assertInstanceOf(TavilyClient::class, $tavily);
    } finally {
      if ($previousGemini !== null) {
        $_ENV['GEMINI_API_KEY'] = $previousGemini;
      } else {
        unset($_ENV['GEMINI_API_KEY']);
      }
      if ($previousOpenai !== null) {
        $_ENV['OPENAI_API_KEY'] = $previousOpenai;
      } else {
        unset($_ENV['OPENAI_API_KEY']);
      }
      if ($previousTavily !== null) {
        $_ENV['TAVILY_API_KEY'] = $previousTavily;
      } else {
        unset($_ENV['TAVILY_API_KEY']);
      }
    }
  }

  public function test_container_throws_exception_when_keys_are_missing()
  {
    $previousGemini = $_ENV['GEMINI_API_KEY'] ?? null;
    unset($_ENV['GEMINI_API_KEY']);

    try {
      $builder = new ContainerBuilder();
      $registerDependencies = require __DIR__ . '/../../src/config/dependencies.php';
      $registerDependencies($builder);
      $container = $builder->build();

      $this->expectException(Exception::class);
      $this->expectExceptionMessage('Missing GEMINI_API_KEY in .env');
      $container->get(GeminiClient::class);
    } finally {
      if ($previousGemini !== null) {
        $_ENV['GEMINI_API_KEY'] = $previousGemini;
      }
    }
  }
}
