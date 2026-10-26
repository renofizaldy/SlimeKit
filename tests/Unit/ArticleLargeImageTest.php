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

class ArticleLargeImageTest extends TestCase
{
  private General $general;

  protected function setUp(): void
  {
    parent::setUp();
    $mockValkey = $this->createMock(Valkey::class);
    $this->general = new General($mockValkey);
  }

  public function test_google_discover_image_format_conformance()
  {
    $originalUrl = 'https://res.cloudinary.com/dh7a4idde/image/upload/v1790587816/SlimeNews/futjoynecyuuipwyljzv.webp';
    $largeUrl = $this->general->formatCloudinaryUrl($originalUrl, 'c_fill,w_1200,h_675,g_auto,q_auto,f_auto');

    // Must contain width 1200 and height 675 for 16:9 ratio
    $this->assertStringContainsString('w_1200', $largeUrl);
    $this->assertStringContainsString('h_675', $largeUrl);
    $this->assertStringContainsString('c_fill', $largeUrl);
    $this->assertStringContainsString('g_auto', $largeUrl);
    $this->assertStringContainsString('q_auto', $largeUrl);
    $this->assertStringContainsString('f_auto', $largeUrl);
    $this->assertStringContainsString('v1790587816/SlimeNews/futjoynecyuuipwyljzv.webp', $largeUrl);
  }

  public function test_client_article_service_preserves_large_key_in_cached_payload()
  {
    $mockValkey = $this->createMock(Valkey::class);
    $mockCloudinary = $this->createMock(Cloudinary::class);
    $mockCronhooks = $this->createMock(Cronhooks::class);

    $cachedPayload = [
      'items' => [
        [
          'slug'    => 'test-article',
          'title'   => 'Test Article',
          'picture' => [
            'original'  => 'https://res.cloudinary.com/dh7a4idde/image/upload/v12345/pic.webp',
            'large'     => 'https://res.cloudinary.com/dh7a4idde/image/upload/c_fill,w_1200,h_675,g_auto,q_auto,f_auto/v12345/pic.webp',
            'thumbnail' => 'https://res.cloudinary.com/dh7a4idde/image/upload/c_thumb,w_600/v12345/pic.webp',
            'caption'   => 'Photo caption',
          ],
        ],
      ],
      'pagination' => [
        'page'       => 1,
        'pageSize'   => 10,
        'total'      => 1,
        'totalPages' => 1,
      ],
    ];

    $mockValkey->method('get')->willReturn(json_encode($cachedPayload));

    $service = new ClientArticleService($this->general, $mockCloudinary, $mockValkey, $mockCronhooks);
    $result = $service->list(['site' => 'lolitamakeup']);

    $this->assertArrayHasKey('items', $result);
    $this->assertArrayHasKey('picture', $result['items'][0]);
    $this->assertArrayHasKey('large', $result['items'][0]['picture']);
    $this->assertStringContainsString('w_1200', $result['items'][0]['picture']['large']);
  }
}
