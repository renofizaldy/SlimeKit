<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\General;
use App\Lib\Valkey;
use ReflectionMethod;

class GeneralRecentArticlesTest extends TestCase
{
  private General $general;

  protected function setUp(): void
  {
    parent::setUp();
    $mockValkey = $this->createMock(Valkey::class);
    $this->general = new General($mockValkey);
  }

  public function test_get_recent_articles_method_signature_and_contract()
  {
    $this->assertTrue(method_exists($this->general, 'getRecentArticles'));

    $ref = new ReflectionMethod($this->general, 'getRecentArticles');
    $params = $ref->getParameters();

    $this->assertCount(1, $params);
    $this->assertSame('limit', $params[0]->getName());
    $this->assertSame(75, $params[0]->getDefaultValue());
  }
}
