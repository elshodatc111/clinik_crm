<?php

namespace App\Http\Middleware;

use App\Models\Clinic;
use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Klinika paneliga kirish va obuna holatiga qarab cheklash. */
class ClinicAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $clinicId = Tenant::id();

        if (! $clinicId) {
            return $user->isSuper() ? redirect()->route('admin.dashboard') : abort(403);
        }

        $clinic = Clinic::find($clinicId);
        if (! $clinic || (! $user->isSuper() && ! $user->is_active)) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['login' => __('Akkaunt faol emas.')]);
        }

        view()->share('clinic', $clinic);
        $request->attributes->set('clinic', $clinic);

        // Super Admin hamma holatda ishlay oladi (qo'llab-quvvatlash uchun)
        if ($user->isSuper()) {
            return $next($request);
        }

        $free = $request->routeIs('app.billing*', 'app.export.*');

        if ($clinic->status === Clinic::SUSPENDED && ! $free) {
            return response()->view('app.suspended', [], 403);
        }
        if ($clinic->status === Clinic::BLOCKED && ! $free) {
            return redirect()->route('app.billing')->with('error', __("Klinikangiz bloklangan. Balansni to'ldirib, obunani tiklang."));
        }
        if ($clinic->status === Clinic::VIEW_ONLY && ! $free && ! $request->isMethodSafe()) {
            return back()->with('error', __("Obuna to'lanmagani uchun tizim faqat ko'rish rejimida. Balansni to'ldiring."));
        }

        return $next($request);
    }
}
