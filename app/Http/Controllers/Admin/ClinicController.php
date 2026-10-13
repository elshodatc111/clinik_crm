<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\ClinicStatusLog;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use App\Services\BillingService;
use App\Services\SmsService;
use Illuminate\Http\Request;

class ClinicController extends Controller
{
    public function index(Request $r)
    {
        $q = Clinic::query()->latest('id');
        if ($s = trim((string) $r->query('q'))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('owner_name', 'like', "%$s%"));
        }
        if ($st = $r->query('status')) {
            $q->where('status', $st);
        }
        $clinics = $q->paginate(20)->withQueryString();
        $counts = Patient::withoutGlobalScopes()->whereIn('clinic_id', $clinics->pluck('id'))
            ->selectRaw('clinic_id, count(*) as c')->groupBy('clinic_id')->pluck('c', 'clinic_id');

        return view('admin.clinics.index', compact('clinics', 'counts'));
    }

    public function create()
    {
        return view('admin.clinics.form', ['clinic' => new Clinic()]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|string|max:150',
            'owner_name' => 'required|string|max:150',
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\(\)]{9,20}$/'],
            'email' => 'nullable|email|max:150|unique:users,email',
            'password' => ['required', 'string', 'max:100', new \App\Rules\StrongPassword],
            'status' => 'required|in:trial,active',
        ]);
        $phone = SmsService::normalize($d['phone']);
        if (User::where('phone', $phone)->exists() || Clinic::where('phone', $phone)->exists()) {
            return back()->withErrors(['phone' => __('Bu raqam allaqachon mavjud.')])->withInput();
        }

        $active = $d['status'] === 'active';
        $clinic = Clinic::create([
            'name' => $d['name'], 'owner_name' => $d['owner_name'], 'phone' => $phone, 'email' => $d['email'] ?? null,
            'status' => $d['status'],
            'trial_ends_at' => now()->addDays((int) Setting::get('demo_days', 14)),
            'setup_paid_at' => $active ? now() : null,
            'paid_until' => $active ? now()->addDays(BillingService::PERIOD_DAYS) : null,
            'plan_id' => $active ? BillingService::planFor(0)?->id : null,
            'settings' => [],
        ]);
        ClinicStatusLog::create(['clinic_id' => $clinic->id, 'to_status' => $d['status'], 'reason' => "Super Admin qo'shdi"]);
        User::create([
            'clinic_id' => $clinic->id, 'name' => $d['owner_name'], 'email' => $d['email'] ?? null, 'phone' => $phone,
            'password' => $d['password'], 'role' => User::ADMIN, 'phone_verified_at' => now(),
        ]);

        return redirect()->route('admin.clinics.show', $clinic)->with('success', __("Klinika qo'shildi."));
    }

    public function show(Clinic $clinic)
    {
        return view('admin.clinics.show', [
            'clinic' => $clinic,
            'users' => User::where('clinic_id', $clinic->id)->get(),
            'logs' => $clinic->statusLogs()->limit(15)->get(),
            'transactions' => $clinic->balanceTransactions()->limit(15)->get(),
            'count' => $clinic->patientCount(),
            'plan' => BillingService::planFor($clinic->patientCount()),
        ]);
    }

    public function edit(Clinic $clinic)
    {
        return view('admin.clinics.form', compact('clinic'));
    }

    public function update(Request $r, Clinic $clinic)
    {
        $d = $r->validate([
            'name' => 'required|string|max:150',
            'owner_name' => 'required|string|max:150',
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\(\)]{9,20}$/'],
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
        ]);
        $d['phone'] = SmsService::normalize($d['phone']);
        $clinic->update($d);

        return redirect()->route('admin.clinics.show', $clinic)->with('success', __('Saqlandi.'));
    }

    public function destroy(Request $r, Clinic $clinic)
    {
        $r->validate(['confirm_name' => 'required|string']);
        if (trim($r->input('confirm_name')) !== $clinic->name) {
            return back()->withErrors(['confirm_name' => __("Klinika nomi noto'g'ri kiritildi.")]);
        }
        BillingService::purge($clinic, "Super Admin o'chirdi", $clinic->status !== Clinic::TRIAL);

        return redirect()->route('admin.clinics.index')->with('success', __("Klinika va uning barcha ma'lumotlari o'chirildi."));
    }

    public function status(Request $r, Clinic $clinic)
    {
        $d = $r->validate(['status' => 'required|in:trial,active,view_only,blocked,suspended', 'reason' => 'nullable|string|max:200']);
        if ($d['status'] === Clinic::ACTIVE && ! $clinic->paid_until) {
            $clinic->paid_until = now()->addDays(BillingService::PERIOD_DAYS);
            $clinic->setup_paid_at ??= now();
            $clinic->save();
        }
        BillingService::changeStatus($clinic, $d['status'], $d['reason'] ?: "Super Admin o'zgartirdi");

        return back()->with('success', __('Holat yangilandi.'));
    }

    public function extend(Request $r, Clinic $clinic)
    {
        $d = $r->validate(['days' => 'required|integer|min:1|max:365']);
        if ($clinic->status === Clinic::TRIAL) {
            $base = $clinic->trial_ends_at && $clinic->trial_ends_at->isFuture() ? $clinic->trial_ends_at : now();
            $clinic->trial_ends_at = $base->copy()->addDays($d['days']);
            $clinic->save();
        } else {
            $base = $clinic->paid_until && $clinic->paid_until->isFuture() ? $clinic->paid_until : now();
            $clinic->paid_until = $base->copy()->addDays($d['days']);
            $clinic->last_notice_key = null;
            $clinic->save();
            BillingService::changeStatus($clinic, Clinic::ACTIVE, "Super Admin {$d['days']} kunga uzaytirdi");
        }

        return back()->with('success', __('Muddat uzaytirildi.'));
    }

    public function balance(Request $r, Clinic $clinic)
    {
        $d = $r->validate(['amount' => 'required|integer|not_in:0', 'note' => 'nullable|string|max:200']);
        BillingService::adjust($clinic, (int) $d['amount'], 'manual', $d['note'] ?: "Super Admin tomonidan (qo'lda)", $r->user()->id);
        if ($d['amount'] > 0) {
            BillingService::afterTopup($clinic);
        }

        return back()->with('success', __('Balans yangilandi.'));
    }

    public function hold(Clinic $clinic)
    {
        $clinic->update(['hold_deletion' => ! $clinic->hold_deletion]);

        return back()->with('success', $clinic->hold_deletion ? __("Avtomatik o'chirish to'xtatildi.") : __("Avtomatik o'chirish yoqildi."));
    }

    public function impersonate(Request $r, Clinic $clinic)
    {
        session(['impersonate_clinic_id' => $clinic->id]);
        \App\Models\AuditLog::record('impersonate', null, $clinic->id, ['clinic' => $clinic->name]);

        return redirect()->route('app.dashboard');
    }

    public function stopImpersonate(Request $r)
    {
        $id = session('impersonate_clinic_id');
        session()->forget('impersonate_clinic_id');
        if ($id) {
            \App\Models\AuditLog::record('impersonate_end', null, (int) $id);
        }

        return $id ? redirect()->route('admin.clinics.show', $id) : redirect()->route('admin.dashboard');
    }
}
