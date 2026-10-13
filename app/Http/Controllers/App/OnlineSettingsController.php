<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Onlayn qabul sahifasi va klinikaning O'Z Payme kassasi sozlamalari. */
class OnlineSettingsController extends Controller
{
    public function edit()
    {
        $clinic = Clinic::findOrFail(Tenant::id());
        if (! $clinic->slug) {
            $clinic->slug = $this->freeSlug(Str::slug($clinic->name) ?: 'klinika', $clinic->id);
            $clinic->save();
        }

        return view('app.online-settings', [
            'clinic' => $clinic,
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
            'doctors' => User::clinicDoctors()->get(),
            'links' => DB::table('doctor_service')->get()->groupBy('user_id')->map(fn ($g) => $g->pluck('service_id')->all()),
            'cfg' => fn ($k) => BookingService::cfg($clinic, $k),
            'paymeReady' => BookingService::paymeConfigured($clinic),
        ]);
    }

    protected function freeSlug(string $base, int $exceptId): string
    {
        $slug = $base;
        $i = 2;
        while (Clinic::where('slug', $slug)->where('id', '!=', $exceptId)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function update(Request $r)
    {
        $clinic = Clinic::findOrFail(Tenant::id());
        $d = $r->validate([
            'slug' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique('clinics', 'slug')->ignore($clinic->id), Rule::notIn(['admin', 'app', 'api', 'login', 'register', 'payme', 'offer', 'lang', 'dashboard', 'k'])],
            'online_start' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'online_end' => ['required', 'regex:/^\d{2}:\d{2}$/', 'after:online_start'],
            'online_days' => 'nullable|array', 'online_days.*' => 'integer|between:1,7',
            'online_days_ahead' => 'required|integer|between:1,90',
            'online_lead_min' => 'required|integer|between:0,1440',
            'online_hold_min' => 'required|integer|between:5,60',
            'payme_merchant_id' => 'nullable|string|max:100',
            'payme_key' => 'nullable|string|max:200',
            'payme_test_key' => 'nullable|string|max:200',
            'services' => 'nullable|array', 'services.*' => 'integer',
            'doctors' => 'nullable|array', 'doctors.*' => 'integer',
            'links' => 'nullable|array',
            'booking_offer' => 'nullable|string|max:20000',
        ]);

        $s = $clinic->settings ?? [];
        $s['online_enabled'] = $r->boolean('online_enabled');
        $s['payme_test'] = $r->boolean('payme_test');
        foreach (['online_start', 'online_end', 'online_days_ahead', 'online_lead_min', 'online_hold_min', 'payme_merchant_id'] as $k) {
            $s[$k] = $d[$k] ?? null;
        }
        $s['booking_offer'] = trim((string) ($d['booking_offer'] ?? '')) ?: null;
        $s['online_days'] = array_map('intval', $d['online_days'] ?? []);
        foreach (['payme_key', 'payme_test_key'] as $k) {
            if (! empty($d[$k])) {
                $s[$k] = Crypt::encryptString($d[$k]);
            }
        }
        $clinic->update(['slug' => $d['slug'], 'settings' => $s]);

        // Xizmatlar va shifokorlar (faqat shu klinikaniki)
        $svcIds = Service::pluck('id')->all();
        Service::whereIn('id', $svcIds)->update(['online_enabled' => false]);
        Service::whereIn('id', array_intersect($d['services'] ?? [], $svcIds))->update(['online_enabled' => true]);

        $docIds = User::clinicDoctors()->pluck('id')->all();
        User::whereIn('id', $docIds)->update(['online_enabled' => false]);
        User::whereIn('id', array_intersect($d['doctors'] ?? [], $docIds))->update(['online_enabled' => true]);

        DB::table('doctor_service')->whereIn('user_id', $docIds)->delete();
        foreach (($d['links'] ?? []) as $docId => $sids) {
            if (! in_array((int) $docId, $docIds, true)) {
                continue;
            }
            foreach (array_intersect(array_map('intval', (array) $sids), $svcIds) as $sid) {
                DB::table('doctor_service')->insertOrIgnore(['user_id' => (int) $docId, 'service_id' => $sid]);
            }
        }

        return redirect()->route('app.online.edit')->with('success', __('Onlayn qabul sozlamalari saqlandi.'));
    }
}
