<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Admin\AdminArticleService;
use App\Helpers\General;
use App\Lib\Cloudinary;
use App\Lib\Valkey;
use App\Lib\Cronhooks;
use App\Lib\NextRevalidator;
use ReflectionMethod;

class AdminArticleCacheInvalidationTest extends TestCase
{
  public function test_invalidate_article_cache_deletes_list_detail_similar_and_admin()
  {
    $mockValkey = $this->createMock(Valkey::class);
    $mockGeneral = $this->createMock(General::class);
    $mockCloudinary = $this->createMock(Cloudinary::class);
    $mockCronhooks = $this->createMock(Cronhooks::class);
    $mockNextRevalidator = $this->createMock(NextRevalidator::class);

    $deletedPrefixes = [];
    $mockValkey->expects($this->exactly(4))
      ->method('deleteByPrefix')
      ->willReturnCallback(function ($prefix) use (&$deletedPrefixes) {
        $deletedPrefixes[] = $prefix;
        return true;
      });

    $service = new AdminArticleService(
      $mockGeneral,
      $mockCloudinary,
      $mockValkey,
      $mockCronhooks,
      $mockNextRevalidator
    );

    $reflection = new ReflectionMethod($service, 'invalidateArticleCache');
    $reflection->setAccessible(true);
    $reflection->invoke($service, 'blokbaca');

    $this->assertContains('article:list:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article:detail:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article:similar:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article:admin', $deletedPrefixes);
  }

  public function test_invalidate_article_cache_cleans_both_new_and_old_site()
  {
    $mockValkey = $this->createMock(Valkey::class);
    $mockGeneral = $this->createMock(General::class);
    $mockCloudinary = $this->createMock(Cloudinary::class);
    $mockCronhooks = $this->createMock(Cronhooks::class);
    $mockNextRevalidator = $this->createMock(NextRevalidator::class);

    $deletedPrefixes = [];
    $mockValkey->expects($this->exactly(7))
      ->method('deleteByPrefix')
      ->willReturnCallback(function ($prefix) use (&$deletedPrefixes) {
        $deletedPrefixes[] = $prefix;
        return true;
      });

    $service = new AdminArticleService(
      $mockGeneral,
      $mockCloudinary,
      $mockValkey,
      $mockCronhooks,
      $mockNextRevalidator
    );

    $reflection = new ReflectionMethod($service, 'invalidateArticleCache');
    $reflection->setAccessible(true);
    $reflection->invoke($service, 'lolitamakeup', 'blokbaca');

    $this->assertContains('article:list:site=lolitamakeup:', $deletedPrefixes);
    $this->assertContains('article:detail:site=lolitamakeup:', $deletedPrefixes);
    $this->assertContains('article:similar:site=lolitamakeup:', $deletedPrefixes);
    $this->assertContains('article:list:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article:detail:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article:similar:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article:admin', $deletedPrefixes);
  }
}
