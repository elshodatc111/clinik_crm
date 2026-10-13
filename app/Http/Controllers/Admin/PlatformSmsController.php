<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Services\PlatformSms;
use App\Services\SmsService;
use App\Support\SmsEvents;
use Illuminate\Http\Request;

/** Super Admin: platforma SMS matnlari, Eskiz tasdiqlash holati, yoqish/o'chirish, sinov. */
class PlatformSmsController extends Controller
{
    protected function event(string $e): string
    {
        abort_unless(isset(PlatformSms::all()[$e]), 404);

        return $e;
    }

    public function index()
    {
        $events = [];
        foreach (PlatformSms::all() as $k => $def) {
            $events[$k] = $def + ['state' => PlatformSms::get($k)];
        }
        $since = now()->subDays(30);
        $logs = SmsLog::where(fn ($q) => $q->where('event', 'like', 'p\_%')->orWhere('event', 'otp'))->where('created_at', '>=', $since);

        return view('admin.sms-settings', [
            'events' => $events, 'sample' => PlatformSms::sample(),
            'enabled' => PlatformSms::globalEnabled(), 'needApproval' => PlatformSms::needApproval(),
            'connected' => (bool) SmsService::platformCreds(), 'from' => Setting::get('eskiz_from', '4546'),
            'stats' => [
                'sent' => (clone $logs)->where('status', 'sent')->count(),
                'failed' => (clone $logs)->where('status', 'failed')->count(),
                'skipped' => (clone $logs)->where('status', 'skipped')->count(),
            ],
            'recent' => SmsLog::where(fn ($q) => $q->where('event', 'like', 'p\_%')->orWhere('event', 'otp'))->latest('id')->limit(8)->get(),
        ]);
    }

    public function settings(Request $r)
    {
        Setting::set('platform_sms_off', $r->boolean('enabled') ? '' : '1');
        Setting::set('platform_sms_need_approval', $r->boolean('need_approval') ? '1' : '');

        return back()->with('success', $r->boolean('enabled') ? __('Platforma SMS xizmati yoqildi.') : __("Platforma SMS xizmati o'chirildi (tasdiqlash kodidan tashqari)."));
    }

    public function connection()
    {
        $creds = SmsService::platformCreds();
        if (! $creds) {
            return back()->with('error', __('Avval Sozlamalarda Eskiz email va parolini kiriting.'));
        }
        $err = SmsService::checkLogin($creds);

        return back()->with($err ? 'error' : 'success', $err ? __("Eskiz akkauntiga kirib bo'lmadi: :e", ['e' => $err]) : __('Eskiz bilan aloqa yaxshi. Akkaunt ulangan.'));
    }

    public function save(Request $r, string $event)
    {
        $event = $this->event($event);
        $d = $r->validate(['text' => 'required|string|max:480']);
        $text = trim($d['text']);
        if ($event === 'otp' && ! str_contains($text, '{code}')) {
            return back()->withInput()->with('error', __("Tasdiqlash kodi matnida {code} bo'lishi shart."));
        }
        $cur = PlatformSms::get($event);
        $data = ['text' => $text];
        if (! (PlatformSms::all()[$event]['locked'] ?? false)) {
            $data['enabled'] = $r->boolean('enabled');
        }
        if ($text !== trim((string) $cur['approved_text']) && $cur['status'] !== 'draft') {
            $data += ['status' => 'draft', 'eskiz_id' => null, 'note' => null, 'checked_at' => null];
        }
        PlatformSms::patch($event, $data);

        return back()->with('success', __('Shablon saqlandi.').(isset($data['status']) ? ' '.__("Matn o'zgargani uchun uni Eskizga qayta yuborib tasdiqlating.") : ''));
    }

    public function reset(string $event)
    {
        PlatformSms::reset($this->event($event));

        return back()->with('success', __('Standart matn tiklandi.'));
    }

