<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\General;
use App\Lib\Valkey;
use App\Lib\Cloudinary;
use App\Lib\Cronhooks;
use App\Lib\NextRevalidator;
use App\Services\Client\ClientArticleService;
use App\Services\Admin\AdminArticleService;
use ReflectionMethod;

class BlokbacaCacheAndInvalidationWorkflowTest extends TestCase
{
  public function test_client_article_returns_source_cache_when_cached_for_blokbaca()
  {
    $mockValkey = $this->createMock(Valkey::class);
    $mockCloudinary = $this->createMock(Cloudinary::class);
    $mockCronhooks = $this->createMock(Cronhooks::class);
    $helper = new General($mockValkey);

    $cachedPayload = [
      'items' => [
        ['slug' => 'berita-blokbaca', 'title' => 'Berita Blokbaca Terkini', 'site' => 'blokbaca'],
      ],
      'pagination' => [
        'page'       => 1,
        'pageSize'   => 10,
        'total'      => 1,
        'totalPages' => 1,
      ],
    ];

    $expectedKey = 'article:list:site=blokbaca:order_by=publish:order_type=DESC:page=1:pageSize=10:search=:category=:featured=';

    $mockValkey->expects($this->once())
      ->method('get')
      ->with($expectedKey)
      ->willReturn(json_encode($cachedPayload));

    $service = new ClientArticleService($helper, $mockCloudinary, $mockValkey, $mockCronhooks);

    $result = $service->list([
      'site'     => 'blokbaca',
      'page'     => 1,
      'pageSize' => 10,
    ]);

    $this->assertSame('cache', $result['source']);
    $this->assertSame($cachedPayload['items'], $result['items']);
  }

  public function test_admin_article_invalidation_properly_targets_blokbaca_namespace_only()
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

    $adminService = new AdminArticleService(
      $mockGeneral,
      $mockCloudinary,
      $mockValkey,
      $mockCronhooks,
      $mockNextRevalidator
    );

    $reflection = new ReflectionMethod($adminService, 'invalidateArticleCache');
    $reflection->setAccessible(true);
    $reflection->invoke($adminService, 'blokbaca');

    // Verify all targeted prefixes include trailing colon
    $this->assertContains('article:list:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article:detail:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article:similar:site=blokbaca:', $deletedPrefixes);
    $this->assertContains('article:admin', $deletedPrefixes);

    // Verify other sites are not deleted
    $this->assertNotContains('article:list:site=lolitamakeup:', $deletedPrefixes);
  }
}
