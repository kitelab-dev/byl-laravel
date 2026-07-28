<?php

namespace Byl\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Эрхтэй захиалгагүй хэрэглэгчийг зогсооно.
 *
 * ```php
 * Route::get('/dashboard', ...)->middleware('byl.subscribed');
 * Route::get('/pro', ...)->middleware('byl.subscribed:growth_monthly');
 * ```
 */
class EnsureSubscribed
{
    public function handle(Request $request, Closure $next, ?string $price = null): Response
    {
        $billable = $request->user();

        if ($billable === null || ! method_exists($billable, 'subscribed') || ! $billable->subscribed($price)) {
            return $this->deny($request);
        }

        return $next($request);
    }

    protected function deny(Request $request): Response
    {
        $redirect = config('byl.billable.redirect_to');

        if ($redirect === null) {
            abort(403, 'Энэ хуудсанд хандахын тулд захиалга шаардлагатай.');
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Энэ хуудсанд хандахын тулд захиалга шаардлагатай.'], 403)
            : redirect()->to($redirect);
    }
}
