<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\Setting;

class SettingsService
{
    /** @var array<string,mixed>|null */
    private ?array $cache = null;

    /** @return array<string,mixed> */
    public function all(): array
    {
        return $this->cache ??= Setting::pluck('value', 'key')->all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        $this->cache = null;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function float(string $key, float $default = 0.0): float
    {
        return (float) ($this->get($key) ?? $default);
    }

    /** @return array<string,mixed> */
    public function json(string $key, array $default = []): array
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        return json_decode((string) $value, true) ?: $default;
    }
}