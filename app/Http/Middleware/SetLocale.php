<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try getting locale from header, optionally session or query parameter
        $locale = $request->header('Accept-Language');

        // You might want to strip quality values (e.g. 'en-US,en;q=0.9')
        if ($locale) {
             // Simple extraction, take first 2 chars or handle full tag
             $locale = substr($locale, 0, 2);
        }

        if (! $locale || ! in_array($locale, ['en', 'tr'])) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
