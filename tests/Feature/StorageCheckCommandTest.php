<?php

namespace Tests\Feature;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class StorageCheckCommandTest extends TestCase
{
    public function test_storage_check_verifies_content_and_removes_probe(): void
    {
        Storage::fake('r2');

        $this->artisan('app:storage-check')
            ->expectsOutput('R2 storage check passed')
            ->assertExitCode(0);

        $this->assertSame([], Storage::disk('r2')->allFiles('_health'));
    }

    public function test_storage_check_failure_does_not_expose_credentials_or_trace(): void
    {
        config([
            'filesystems.disks.r2.key' => 'R2_ACCESS_KEY_SHOULD_NOT_LEAK',
            'filesystems.disks.r2.secret' => 'R2_SECRET_SHOULD_NOT_LEAK',
        ]);

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->andThrow(new RuntimeException(
            'failed for R2_ACCESS_KEY_SHOULD_NOT_LEAK:R2_SECRET_SHOULD_NOT_LEAK at https://example.test?token=private'
        ));
        Storage::shouldReceive('disk')->with('r2')->andReturn($disk);

        $this->artisan('app:storage-check')
            ->expectsOutput('R2 storage check failed')
            ->doesntExpectOutputToContain('R2_ACCESS_KEY_SHOULD_NOT_LEAK')
            ->doesntExpectOutputToContain('R2_SECRET_SHOULD_NOT_LEAK')
            ->doesntExpectOutputToContain('token=private')
            ->doesntExpectOutputToContain('RuntimeException')
            ->assertExitCode(1);
    }

    public function test_storage_check_fails_when_probe_cannot_be_deleted(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturnTrue();
        $disk->shouldReceive('get')->once()->andReturn('tet-memory-r2-health-check');
        $disk->shouldReceive('delete')->twice()->andReturnFalse();
        Storage::shouldReceive('disk')->with('r2')->andReturn($disk);

        $this->artisan('app:storage-check')
            ->expectsOutput('R2 storage check failed')
            ->assertExitCode(1);
    }

    public function test_storage_check_redacts_disk_resolution_failure(): void
    {
        Storage::shouldReceive('disk')->with('r2')->andThrow(new RuntimeException(
            'R2_SECRET_SHOULD_NOT_LEAK'
        ));

        $this->artisan('app:storage-check')
            ->expectsOutput('R2 storage check failed')
            ->doesntExpectOutputToContain('R2_SECRET_SHOULD_NOT_LEAK')
            ->assertExitCode(1);
    }
}