    public function submit(Request $r, string $event)
    {
        $event = $this->event($event);
        $creds = SmsService::platformCreds();
        if (! $creds) {
            return back()->with('error', __('Avval Sozlamalarda Eskiz email va parolini kiriting.'));
        }
        $text = trim((string) ($r->input('text') ?: PlatformSms::get($event)['text']));
        PlatformSms::patch($event, ['text' => $text]);
        $res = SmsService::submitTemplate($creds, $text);
        if (! $res['ok']) {
            return back()->with('error', __("Eskizga yuborib bo'lmadi: :e", ['e' => $res['message'] ?: '—']));
        }
        PlatformSms::patch($event, ['status' => 'moderation', 'eskiz_id' => $res['id'], 'approved_text' => null, 'note' => null, 'checked_at' => now()->toDateTimeString()]);

        return back()->with('success', __('Shablon Eskizga yuborildi. Tasdiqlanishini kuting (odatda bir necha soat), keyin "Tekshirish" tugmasini bosing.'));
    }

    public function check(string $event)
    {
        $event = $this->event($event);
        $creds = SmsService::platformCreds();
        if (! $creds) {
            return back()->with('error', __('Avval Sozlamalarda Eskiz email va parolini kiriting.'));
        }
        $res = SmsService::fetchTemplates($creds);
        if (! $res['ok']) {
            return back()->with('error', __("Eskizdan holatni olib bo'lmadi: :e", ['e' => $res['message'] ?: '—']));
        }
        $st = PlatformSms::get($event);
        $norm = fn ($t) => preg_replace('/\s+/u', ' ', trim((string) $t));
        $row = collect($res['rows'])->first(fn ($x) => $st['eskiz_id'] && (int) data_get($x, 'id') === (int) $st['eskiz_id'])
            ?? collect($res['rows'])->first(fn ($x) => $norm(data_get($x, 'template') ?? data_get($x, 'original_text')) === $norm($st['text']));
        if (! $row) {
            return back()->with('error', __('Bu shablon Eskiz ro\'yxatida topilmadi. Avval "Eskizga yuborish" tugmasini bosing.'));
        }
        $new = SmsService::mapTemplateStatus(data_get($row, 'status'));
        PlatformSms::patch($event, [
            'status' => $new, 'eskiz_id' => data_get($row, 'id') ?: $st['eskiz_id'], 'checked_at' => now()->toDateTimeString(),
            'approved_text' => $new === 'approved' ? $st['text'] : null,
            'note' => $new === 'rejected' ? (string) (data_get($row, 'reason') ?? data_get($row, 'comment') ?? '') : null,
        ]);

        return back()->with($new === 'rejected' ? 'error' : 'success', __('Eskiz holati: :s', ['s' => __(SmsEvents::statusLabel($new))]));
    }

    public function mark(string $event)
    {
        $event = $this->event($event);
        PlatformSms::patch($event, ['status' => 'approved', 'approved_text' => PlatformSms::get($event)['text'], 'checked_at' => now()->toDateTimeString(), 'note' => null]);

        return back()->with('success', __('Shablon tasdiqlangan deb belgilandi.'));
    }

    public function test(Request $r)
    {
        $d = $r->validate(['test_phone' => 'required|string|max:20', 'event' => 'nullable|string']);
        if (! SmsService::platformCreds()) {
            return back()->with('error', __('Avval Sozlamalarda Eskiz email va parolini kiriting.'));
        }
        $ev = $d['event'] ?? null;
        $text = isset(PlatformSms::all()[$ev ?? '']) ? PlatformSms::render($ev, PlatformSms::sample()) : \App\Services\BillingService::appName().': '.__('SMS sinov xabari.');
        $ok = SmsService::send($d['test_phone'], $text, null, false, 'test');
        $last = SmsLog::latest('id')->first();

        return back()->with($ok ? 'success' : 'error', $ok ? __('Sinov SMS yuborildi.') : __('Yuborilmadi: :e', ['e' => mb_substr((string) $last?->response, 0, 200)]));
    }
}
