<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $fillable = ['key', 'value', 'description'];

    protected static function booted(): void
    {
        static::saved(function ($config) {
            \Illuminate\Support\Facades\Cache::forget("config:{$config->key}");
        });

        static::deleted(function ($config) {
            \Illuminate\Support\Facades\Cache::forget("config:{$config->key}");
        });
    }
}
