<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\PinflLookup;
use App\Services\PinflLookupService;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/** JSHSHIR xizmatini klinikaning O'Z shartnomasi bo'yicha ulash va yoqish. */
class PinflSettingsController extends Controller
{
    public function edit()
    {
        $clinic = Clinic::findOrFail(Tenant::id());
        $month = PinflLookup::where('created_at', '>=', now()->startOfMonth());

        return view('app.pinfl-settings', [
            'clinic' => $clinic,
            'stats' => ['ok' => (clone $month)->where('status', 'ok')->count(), 'nf' => (clone $month)->where('status', 'not_found')->count(), 'err' => (clone $month)->where('status', 'error')->count()],
            'hasSecret' => (bool) $clinic->setting('pinfl_secret'),
            'map' => (array) $clinic->setting('pinfl_map', []),
        ]);
    }

    public function update(Request $r)
    {
        $clinic = Clinic::findOrFail(Tenant::id());
        $d = $r->validate([
            'pinfl_url' => 'nullable|string|max:500',
            'pinfl_method' => 'required|in:GET,POST',
            'pinfl_auth' => 'required|in:none,bearer,header,basic,query',
            'pinfl_auth_name' => 'nullable|string|max:100',
            'pinfl_secret' => 'nullable|string|max:500',
            'pinfl_body' => 'nullable|string|max:1000',
            'pinfl_root' => 'nullable|string|max:100',
            'map' => 'nullable|array',
            'map.*' => 'nullable|string|max:100',
        ]);
        $enable = $r->boolean('pinfl_enabled');
        if ($enable && empty($d['pinfl_url'])) {
            return back()->withInput()->withErrors(['pinfl_url' => __('Xizmatni yoqish uchun API manzilini kiriting.')]);
        }
        if (! empty($d['pinfl_url'])) {
            try {
                PinflLookupService::assertSafeUrl(str_replace('{pinfl}', '00000000000000', $d['pinfl_url']));
            } catch (\InvalidArgumentException $e) {
                return back()->withInput()->withErrors(['pinfl_url' => $e->getMessage()]);
            }
        }
        if (! empty($d['pinfl_body']) && ! is_array(json_decode(str_replace('{pinfl}', '0', $d['pinfl_body']), true))) {
            return back()->withInput()->withErrors(['pinfl_body' => __("So'rov tanasi (body) to'g'ri JSON bo'lishi kerak.")]);
        }

        $s = $clinic->settings ?? [];
        $s['pinfl_enabled'] = $enable;
        foreach (['pinfl_url', 'pinfl_method', 'pinfl_auth', 'pinfl_auth_name', 'pinfl_body', 'pinfl_root'] as $k) {
            $s[$k] = $d[$k] ?? null;
        }
        if (! empty($d['pinfl_secret'])) {
            $s['pinfl_secret'] = Crypt::encryptString($d['pinfl_secret']);
        }
        $s['pinfl_map'] = collect($d['map'] ?? [])->only(PinflLookupService::FIELDS)->map(fn ($v) => trim((string) $v))->all();
        $clinic->update(['settings' => $s]);

        return redirect()->route('app.pinfl.edit')->with('success', __('JSHSHIR xizmati sozlamalari saqlandi.'));
    }

    /** Sinov: ulanish va maydonlar xaritasini tekshirish. */
    public function test(Request $r)
    {
        $clinic = Clinic::findOrFail(Tenant::id());
        $d = $r->validate(['test_pinfl' => 'required|digits:14']);
        try {
            $res = PinflLookupService::fetch($clinic, $d['test_pinfl']);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', __('Sinov muvaffaqiyatsiz: :e', ['e' => $e->getMessage()]));
        }

        return back()->withInput()->with('pinfl_test', $res);
    }
}
