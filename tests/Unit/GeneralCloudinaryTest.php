<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\General;
use App\Lib\Valkey;
use App\Lib\Cloudinary;

class GeneralCloudinaryTest extends TestCase
{
  private General $general;

  protected function setUp(): void
  {
    parent::setUp();
    $mockValkey = $this->createMock(Valkey::class);
    $this->general = new General($mockValkey);
  }

  public function test_format_cloudinary_url_with_versioned_url()
  {
    $input = 'https://res.cloudinary.com/dh7a4idde/image/upload/v1790587816/SlimeNews/futjoynecyuuipwyljzv.webp';
    $output = $this->general->formatCloudinaryUrl($input, 'c_fill,w_1200,h_675,g_auto,q_auto,f_auto');
    $expected = 'https://res.cloudinary.com/dh7a4idde/image/upload/c_fill,w_1200,h_675,g_auto,q_auto,f_auto/v1790587816/SlimeNews/futjoynecyuuipwyljzv.webp';

    $this->assertEquals($expected, $output);
  }

  public function test_format_cloudinary_url_replaces_existing_transformation()
  {
    $input = 'https://res.cloudinary.com/dh7a4idde/image/upload/c_thumb,w_600/v1790587816/SlimeNews/futjoynecyuuipwyljzv.webp';
    $output = $this->general->formatCloudinaryUrl($input, 'c_fill,w_1200,h_675,g_auto,q_auto,f_auto');
    $expected = 'https://res.cloudinary.com/dh7a4idde/image/upload/c_fill,w_1200,h_675,g_auto,q_auto,f_auto/v1790587816/SlimeNews/futjoynecyuuipwyljzv.webp';

    $this->assertEquals($expected, $output);
  }

  public function test_format_cloudinary_url_without_version()
  {
    $input = 'https://res.cloudinary.com/dh7a4idde/image/upload/SlimeNews/futjoynecyuuipwyljzv.webp';
    $output = $this->general->formatCloudinaryUrl($input, 'c_thumb,w_600');
    $expected = 'https://res.cloudinary.com/dh7a4idde/image/upload/c_thumb,w_600/SlimeNews/futjoynecyuuipwyljzv.webp';

    $this->assertEquals($expected, $output);
  }

  public function test_format_cloudinary_url_with_existing_transformation_without_version()
  {
    $input = 'https://res.cloudinary.com/dh7a4idde/image/upload/c_thumb,w_600/SlimeNews/futjoynecyuuipwyljzv.webp';
    $output = $this->general->formatCloudinaryUrl($input, 'c_fill,w_1200,h_675,g_auto,q_auto,f_auto');
    $expected = 'https://res.cloudinary.com/dh7a4idde/image/upload/c_fill,w_1200,h_675,g_auto,q_auto,f_auto/SlimeNews/futjoynecyuuipwyljzv.webp';

    $this->assertEquals($expected, $output);
  }

  public function test_format_cloudinary_url_non_cloudinary_returns_unchanged()
  {
    $externalUrl = 'https://images.unsplash.com/photo-12345?w=800';
    $this->assertEquals($externalUrl, $this->general->formatCloudinaryUrl($externalUrl, 'c_thumb,w_600'));

    $empty = '';
    $this->assertEquals($empty, $this->general->formatCloudinaryUrl($empty, 'c_thumb,w_600'));

    $nullVal = null;
    $this->assertNull($this->general->formatCloudinaryUrl($nullVal, 'c_thumb,w_600'));
  }

  public function test_upload_image_returns_large_and_thumbnail()
  {
    $mockCloudinary = $this->createMock(Cloudinary::class);
    $mockCloudinary->expects($this->once())
      ->method('upload')
      ->with('dummy-file-data')
      ->willReturn([
        'public_id'  => 'SlimeNews/test12345',
        'secure_url' => 'https://res.cloudinary.com/demo/image/upload/v1234567890/SlimeNews/test12345.webp'
      ]);

    $result = $this->general->uploadImage($mockCloudinary, 'dummy-file-data');

    $this->assertIsArray($result);
    $this->assertEquals('SlimeNews/test12345', $result['public_id']);
    $this->assertEquals('https://res.cloudinary.com/demo/image/upload/v1234567890/SlimeNews/test12345.webp', $result['original']);
    $this->assertEquals('https://res.cloudinary.com/demo/image/upload/c_fill,w_1200,h_675,g_auto,q_auto,f_auto/v1234567890/SlimeNews/test12345.webp', $result['large']);
    $this->assertEquals('https://res.cloudinary.com/demo/image/upload/c_thumb,w_600/v1234567890/SlimeNews/test12345.webp', $result['thumbnail']);
  }
}
