<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    protected array $plain = ['app_name', 'company_name', 'primary_color', 'support_phone', 'support_phone2', 'support_email', 'contact_address', 'contact_hours', 'contact_telegram', 'contact_instagram', 'contact_map_url', 'setup_fee', 'demo_days', 'demo_grace_days', 'eskiz_email', 'eskiz_from', 'payme_merchant_id'];

    protected array $secret = ['eskiz_password', 'payme_key', 'payme_test_key'];

    public function edit()
    {
        return view('admin.settings', ['s' => Setting::all_()]);
    }

    public function update(Request $r)
    {
        $d = $r->validate([
            'app_name' => 'required|string|max:60',
            'company_name' => 'nullable|string|max:200',
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'support_phone' => 'nullable|string|max:30',
            'support_email' => 'nullable|email|max:100',
            'support_phone2' => 'nullable|string|max:30',
            'contact_address' => 'nullable|string|max:250',
            'contact_hours' => 'nullable|string|max:120',
            'contact_telegram' => 'nullable|string|max:120',
            'contact_instagram' => 'nullable|string|max:120',
            'contact_map_url' => ['nullable', 'url', 'max:600', 'regex:/^https:\/\//'],
            'setup_fee' => 'required|integer|min:0',
            'demo_days' => 'required|integer|min:1|max:90',
            'demo_grace_days' => 'required|integer|min:0|max:30',
            'eskiz_email' => 'nullable|string|max:100',
            'eskiz_from' => 'nullable|string|max:30',
            'payme_merchant_id' => 'nullable|string|max:100',
            'eskiz_password' => 'nullable|string|max:200',
            'payme_key' => 'nullable|string|max:200',
            'payme_test_key' => 'nullable|string|max:200',
            'logo' => 'nullable|image|max:2048',
        ]);

        foreach ($this->plain as $k) {
            Setting::set($k, $d[$k] ?? null);
        }
        foreach ($this->secret as $k) {
            if (! empty($d[$k])) {
                Setting::set($k, $d[$k]);
            }
        }
        Setting::set('payme_test_mode', $r->boolean('payme_test_mode') ? '1' : '');

        if ($r->boolean('remove_logo') && Setting::get('logo_path')) {
            Storage::disk('public')->delete(Setting::get('logo_path'));
            Setting::set('logo_path', null);
        }
        if ($r->hasFile('logo')) {
            if ($old = Setting::get('logo_path')) {
                Storage::disk('public')->delete($old);
            }
            Setting::set('logo_path', $r->file('logo')->store('brand', 'public'));
        }
        \Illuminate\Support\Facades\Cache::forget('eskiz_token');

        return back()->with('success', __('Sozlamalar saqlandi.'));
    }
}
