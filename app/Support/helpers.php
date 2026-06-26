<?php

use App\Support\Money;
use App\Support\Settings;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    /**
     * Read an application setting from the DB-backed (cached) Settings service.
     * The single source of truth for site name, branding, contact info, etc.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(Settings::class)->get($key, $default);
    }
}

if (! function_exists('setting_file_url')) {
    /**
     * Resolve a setting that stores a file path (logo / favicon) to a public URL.
     * Returns null when unset, or the value as-is if it is already an absolute URL.
     */
    function setting_file_url(string $key): ?string
    {
        $path = setting($key);

        if (blank($path)) {
            return null;
        }

        if (str_starts_with((string) $path, 'http')) {
            return (string) $path;
        }

        return Storage::disk('public')->url($path);
    }
}

if (! function_exists('money')) {
    /**
     * Format a monetary amount with a currency symbol.
     *
     * Single source of truth for money formatting across the whole app —
     * never call number_format() directly in views or resources.
     *
     * Example: money(15000) => "15,000.00 ر.س"
     */
    function money(int|float|string|null $amount, string $currency = 'SAR'): string
    {
        return Money::format($amount, $currency);
    }
}
