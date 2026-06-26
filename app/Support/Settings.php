<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Database-backed application settings (mail, general, …). Values are cached and
 * the whole store is extensible — new settings are simply new keys. Sensitive
 * keys (e.g. the SMTP password) are encrypted at rest.
 */
class Settings
{
    private const CACHE_KEY = 'app.settings';

    /**
     * Keys whose values are encrypted at rest.
     *
     * @var array<int, string>
     */
    private const ENCRYPTED = [
        'mail.password',
        'payments.moyasar.api_key',
        'payments.moyasar.secret_key',
        'payments.hyperpay.access_token',
        'payments.tap.secret_key',
        'payments.stripe.secret_key',
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        if (in_array($key, self::ENCRYPTED, true)) {
            try {
                return Crypt::decryptString($value);
            } catch (Throwable) {
                return $default;
            }
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $stored = ($value !== null && in_array($key, self::ENCRYPTED, true))
            ? Crypt::encryptString((string) $value)
            : $value;

        Setting::updateOrCreate(['key' => $key], ['value' => $stored]);
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Persist many settings at once.
     *
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Raw key/value map (cached). Safe before the table exists (migrations).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => Setting::pluck('value', 'key')->all());
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Drop the cached settings map so the next read re-loads from the database.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
