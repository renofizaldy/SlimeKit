<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\General;
use App\Lib\Valkey;
use App\Lib\Cloudinary;
use App\Lib\Cronhooks;
use App\Services\Client\ClientArticleService;
use App\Services\Client\ClientArticleCategoryService;

class ClientArticlePaginationTest extends TestCase
{
  public function test_client_article_service_list_returns_cached_data_with_pagination_key()
  {
    $mockValkey = $this->createMock(Valkey::class);
    $mockCloudinary = $this->createMock(Cloudinary::class);
    $mockCronhooks = $this->createMock(Cronhooks::class);
    $helper = new General($mockValkey);

    $cachedPayload = [
      'items' => [
        ['slug' => 'test-article-1', 'title' => 'Article 1'],
      ],
      'pagination' => [
        'page'       => 2,
        'pageSize'   => 5,
        'total'      => 12,
        'totalPages' => 3,
      ],
    ];

    $expectedCacheKey = 'article:list:order_by=publish:order_type=DESC:page=2:pageSize=5:search=:category=:featured=';

    $mockValkey->expects($this->once())
      ->method('get')
      ->with($expectedCacheKey)
      ->willReturn(json_encode($cachedPayload));

    $service = new ClientArticleService($helper, $mockCloudinary, $mockValkey, $mockCronhooks);

    $result = $service->list([
      'page'     => 2,
      'pageSize' => 5,
    ]);

    $this->assertEquals('cache', $result['source']);
    $this->assertEquals($cachedPayload['items'], $result['items']);
    $this->assertEquals(2, $result['pagination']['page']);
    $this->assertEquals(5, $result['pagination']['pageSize']);
  }

  public function test_client_article_category_service_list_returns_cached_data_with_pagination_key()
  {
    $mockValkey = $this->createMock(Valkey::class);
    $mockCloudinary = $this->createMock(Cloudinary::class);
    $helper = new General($mockValkey);

    $cachedPayload = [
      'items' => [
        ['slug' => 'category-1', 'title' => 'Category 1'],
      ],
      'pagination' => [
        'page'       => 1,
        'pageSize'   => 10,
        'total'      => 4,
        'totalPages' => 1,
      ],
    ];

    $expectedCacheKey = 'article_category:list:page=1:pageSize=10';

    $mockValkey->expects($this->once())
      ->method('get')
      ->with($expectedCacheKey)
      ->willReturn(json_encode($cachedPayload));

    $service = new ClientArticleCategoryService($helper, $mockCloudinary, $mockValkey);

    $result = $service->list([
      'page'     => 1,
      'pageSize' => 10,
    ]);

    $this->assertEquals('cache', $result['source']);
    $this->assertEquals($cachedPayload['items'], $result['items']);
    $this->assertEquals(1, $result['pagination']['page']);
    $this->assertEquals(10, $result['pagination']['pageSize']);
  }
}
