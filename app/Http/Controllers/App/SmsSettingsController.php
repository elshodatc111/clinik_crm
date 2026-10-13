<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\SmsLog;
use App\Services\SmsService;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/** Har bir klinika bemorlarga SMS yuborish uchun O'Z Eskiz akkauntini ulaydi. */
class SmsSettingsController extends Controller
{
    public const DEFAULT_TPL = 'Hurmatli :name, :date da :clinic klinikasida :doctor qabuliga yozilgansiz. Manzil: :address';

    public function edit()
    {
        $clinic = Clinic::findOrFail(Tenant::id());

        return view('app.sms-settings', [
            'clinic' => $clinic,
            'connected' => (bool) SmsService::clinicCreds($clinic->id),
            'tpl' => $clinic->setting('sms_tpl_reminder') ?: self::DEFAULT_TPL,
        ]);
    }

    public function update(Request $r)
    {
        $clinic = Clinic::findOrFail(Tenant::id());
        $d = $r->validate([
            'eskiz_email' => 'nullable|email|max:150',
            'eskiz_password' => 'nullable|string|max:150',
            'eskiz_from' => 'nullable|string|max:20',
            'sms_tpl_reminder' => 'nullable|string|max:300',
        ]);
        $s = $clinic->settings ?? [];
        $s['eskiz_email'] = $d['eskiz_email'] ?? null;
        $s['eskiz_from'] = $d['eskiz_from'] ?? null;
        if (! empty($d['eskiz_password'])) {
            $s['eskiz_password'] = Crypt::encryptString($d['eskiz_password']);
        }
        if (empty($s['eskiz_email'])) { // ajratib qo'yish
            $s['eskiz_password'] = null;
        }
        $s['sms_tpl_reminder'] = $d['sms_tpl_reminder'] ?? null;
        foreach (['sms_remind_24h', 'sms_remind_2h', 'sms_on_create', 'sms_lab_ready'] as $k) {
            $s[$k] = $r->boolean($k);
        }
        $clinic->update(['settings' => $s]);

        $msg = __('SMS sozlamalari saqlandi.');
        if ($creds = SmsService::clinicCreds($clinic->id)) {
            if ($err = SmsService::checkLogin($creds)) {
                return redirect()->route('app.sms.settings')->with('error', __("Eskiz akkauntiga kirib bo'lmadi: :e", ['e' => $err]));
            }
            $msg .= ' '.__('Eskiz akkaunti muvaffaqiyatli ulandi.');
        }

        return redirect()->route('app.sms.settings')->with('success', $msg);
    }

    public function test(Request $r)
    {
        $clinic = Clinic::findOrFail(Tenant::id());
        $d = $r->validate(['test_phone' => 'required|string|max:20']);
        if (! SmsService::clinicCreds($clinic->id)) {
            return back()->with('error', __('Avval Eskiz akkauntini ulang.'));
        }
        $ok = SmsService::send($d['test_phone'], $clinic->name.': '.__('SMS sinov xabari.'), $clinic->id, true);
        $last = SmsLog::where('clinic_id', $clinic->id)->latest('id')->first();

        return back()->with($ok ? 'success' : 'error', $ok ? __('Sinov SMS yuborildi.') : __("Yuborilmadi: :e", ['e' => mb_substr((string) $last?->response, 0, 200)]));
    }
}
