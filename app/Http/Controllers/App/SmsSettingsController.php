<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\SmsLog;
use App\Services\Notify;
use App\Services\SmsService;
use App\Support\SmsEvents;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/** Har bir klinika bemorlarga SMS yuborish uchun O'Z Eskiz akkauntini va shablonlarini boshqaradi. */
class SmsSettingsController extends Controller
{
    protected function clinic(): Clinic
    {
        return Clinic::findOrFail(Tenant::id());
    }

    protected function event(string $event): string
    {
        abort_unless(isset(SmsEvents::all()[$event]), 404);

        return $event;
    }

    /** Bitta hodisa shablonining holatini yangilash. */
    protected function patch(Clinic $c, string $event, array $data): void
    {
        $s = $c->settings ?? [];
        $cur = (array) data_get($s, 'sms_tpls.'.$event, []);
        $s['sms_tpls'][$event] = array_merge($cur, $data);
        $c->update(['settings' => $s]);
    }

    public function edit()
    {
        $c = $this->clinic();
        $events = [];
        foreach (SmsEvents::all() as $k => $def) {
            $events[$k] = $def + ['state' => SmsEvents::get($c, $k)];
        }
        $since = now()->subDays(30);

        return view('app.sms-settings', [
            'clinic' => $c,
            'connected' => (bool) SmsService::clinicCreds($c->id),
            'enabled' => $c->setting('sms_enabled', true) !== false,
            'needApproval' => (bool) $c->setting('sms_need_approval', true),
            'events' => $events,
            'sample' => SmsEvents::sample(),
            'stats' => [
                'sent' => SmsLog::where('clinic_id', $c->id)->where('status', 'sent')->where('created_at', '>=', $since)->count(),
                'failed' => SmsLog::where('clinic_id', $c->id)->where('status', 'failed')->where('created_at', '>=', $since)->count(),
                'skipped' => SmsLog::where('clinic_id', $c->id)->where('status', 'skipped')->where('created_at', '>=', $since)->count(),
            ],
        ]);
    }

    /** Eskiz akkaunti va umumiy sozlamalar. */
    public function update(Request $r)
    {
        $c = $this->clinic();
        $d = $r->validate([
            'eskiz_email' => 'nullable|email|max:150',
            'eskiz_password' => 'nullable|string|max:150',
            'eskiz_from' => 'nullable|string|max:20',
        ]);
        $s = $c->settings ?? [];
        $s['eskiz_email'] = $d['eskiz_email'] ?? null;
        $s['eskiz_from'] = $d['eskiz_from'] ?? null;
        if (! empty($d['eskiz_password'])) {
            $s['eskiz_password'] = Crypt::encryptString($d['eskiz_password']);
        }
        if (empty($s['eskiz_email'])) { // ajratib qo'yish
            $s['eskiz_password'] = null;
        }
        $s['sms_need_approval'] = $r->boolean('sms_need_approval');
        $c->update(['settings' => $s]);

        $msg = __('SMS sozlamalari saqlandi.');
        if ($creds = SmsService::clinicCreds($c->id)) {
            if ($err = SmsService::checkLogin($creds)) {
                return redirect()->route('app.sms.settings')->with('error', __("Eskiz akkauntiga kirib bo'lmadi: :e", ['e' => $err]));
            }
            $msg .= ' '.__('Eskiz akkaunti muvaffaqiyatli ulandi.');
        }

        return redirect()->route('app.sms.settings')->with('success', $msg);
    }

    /** SMS xizmatini butunlay o'chirish / qayta yoqish. */
    public function toggle(Request $r)
    {
        $c = $this->clinic();
        $s = $c->settings ?? [];
        $s['sms_enabled'] = $r->boolean('enabled');
        $c->update(['settings' => $s]);

        return back()->with('success', $s['sms_enabled'] ? __('SMS xizmati yoqildi.') : __("SMS xizmati o'chirildi. Bemorlarga SMS yuborilmaydi."));
    }

    public function connection()
    {
        $c = $this->clinic();
        $creds = SmsService::clinicCreds($c->id);
        if (! $creds) {
            return back()->with('error', __('Avval Eskiz akkauntini ulang.'));
        }
        $err = SmsService::checkLogin($creds);

        return back()->with($err ? 'error' : 'success', $err ? __("Eskiz akkauntiga kirib bo'lmadi: :e", ['e' => $err]) : __('Eskiz bilan aloqa yaxshi. Akkaunt ulangan.'));
    }

    /** Shablon matni va yoqilganligini saqlash. Matn o'zgarsa, tasdiq bekor bo'ladi. */
    public function save(Request $r, string $event)
    {
        $c = $this->clinic();
        $event = $this->event($event);
        $d = $r->validate(['text' => 'required|string|max:480']);
        $cur = SmsEvents::get($c, $event);
        $data = ['text' => trim($d['text']), 'enabled' => $r->boolean('enabled')];
        if (trim($d['text']) !== trim((string) $cur['approved_text']) && $cur['status'] !== 'draft') {
            $data += ['status' => 'draft', 'eskiz_id' => null, 'note' => null, 'checked_at' => null];
        }
        $this->patch($c, $event, $data);

        return back()->with('success', __('Shablon saqlandi.').(isset($data['status']) ? ' '.__('Matn o\'zgargani uchun uni Eskizga qayta yuborib tasdiqlating.') : ''));
    }

