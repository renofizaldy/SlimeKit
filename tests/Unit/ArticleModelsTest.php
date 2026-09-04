<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleAgent;
use App\Models\ArticleStats;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleModelsTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    $capsule = new \Illuminate\Database\Capsule\Manager();
    $capsule->addConnection([
      'driver' => 'sqlite',
      'database' => ':memory:',
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
  }

  public function test_article_model_properties_and_relations()
  {
    $article = new Article();
    $this->assertEquals('tb_article', $article->getTable());
    $this->assertEquals(['id'], $article->getGuarded());
    $this->assertTrue($article->timestamps);
    $this->assertArrayHasKey('featured', $article->getCasts());
    $this->assertEquals('array', $article->getCasts()['featured']);

    $this->assertInstanceOf(BelongsTo::class, $article->category());
    $this->assertInstanceOf(BelongsTo::class, $article->picture());
    $this->assertInstanceOf(BelongsTo::class, $article->authorUser());
    $this->assertInstanceOf(HasOne::class, $article->seoMeta());
    $this->assertInstanceOf(HasOne::class, $article->articleAgent());
    $this->assertInstanceOf(HasOne::class, $article->cronhooks());
  }

  public function test_article_category_model_properties_and_relations()
  {
    $cat = new ArticleCategory();
    $this->assertEquals('tb_article_category', $cat->getTable());
    $this->assertEquals(['id'], $cat->getGuarded());
    $this->assertTrue($cat->timestamps);

    $this->assertInstanceOf(HasMany::class, $cat->articles());
    $this->assertInstanceOf(HasOne::class, $cat->seoMeta());
  }

  public function test_article_agent_model_properties_and_relations()
  {
    $agent = new ArticleAgent();
    $this->assertEquals('tb_article_agent', $agent->getTable());
    $this->assertFalse($agent->incrementing);
    $this->assertEquals('string', $agent->getKeyType());
    $this->assertArrayHasKey('brainstorming_options', $agent->getCasts());
    $this->assertArrayHasKey('selected_option_data', $agent->getCasts());

    $this->assertInstanceOf(BelongsTo::class, $agent->article());
  }

  public function test_article_stats_model_properties()
  {
    $stats = new ArticleStats();
    $this->assertEquals('tb_article_stats', $stats->getTable());
    $this->assertEquals(['id'], $stats->getGuarded());
    $this->assertTrue($stats->timestamps);
  }
}
