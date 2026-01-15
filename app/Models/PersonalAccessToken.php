<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PersonalAccessToken extends SanctumPersonalAccessToken
{

    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'tokenable_id',
        'tokenable_type',
    ];

    protected $casts = [
        'abilities' => 'json',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * Find the token instance matching the given token.
     *
     * @param  string  $token
     * @return static|null
     */
    public static function findToken($token)
    {
        $hashedToken = hash('sha256', $token);
        $cacheKey = "sanctum_token:{$hashedToken}";

        // Try to get from Redis
        $cachedData = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if ($cachedData) {
            $model = new static;
            $model->forceFill($cachedData);
            $model->exists = true;
            return $model;
        }

        // Fallback to DB
        if (strpos($token, '|') === false) {
            $item = static::where('token', $hashedToken)->first();
        } else {
            [$id, $token] = explode('|', $token, 2);
            $hash = hash('sha256', $token);

            $item = static::where('token', $hash)->find($id);
        }

        // Cache if found
        if ($item) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, $item->attributesToArray(), now()->addDay());
        }

        return $item;
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Clear cache on update or delete
        static::saved(function ($token) {
            $cacheKey = "sanctum_token:{$token->token}";
            \Illuminate\Support\Facades\Cache::put($cacheKey, $token->attributesToArray(), now()->addDay());
        });

        static::deleted(function ($token) {
            $cacheKey = "sanctum_token:{$token->token}";
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
        });
    }

    /**
     * Get the tokenable model that the access token belongs to.
     */
    public function tokenable()
    {
        return $this->morphTo('tokenable');
    }
