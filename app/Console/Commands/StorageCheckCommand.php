<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class StorageCheckCommand extends Command
{
    protected $signature = 'app:storage-check';

    protected $description = 'Verify private Cloudflare R2 storage access';

    public function handle(): int
    {
        $disk = Storage::disk('r2');
        $path = '_health/'.Str::uuid().'.txt';
        $content = 'tet-memory-r2-health-check';
        $created = false;

        try {
            $disk->put($path, $content);
            $created = true;

            if ($disk->get($path) !== $content) {
                throw new \RuntimeException('R2 probe content mismatch');
            }

            $this->info('R2 storage check passed');

            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('R2 storage check failed');

            return self::FAILURE;
        } finally {
            if ($created) {
                try {
                    $disk->delete($path);
                } catch (Throwable) {
                    // The check result is already known; avoid leaking provider details.
                }
            }
        }
    }
}
