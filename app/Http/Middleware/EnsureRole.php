<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();
        abort_unless($user, 403);

        if (in_array('super_admin', $roles, true)) {
            abort_unless($user->isSuper(), 403);

            return $next($request);
        }

        abort_unless($user->hasRole(...$roles), 403, __("Bu bo'limga kirish huquqingiz yo'q."));

        return $next($request);
    }
}
