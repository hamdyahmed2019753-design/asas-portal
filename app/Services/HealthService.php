<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Lightweight health probe for load balancers / uptime monitors. Each component
 * is checked independently and failures are reported (never thrown), so the
 * endpoint always responds.
 */
class HealthService
{
    /**
     * @return array{healthy: bool, checks: array<string, mixed>}
     */
    public function check(): array
    {
        $checks = [
            'app' => 'ok',
            'database' => $this->probe(fn () => DB::connection()->getPdo() !== null),
            'cache' => $this->probe(function () {
                Cache::put('health:ping', 1, 10);

                return Cache::get('health:ping') === 1;
            }),
            'redis' => $this->probe(fn () => Redis::connection()->ping() !== false),
            'queue' => config('queue.default'),
            'storage' => $this->storageCheck(),
        ];

        // Database is the only hard dependency for serving requests.
        return ['healthy' => $checks['database'] === 'ok', 'checks' => $checks];
    }

    /**
     * Storage health: verifies the configured public disk, its root directory,
     * the public/storage symlink, reachability of any configured branding
     * files, and writability of the storage root. Returns a structured status
     * (status + message); never throws. The symlink is reported as a failure
     * and is NEVER created automatically — run `php artisan storage:link`.
     *
     * @return array{status: string, message: string}
     */
    private function storageCheck(): array
    {
        try {
            // 1. The configured public disk exists.
            if (! array_key_exists('public', (array) config('filesystems.disks'))) {
                return ['status' => 'failed', 'message' => 'public disk is not configured'];
            }

            $root = storage_path('app/public');

            // 2. The storage root exists.
            if (! is_dir($root)) {
                return ['status' => 'failed', 'message' => "storage root missing ({$root})"];
            }

            // 3. The public/storage symlink exists (public/storage -> storage/app/public).
            //    Handles both POSIX symlinks (is_link) and Windows junctions (file_exists).
            $link = public_path('storage');
            if (! file_exists($link) && ! is_link($link)) {
                return ['status' => 'failed', 'message' => 'public/storage symlink missing — run `php artisan storage:link`'];
            }

            // 4. Configured branding files are reachable on the public disk.
            foreach (['general.site_logo', 'general.favicon', 'seo.og_image'] as $key) {
                $path = setting($key);
                if (blank($path)) {
                    continue;
                }
                if (! Storage::disk('public')->exists($path)) {
                    return ['status' => 'failed', 'message' => "configured file not found on public disk: {$key} ({$path})"];
                }
            }

            // 5. The storage root is writable.
            if (! is_writable($root)) {
                return ['status' => 'failed', 'message' => "storage root is not writable ({$root})"];
            }

            return ['status' => 'ok', 'message' => 'public disk, symlink and writability verified'];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'message' => 'storage check error: '.$e->getMessage()];
        }
    }

    private function probe(callable $test): string
    {
        try {
            return $test() ? 'ok' : 'down';
        } catch (Throwable) {
            return 'down';
        }
    }
}
