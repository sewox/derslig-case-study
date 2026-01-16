<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;

class ConfigurationService
{
    public function get(string $key, mixed $default = null): string|int|float|null
    {
        // Cache for 24 hours (1440 minutes), clear on update
        return Cache::remember("config:{$key}", now()->addDay(), function () use ($key, $default) {
            $config = Configuration::where('key', $key)->first();
            return $config ? $config->value : $default;
        });
    }

    public function getInt(string $key, int $default): int
    {
        return (int) $this->get($key, $default);
    }

    public function getFloat(string $key, float $default): float
    {
        return (float) $this->get($key, $default);
    }

    public function getString(string $key, string $default): string
    {
        return (string) $this->get($key, $default);
    }
}
