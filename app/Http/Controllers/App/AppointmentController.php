<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\BillingService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();
        try {
            $date = Carbon::parse($r->query('date', today()->toDateString()))->startOfDay();
        } catch (\Throwable $e) {
            $date = today();
        }
        $doctorId = $user->role === 'doctor' ? $user->id : $r->query('doctor_id');

        $items = Appointment::with(['patient', 'doctor', 'service'])
            ->whereDate('starts_at', $date)
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->when($r->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('starts_at')->get();

        return view('app.appointments.index', [
            'items' => $items, 'date' => $date, 'doctors' => User::clinicDoctors()->get(), 'doctorId' => $doctorId,
        ]);
    }

    public function create(Request $r)
    {
        $patient = $r->query('patient') ? Patient::find($r->query('patient')) : null;

        return view('app.appointments.form', [
            'appointment' => new Appointment([
                'starts_at' => $this->queryTime($r) ?? now()->addHour()->minute(0)->second(0),
                'doctor_id' => $r->user()->role === 'doctor' ? $r->user()->id : ($r->query('doctor_id') ?: null),
            ]),
            'patient' => $patient,
            'doctors' => User::clinicDoctors()->get(),
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    protected function queryTime(Request $r): ?Carbon
    {
        try {
            return $r->query('starts_at') ? Carbon::parse($r->query('starts_at')) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function validated(Request $r, ?Appointment $current = null): array|\Illuminate\Http\RedirectResponse
    {
        $d = $r->validate([
            'patient_id' => 'required|integer',
            'doctor_id' => 'required|integer',
            'service_id' => 'nullable|integer',
            'starts_at' => 'required|date',
            'duration_min' => 'nullable|integer|min:5|max:480',
            'comment' => 'nullable|string|max:1000',
        ]);
        Patient::findOrFail($d['patient_id']);
        User::clinicDoctors()->findOrFail($d['doctor_id']);
        $service = $d['service_id'] ? Service::findOrFail($d['service_id']) : null;

        $start = Carbon::parse($d['starts_at']);
        $minutes = (int) ($d['duration_min'] ?? ($service?->duration_min ?? 30));
        $end = $start->copy()->addMinutes($minutes);

        $busy = Appointment::where('doctor_id', $d['doctor_id'])
            ->whereNotIn('status', ['canceled', 'no_show'])
            ->where('starts_at', '<', $end)->where('ends_at', '>', $start)
            ->when($current, fn ($q) => $q->where('id', '!=', $current->id))->exists();
        if ($busy) {
            return back()->withInput()->withErrors(['starts_at' => __("Bu vaqtda shifokor band. Boshqa vaqt tanlang.")]);
        }

        return [
            'patient_id' => $d['patient_id'], 'doctor_id' => $d['doctor_id'], 'service_id' => $d['service_id'] ?? null,
            'starts_at' => $start, 'ends_at' => $end, 'comment' => $d['comment'] ?? null,
        ];
    }

    public function store(Request $r)
    {
        $data = $this->validated($r);
        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }
        $a = Appointment::create($data + ['status' => 'scheduled', 'created_by' => $r->user()->id]);

        if ($r->boolean('send_sms')) {
            $p = $a->patient;
            $clinicName = view()->shared('clinic')?->name ?? BillingService::appName();
            if ($p && $p->phone) {
                SmsService::send($p->phone, __('Hurmatli :name, :date da :clinic klinikasiga qabulingiz bor.', [
                    'name' => $p->first_name, 'date' => $a->starts_at->format('d.m.Y H:i'), 'clinic' => $clinicName,
                ]), $a->clinic_id, true);
            }
        }

        return redirect()->route('app.appointments.index', ['date' => $a->starts_at->toDateString()])->with('success', __('Qabul qo\'shildi.'));
    }

    public function edit(Appointment $appointment)
    {
        return view('app.appointments.form', [
            'appointment' => $appointment,
            'patient' => $appointment->patient,
            'doctors' => User::clinicDoctors()->get(),
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $r, Appointment $appointment)
    {
        $data = $this->validated($r, $appointment);
        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }
        $appointment->update($data);

        return redirect()->route('app.appointments.index', ['date' => $appointment->starts_at->toDateString()])->with('success', __('Saqlandi.'));
    }

    public function status(Request $r, Appointment $appointment)
    {
        $d = $r->validate(['status' => 'required|in:'.implode(',', array_keys(Appointment::statuses()))]);
        if ($r->user()->role === 'doctor') {
            abort_unless($appointment->doctor_id === $r->user()->id, 403);
        }
        $appointment->update(['status' => $d['status']]);

        return back()->with('success', __('Holat yangilandi.'));
    }
}
