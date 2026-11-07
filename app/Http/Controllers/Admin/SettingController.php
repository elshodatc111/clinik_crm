<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    protected array $plain = ['app_name', 'company_name', 'primary_color', 'support_phone', 'support_phone2', 'support_email', 'contact_address', 'contact_hours', 'contact_telegram', 'contact_instagram', 'contact_map_url', 'card_holder', 'card_bank', 'card_note', 'setup_fee', 'demo_days', 'demo_grace_days', 'eskiz_email', 'eskiz_from', 'payme_merchant_id', 'sms_gateway_url'];

    protected array $secret = ['eskiz_password', 'payme_key', 'payme_test_key', 'sms_gateway_key'];

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
            'card_number' => ['nullable', 'string', 'max:30', function ($a, $v, $fail) {
                $n = preg_replace('/\D/', '', (string) $v);
                if ($n !== '' && strlen($n) !== 16) {
                    $fail(__("Karta raqami 16 ta raqamdan iborat bo'lishi kerak."));
                }
            }],
            'card_holder' => 'nullable|string|max:100',
            'card_bank' => 'nullable|string|max:60',
            'card_note' => 'nullable|string|max:300',
            'setup_fee' => 'required|integer|min:0',
            'demo_days' => 'required|integer|min:1|max:90',
            'demo_grace_days' => 'required|integer|min:0|max:30',
            'eskiz_email' => 'nullable|string|max:100',
            'eskiz_from' => 'nullable|string|max:30',
            'payme_merchant_id' => 'nullable|string|max:100',
            'sms_gateway_url' => ['nullable', 'url', 'max:200', 'regex:/^https?:\/\//'],
            'sms_gateway_key' => 'nullable|string|max:200',
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
        Setting::set('card_number', preg_replace('/\D/', '', (string) ($d['card_number'] ?? '')));
        Setting::set('card_enabled', $r->boolean('card_enabled') ? '1' : '0');
        Setting::set('payme_test_mode', $r->boolean('payme_test_mode') ? '1' : '');
        Setting::set('sms_gateway_on', $r->boolean('sms_gateway_on') ? '1' : '0');
        if ($r->boolean('forget_gateway_key')) {
            Setting::set('sms_gateway_key', null);
        }

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

    /** Platforma Telegram boti: ulash / uzish / yoqish-o'chirish / tekshirish. */
    public function telegram(Request $r)
    {
        $d = $r->validate([
            'action' => 'required|in:connect,disconnect,toggle,check',
            'tg_bot_token' => ['nullable', 'string', 'max:200', 'regex:/^\d{6,}:[A-Za-z0-9_-]{20,}$/'],
        ]);

        if ($d['action'] === 'toggle') {
            Setting::set('tg_otp_enabled', $r->boolean('tg_otp_enabled') ? '1' : '0');

            return back()->with('success', __('Saqlandi.'));
        }

        if ($d['action'] === 'disconnect') {
            if ($api = \App\Services\TelegramOtp::api()) {
                $api->deleteWebhook();
            }
            Setting::set('tg_bot_status', null);
            Setting::set('tg_bot_username', null);
            Setting::set('tg_bot_token', null);

            return back()->with('success', __('Telegram bot uzildi.'));
        }

        if ($d['action'] === 'check') {
            $api = \App\Services\TelegramOtp::api();
            if (! $api) {
                return back()->with('error', __('Bot ulanmagan.'));
            }
            $i = $api->webhookInfo();
            if (! ($i['ok'] ?? false)) {
                return back()->with('error', __('Telegram javob bermadi: :e', ['e' => $i['description'] ?? '?']));
            }
            $r2 = (array) $i['result'];
            $msg = __('Webhook: :u. Kutilayotgan xabarlar: :n.', ['u' => $r2['url'] ?: __("o'rnatilmagan"), 'n' => (int) ($r2['pending_update_count'] ?? 0)]);
            if (! empty($r2['last_error_message'])) {
                return back()->with('error', $msg.' '.__('Oxirgi xato: :e', ['e' => $r2['last_error_message']]));
            }

            return back()->with('success', $msg);
        }

        // connect
        $token = $d['tg_bot_token'] ?: \App\Services\TelegramOtp::token();
        if (! $token) {
            return back()->with('error', __("Bot tokenini kiriting (@BotFather'dan olinadi)."));
        }
        $api = new \App\Services\TelegramApi($token);
        $me = $api->getMe();
        if (! ($me['ok'] ?? false) || empty($me['result']['username'])) {
            return back()->with('error', __("Token noto'g'ri yoki Telegram javob bermadi: :e", ['e' => $me['description'] ?? '?']));
        }
        // Telegram webhook faqat HTTPS'da ishlaydi; TLS proksi ortida route() http qaytarishi mumkin
        $url = preg_replace('#^http://#', 'https://', route('telegram.webhook'));
        if (preg_match('#^https://(localhost|127\.|\[::1\])#', $url)) {
            return back()->with('error', __('Webhook uchun ommaviy HTTPS domen kerak (localhost bo\'lmaydi): :u', ['u' => $url]));
        }
        $hook = $api->setWebhook($url, \App\Services\TelegramOtp::webhookSecret());
        if (! ($hook['ok'] ?? false)) {
            return back()->with('error', __('Webhook o\'rnatilmadi: :e', ['e' => $hook['description'] ?? '?']));
        }
        Setting::set('tg_bot_token', $token);
        Setting::set('tg_bot_username', $me['result']['username']);
        Setting::set('tg_bot_status', 'ok');
        if (Setting::get('tg_otp_enabled') === null) {
            Setting::set('tg_otp_enabled', '1');
        }

        return back()->with('success', __('Bot ulandi: @:u. Webhook o\'rnatildi.', ['u' => $me['result']['username']]));
    }

    public function gatewayCheck()
    {
        if (! \App\Services\GatewaySms::enabled()) {
            return back()->with('error', __('SMS Gateway kaliti kiritilmagan yoki o\'chirilgan.'));
        }
        $h = \App\Services\GatewaySms::health();
        if ($h === null) {
            return back()->with('error', __('SMS Gateway javob bermayapti: manzilni tekshiring.'));
        }
        $n = (int) ($h['modems_usable'] ?? 0);

        return back()->with($n > 0 ? 'success' : 'error', $n > 0
            ? __('SMS Gateway ishlayapti: tayyor modemlar :n ta.', ['n' => $n])
            : __("Gateway ulangan, lekin tayyor modem yo'q (holat: :s). SMS Eskiz orqali ketadi.", ['s' => $h['status'] ?? '?']));
    }
}
