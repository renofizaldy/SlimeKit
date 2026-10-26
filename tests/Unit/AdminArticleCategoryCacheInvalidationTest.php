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
    $reflection->invoke($service);

    // Category caches
    $this->assertContains('article_category:list:', $deletedPrefixes);
    $this->assertContains('article_category:detail:', $deletedPrefixes);

    // Article caches
    $this->assertContains('article:list:', $deletedPrefixes);
    $this->assertContains('article:detail:', $deletedPrefixes);
    $this->assertContains('article:similar:', $deletedPrefixes);
    $this->assertContains('article:admin', $deletedPrefixes);
  }
}
