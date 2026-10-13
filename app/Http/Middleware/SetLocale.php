<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = session('locale') ?: ($request->user()?->locale ?: 'uz');
        app()->setLocale(in_array($locale, ['uz', 'ru'], true) ? $locale : 'uz');

        return $next($request);
    }
}
