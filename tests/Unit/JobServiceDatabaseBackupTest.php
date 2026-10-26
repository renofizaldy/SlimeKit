<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\JobService;
use App\Helpers\General;
use App\Lib\Valkey;
use App\Lib\Cronhooks;
use App\Lib\NextRevalidator;
use App\Lib\CloudflareR2;
use App\Lib\GoogleCloudStorage;
use ReflectionMethod;

class JobServiceDatabaseBackupTest extends TestCase
{
  public function test_upload_to_providers_dispatches_to_both_r2_and_gcs()
  {
    $mockGeneral = $this->createMock(General::class);
    $mockValkey = $this->createMock(Valkey::class);
    $mockCronhooks = $this->createMock(Cronhooks::class);
    $mockNextRevalidator = $this->createMock(NextRevalidator::class);
    $mockR2 = $this->createMock(CloudflareR2::class);
    $mockGcs = $this->createMock(GoogleCloudStorage::class);

    $destination = 'backup/test_backup.sql.gz';
    $tempFile = tempnam(sys_get_temp_dir(), 'test_dump_');
    file_put_contents($tempFile, 'dummy sql content');

    $mockR2->expects($this->once())
      ->method('put')
      ->with($destination, $tempFile, 'application/gzip')
      ->willReturn($destination);

    $mockGcs->expects($this->once())
      ->method('storage')
      ->with('upload', 'test-bucket', $tempFile, $destination)
      ->willReturn($destination);

    $service = new JobService(
      $mockGeneral,
      $mockValkey,
      $mockCronhooks,
      $mockNextRevalidator,
      $mockR2,
      $mockGcs,
      [
        'backup_provider' => ['provider' => 'both', 'folder' => 'backup'],
        'google_cloud_storage' => ['bucket' => 'test-bucket'],
      ]
    );

    $method = new ReflectionMethod($service, 'uploadToProviders');
    $method->setAccessible(true);
    $results = $method->invoke($service, $tempFile, $destination, 'both');

    @unlink($tempFile);

    $this->assertArrayHasKey('r2', $results);
    $this->assertEquals('SUCCESS', $results['r2']['status']);
    $this->assertArrayHasKey('gcs', $results);
    $this->assertEquals('SUCCESS', $results['gcs']['status']);
  }

  public function test_format_bytes_utility()
  {
    $mockGeneral = $this->createMock(General::class);
    $mockValkey = $this->createMock(Valkey::class);
    $mockCronhooks = $this->createMock(Cronhooks::class);
    $mockNextRevalidator = $this->createMock(NextRevalidator::class);

    $service = new JobService($mockGeneral, $mockValkey, $mockCronhooks, $mockNextRevalidator);

    $method = new ReflectionMethod($service, 'formatBytes');
    $method->setAccessible(true);

    $this->assertEquals('500 B', $method->invoke($service, 500));
    $this->assertEquals('1.5 KB', $method->invoke($service, 1536));
    $this->assertEquals('2 MB', $method->invoke($service, 2097152));
  }
}
