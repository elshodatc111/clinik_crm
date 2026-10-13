<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\SmsService;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    protected function rules(): array
    {
        return [
            'pinfl' => ['nullable', 'digits:14'],
            'last_name' => 'required|string|max:100',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date|before_or_equal:today',
            'gender' => 'nullable|in:m,f',
            'phone' => 'nullable|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'blood_group' => 'nullable|string|max:20',
            'allergies' => 'nullable|string|max:2000',
            'chronic_diseases' => 'nullable|string|max:2000',
            'source' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:3000',
        ];
    }

    protected function clean(array $d): array
    {
        foreach (['phone', 'phone2'] as $k) {
            if (! empty($d[$k])) {
                $d[$k] = SmsService::normalize($d[$k]);
            }
        }
        $d['pinfl'] = ($d['pinfl'] ?? null) ?: null;

        return $d;
    }

    protected function duplicateResponse(array $d, ?int $exceptId = null)
    {
        $dup = Patient::findDuplicate($d, $exceptId);
        if (! $dup) {
            return null;
        }
        $msg = __("Bu bemor allaqachon kiritilgan: :name. Takroran qo'shib bo'lmaydi.", ['name' => $dup->full_name]);
        if ($dup->trashed()) {
            $msg = __("Bu bemor ilgari kiritilgan va o'chirilgan. Tiklash uchun administrator bilan bog'laning.");
        }

        return back()->withInput()->withErrors(['duplicate' => $msg])->with('duplicate_id', $dup->trashed() ? null : $dup->id);
    }

    public function index(Request $r)
    {
        $q = Patient::query()->orderBy('last_name')->orderBy('first_name');
        if ($s = trim((string) $r->query('q'))) {
            $digits = preg_replace('/\D+/', '', $s);
            $q->where(function ($w) use ($s, $digits) {
                $w->where('last_name', 'like', "%$s%")->orWhere('first_name', 'like', "%$s%")->orWhere('middle_name', 'like', "%$s%");
                if ($digits !== '') {
                    $w->orWhere('phone', 'like', "%$digits%")->orWhere('pinfl', 'like', "%$digits%");
                }
            });
        }

        $showMoney = $r->user()->hasRole('clinic_admin', 'receptionist', 'cashier');
        if ($showMoney && $r->query('balance') === 'debt') {
            $q->whereHas('invoices', fn ($i) => $i->whereIn('status', ['unpaid', 'partial']));
        }
        $patients = $q->paginate(25)->withQueryString();

        return view('app.patients.index', [
            'patients' => $patients,
            'showMoney' => $showMoney,
            'balances' => $showMoney ? \App\Services\PatientBalance::forPatients($patients->pluck('id')->all()) : [],
            'totals' => $showMoney ? \App\Services\PatientBalance::clinicTotals() : null,
        ]);
    }

    public function search(Request $r)
    {
        $s = trim((string) $r->query('q'));
        if (mb_strlen($s) < 2) {
            return response()->json([]);
        }
        $digits = preg_replace('/\D+/', '', $s);
        $items = Patient::query()->where(function ($w) use ($s, $digits) {
            $w->where('last_name', 'like', "%$s%")->orWhere('first_name', 'like', "%$s%");
            if ($digits !== '') {
                $w->orWhere('phone', 'like', "%$digits%")->orWhere('pinfl', 'like', "%$digits%");
            }
        })->orderBy('last_name')->limit(10)->get();

        return response()->json($items->map(fn ($p) => [
            'id' => $p->id,
            'text' => $p->full_name.($p->birth_date ? ' · '.$p->birth_date->format('d.m.Y') : '').($p->phone ? ' · +'.$p->phone : ''),
        ]));
    }

    public function create()
    {
        return view('app.patients.form', ['patient' => new Patient()]);
    }

    public function store(Request $r)
    {
        $d = $this->clean($r->validate($this->rules()));
        if ($resp = $this->duplicateResponse($d)) {
            return $resp;
        }
        $patient = Patient::create($d + ['created_by' => $r->user()->id]);

        return redirect()->route('app.patients.show', $patient)->with('success', __("Bemor qo'shildi."));
    }

    /** JSHSHIR bo'yicha F.I.Sh. olish (klinika o'z shartnomasi/API kaliti bilan yoqqan bo'lsa). */
    public function lookup(Request $r)
    {
        $clinic = view()->shared('clinic');
        if (! $clinic || ! \App\Services\PinflLookupService::enabled($clinic)) {
            return response()->json(['status' => 'disabled'], 403);
        }
        $pinfl = (string) $r->query('pinfl');
        if (! preg_match('/^\d{14}$/', $pinfl)) {
            return response()->json(['status' => 'invalid', 'message' => __("JSHSHIR 14 ta raqamdan iborat bo'lishi kerak.")], 422);
        }
        $exists = Patient::withTrashed()->where('pinfl', $pinfl)->first();
        if ($exists) {
            return response()->json(['status' => 'exists', 'message' => __('Bu bemor allaqachon kiritilgan.'), 'url' => route('app.patients.show', $exists)]);
        }
        try {
            $data = \App\Services\PinflLookupService::lookup($clinic, $pinfl, $r->user()->id);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => __('Xizmatdan javob olinmadi: :e', ['e' => $e->getMessage()])], 502);
        }

        return $data
            ? response()->json(['status' => 'ok', 'data' => $data])
            : response()->json(['status' => 'not_found', 'message' => __("Bu JSHSHIR bo'yicha ma'lumot topilmadi.")]);
    }

    public function show(Patient $patient)
    {
        $patient->load(['visits.doctor', 'invoices', 'labOrders', 'appointments.doctor']);
        $photos = \App\Models\PatientPhoto::where('patient_id', $patient->id)->latest('id')->get();

        $balance = auth()->user()->hasRole('clinic_admin', 'receptionist', 'cashier') ? \App\Services\PatientBalance::forPatient($patient->id) : null;
        $advances = $balance ? [
            'payments' => \App\Models\Payment::whereNull('invoice_id')->where('patient_id', $patient->id)->latest('paid_at')->limit(10)->get(),
            'used' => \App\Models\CreditApplication::where('patient_id', $patient->id)->latest('id')->limit(10)->get(),
        ] : null;

        return view('app.patients.show', compact('patient', 'photos', 'balance', 'advances'));
    }

    public function edit(Patient $patient)
    {
        return view('app.patients.form', compact('patient'));
    }

    public function update(Request $r, Patient $patient)
    {
        $d = $this->clean($r->validate($this->rules()));
        if ($resp = $this->duplicateResponse($d, $patient->id)) {
            return $resp;
        }
        $patient->update($d);

        return redirect()->route('app.patients.show', $patient)->with('success', __('Saqlandi.'));
    }
}