    /** Shablonni Eskiz moderatsiyasiga yuborish. */
    public function submit(Request $r, string $event)
    {
        $c = $this->clinic();
        $event = $this->event($event);
        $creds = SmsService::clinicCreds($c->id);
        if (! $creds) {
            return back()->with('error', __('Avval Eskiz akkauntini ulang.'));
        }
        $text = trim((string) ($r->input('text') ?: SmsEvents::get($c, $event)['text']));
        $this->patch($c, $event, ['text' => $text]);
        $res = SmsService::submitTemplate($creds, $text);
        if (! $res['ok']) {
            return back()->with('error', __("Eskizga yuborib bo'lmadi: :e", ['e' => $res['message'] ?: '—']));
        }
        $this->patch($c, $event, ['status' => 'moderation', 'eskiz_id' => $res['id'], 'approved_text' => null, 'note' => null, 'checked_at' => now()->toDateTimeString()]);

        return back()->with('success', __('Shablon Eskizga yuborildi. Tasdiqlanishini kuting (odatda bir necha soat), keyin "Tekshirish" tugmasini bosing.'));
    }

    /** Eskizdan shablon holatini so'rash. */
    public function check(string $event)
    {
        $c = $this->clinic();
        $event = $this->event($event);
        $creds = SmsService::clinicCreds($c->id);
        if (! $creds) {
            return back()->with('error', __('Avval Eskiz akkauntini ulang.'));
        }
        $res = SmsService::fetchTemplates($creds);
        if (! $res['ok']) {
            return back()->with('error', __("Eskizdan holatni olib bo'lmadi: :e", ['e' => $res['message'] ?: '—']));
        }
        $st = SmsEvents::get($c, $event);
        $norm = fn ($t) => preg_replace('/\s+/u', ' ', trim((string) $t));
        $row = collect($res['rows'])->first(fn ($x) => $st['eskiz_id'] && (int) data_get($x, 'id') === (int) $st['eskiz_id'])
            ?? collect($res['rows'])->first(fn ($x) => $norm(data_get($x, 'template') ?? data_get($x, 'original_text')) === $norm($st['text']));
        if (! $row) {
            return back()->with('error', __('Bu shablon Eskiz ro\'yxatida topilmadi. Avval "Eskizga yuborish" tugmasini bosing.'));
        }
        $new = SmsService::mapTemplateStatus(data_get($row, 'status'));
        $this->patch($c, $event, [
            'status' => $new, 'eskiz_id' => data_get($row, 'id') ?: $st['eskiz_id'], 'checked_at' => now()->toDateTimeString(),
            'approved_text' => $new === 'approved' ? $st['text'] : null,
            'note' => $new === 'rejected' ? (string) (data_get($row, 'reason') ?? data_get($row, 'comment') ?? '') : null,
        ]);
        if ($new !== $st['status'] && in_array($new, ['approved', 'rejected'], true)) {
            Notify::toAdmins($c->id, $new === 'approved' ? __('SMS shabloni tasdiqlandi') : __('SMS shabloni rad etildi'), __(SmsEvents::all()[$event]['label']), $new === 'approved' ? 'success' : 'danger', route('app.sms.settings'), 'sms');
        }

        return back()->with($new === 'approved' ? 'success' : ($new === 'rejected' ? 'error' : 'success'), __('Eskiz holati: :s', ['s' => __(SmsEvents::statusLabel($new))]));
    }

    /** Eskiz kabinetida allaqachon tasdiqlangan bo'lsa, qo'lda belgilash. */
    public function mark(Request $r, string $event)
    {
        $c = $this->clinic();
        $event = $this->event($event);
        $st = SmsEvents::get($c, $event);
        $this->patch($c, $event, ['status' => 'approved', 'approved_text' => $st['text'], 'checked_at' => now()->toDateTimeString(), 'note' => null]);

        return back()->with('success', __("Shablon tasdiqlangan deb belgilandi."));
    }

    public function test(Request $r)
    {
        $c = $this->clinic();
        $d = $r->validate(['test_phone' => 'required|string|max:20', 'event' => 'nullable|string']);
        if (! SmsService::clinicCreds($c->id)) {
            return back()->with('error', __('Avval Eskiz akkauntini ulang.'));
        }
        $ev = $d['event'] ?? null;
        $text = isset(SmsEvents::all()[$ev ?? ''])
            ? SmsEvents::render(SmsEvents::get($c, $ev)['text'], SmsEvents::sample() + ['clinic' => $c->name, 'address' => (string) $c->address])
            : $c->name.': '.__('SMS sinov xabari.');
        $ok = SmsService::send($d['test_phone'], $text, $c->id, true, 'test');
        $last = SmsLog::where('clinic_id', $c->id)->latest('id')->first();

        return back()->with($ok ? 'success' : 'error', $ok ? __('Sinov SMS yuborildi.') : __("Yuborilmadi: :e", ['e' => mb_substr((string) $last?->response, 0, 200)]));
    }
}
