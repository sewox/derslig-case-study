<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PreventDuplicateRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only valid for idempotent methods (like POST/PUT/PATCH/DELETE) where duplication matters
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        // Create a unique key for the request content
        // user_id + method + url + request_body_hash
        $key = 'duplicate_request_' . $user->id . '_' . Str::slug($request->method() . '_' . $request->path()) . '_' . md5($request->getContent());
        
        // Lock for 5 seconds to prevent rapid double clicks
        if (Cache::has($key)) {
            return response()->json([
                'message' => __('messages.error.duplicate_request') ?? 'Duplicate request detected. Please wait.',
            ], 429);
        }

        Cache::put($key, true, 5); // 5 seconds lock

        return $next($request);
    }
}
