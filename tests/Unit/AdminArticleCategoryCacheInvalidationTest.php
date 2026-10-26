<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Admin\AdminArticleCategoryService;
use App\Helpers\General;
use App\Lib\Cloudinary;
use App\Lib\Valkey;
use ReflectionMethod;

class AdminArticleCategoryCacheInvalidationTest extends TestCase
{
  public function test_invalidate_category_and_article_cache_deletes_both_category_and_article_caches()
  {
    $mockValkey = $this->createMock(Valkey::class);
    $mockGeneral = $this->createMock(General::class);
    $mockCloudinary = $this->createMock(Cloudinary::class);

    $deletedPrefixes = [];
    $mockValkey->expects($this->exactly(6))
      ->method('deleteByPrefix')
      ->willReturnCallback(function ($prefix) use (&$deletedPrefixes) {
        $deletedPrefixes[] = $prefix;
        return true;
      });

    $service = new AdminArticleCategoryService(
      $mockGeneral,
      $mockCloudinary,
      $mockValkey
    );

    $reflection = new ReflectionMethod($service, 'invalidateCategoryAndArticleCache');
    $reflection->setAccessible(true);
    $reflection->invoke($service, 'blokbaca');

    // Category caches
    $this->assertContains('article_category:list:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article_category:detail:site=blokbaca:', $deletedPrefixes);

    // Article caches
    $this->assertContains('article:list:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article:detail:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article:similar:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article:admin', $deletedPrefixes);
  }

  public function test_invalidate_category_and_article_cache_cleans_both_new_and_old_site()
  {
    $mockValkey = $this->createMock(Valkey::class);
    $mockGeneral = $this->createMock(General::class);
    $mockCloudinary = $this->createMock(Cloudinary::class);

    $deletedPrefixes = [];
    $mockValkey->expects($this->exactly(11))
      ->method('deleteByPrefix')
      ->willReturnCallback(function ($prefix) use (&$deletedPrefixes) {
        $deletedPrefixes[] = $prefix;
        return true;
      });

    $service = new AdminArticleCategoryService(
      $mockGeneral,
      $mockCloudinary,
      $mockValkey
    );

    $reflection = new ReflectionMethod($service, 'invalidateCategoryAndArticleCache');
    $reflection->setAccessible(true);
    $reflection->invoke($service, 'lolitamakeup', 'blokbaca');

    $this->assertContains('article_category:list:site=lolitamakeup:', $deletedPrefixes);
    $this->assertContains('article_category:detail:site=lolitamakeup:', $deletedPrefixes);
    $this->assertContains('article_category:list:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article_category:detail:site=blokbaca:', $deletedPrefixes);

    $this->assertContains('article:list:site=lolitamakeup:', $deletedPrefixes);
    $this->assertContains('article:detail:site=lolitamakeup:', $deletedPrefixes);
    $this->assertContains('article:similar:site=lolitamakeup:', $deletedPrefixes);

    $this->assertContains('article:list:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article:detail:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article:similar:site=blokbaca:', $deletedPrefixes);

    $this->assertContains('article:admin', $deletedPrefixes);
  }
}
