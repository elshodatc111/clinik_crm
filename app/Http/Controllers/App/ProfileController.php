<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Services\SmsService;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /** Klinika sozlamalari JSON ichida saqlanadigan maydonlar */
    public const EXTRA = ['phone2', 'website', 'telegram', 'instagram', 'working_hours', 'inn', 'license_no', 'bank_details', 'invoice_header', 'map_link', 'description'];

    public function edit()
    {
        return view('app.profile', ['clinic' => Clinic::findOrFail(Tenant::id())]);
    }

    public function update(Request $r)
    {
        $clinic = Clinic::findOrFail(Tenant::id());
        $d = $r->validate([
            'name' => 'required|string|max:150',
            'owner_name' => 'required|string|max:150',
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\(\)]{9,20}$/'],
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
            'phone2' => 'nullable|string|max:40',
            'website' => 'nullable|string|max:150',
            'telegram' => 'nullable|string|max:150',
            'instagram' => 'nullable|string|max:150',
            'working_hours' => 'nullable|string|max:200',
            'inn' => 'nullable|string|max:20',
            'license_no' => 'nullable|string|max:100',
            'bank_details' => 'nullable|string|max:500',
            'invoice_header' => 'nullable|string|max:500',
            'map_link' => 'nullable|string|max:300',
            'description' => 'nullable|string|max:2000',
            'logo' => 'nullable|image|max:2048',
        ]);

        $phone = SmsService::normalize($d['phone']);
        if (Clinic::where('phone', $phone)->where('id', '!=', $clinic->id)->exists()) {
            return back()->withInput()->withErrors(['phone' => __('Bu telefon raqami boshqa klinikada ishlatilgan.')]);
        }

        $settings = $clinic->settings ?? [];
        foreach (self::EXTRA as $k) {
            $settings[$k] = $d[$k] ?? null;
        }

        $logo = $clinic->logo_path;
        if ($r->boolean('remove_logo') && $logo) {
            Storage::disk('public')->delete($logo);
            $logo = null;
        }
        if ($r->hasFile('logo')) {
            if ($logo) {
                Storage::disk('public')->delete($logo);
            }
            $logo = $r->file('logo')->store('clinics/'.$clinic->id, 'public');
        }

        $clinic->update([
            'name' => $d['name'], 'owner_name' => $d['owner_name'], 'phone' => $phone, 'email' => $d['email'] ?? null,
            'address' => $d['address'] ?? null, 'settings' => $settings, 'logo_path' => $logo,
        ]);

        return redirect()->route('app.profile.edit')->with('success', __('Klinika ma\'lumotlari saqlandi.'));
    }
}
