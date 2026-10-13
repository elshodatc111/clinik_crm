<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitController extends Controller
{
    protected function rules(): array
    {
        return [
            'complaint' => 'nullable|string|max:5000',
            'anamnesis' => 'nullable|string|max:5000',
            'examination' => 'nullable|string|max:5000',
            'diagnosis_code' => 'nullable|string|max:20',
            'diagnosis_text' => 'nullable|string|max:3000',
            'recommendations' => 'nullable|string|max:5000',
            'rx' => 'nullable|array|max:30',
            'rx.*.drug' => 'nullable|string|max:150',
            'rx.*.dose' => 'nullable|string|max:100',
            'rx.*.frequency' => 'nullable|string|max:100',
            'rx.*.duration' => 'nullable|string|max:100',
        ];
    }

    protected function syncRx(Visit $visit, array $rows): void
    {
        $visit->prescriptions()->delete();
        foreach ($rows as $row) {
            if (! empty(trim($row['drug'] ?? ''))) {
                Prescription::create([
                    'visit_id' => $visit->id, 'drug' => $row['drug'], 'dose' => $row['dose'] ?? null,
                    'frequency' => $row['frequency'] ?? null, 'duration' => $row['duration'] ?? null,
                ]);
            }
        }
    }

    public function index(Request $r)
    {
        $user = $r->user();
        $visits = Visit::with(['patient', 'doctor'])->latest('visited_at')
            ->when($user->isScopedDoctor(), fn ($q) => $q->where('doctor_id', $user->id))
            ->when($r->query('patient_id'), fn ($q, $p) => $q->where('patient_id', $p))
            ->paginate(25)->withQueryString();

        return view('app.visits.index', compact('visits'));
    }

    public function create(Request $r)
    {
        $appointment = $r->query('appointment') ? Appointment::with('patient')->findOrFail($r->query('appointment')) : null;
        $patient = $appointment?->patient ?? ($r->query('patient') ? Patient::findOrFail($r->query('patient')) : null);
        abort_unless($patient, 404, __('Bemor tanlanmagan.'));

        return view('app.visits.form', [
            'visit' => new Visit(),
            'patient' => $patient,
            'appointment' => $appointment,
            'doctors' => User::clinicDoctors()->get(),
        ]);
    }

    public function store(Request $r)
    {
        $d = $r->validate($this->rules() + [
            'patient_id' => 'required|integer', 'appointment_id' => 'nullable|integer', 'doctor_id' => 'nullable|integer',
        ]);
        $patient = Patient::findOrFail($d['patient_id']);
        $appointment = $d['appointment_id'] ? Appointment::findOrFail($d['appointment_id']) : null;
        $doctorId = $r->user()->isScopedDoctor() ? $r->user()->id : ($d['doctor_id'] ?? $appointment?->doctor_id);
        if (! $doctorId) {
            return back()->withInput()->withErrors(['doctor_id' => __('Shifokorni tanlang.')]);
        }
        User::clinicDoctors()->findOrFail($doctorId);

        $visit = DB::transaction(function () use ($d, $patient, $appointment, $doctorId, $r) {
            $visit = Visit::create([
                'patient_id' => $patient->id, 'appointment_id' => $appointment?->id, 'doctor_id' => $doctorId,
                'complaint' => $d['complaint'] ?? null, 'anamnesis' => $d['anamnesis'] ?? null, 'examination' => $d['examination'] ?? null,
                'diagnosis_code' => $d['diagnosis_code'] ?? null, 'diagnosis_text' => $d['diagnosis_text'] ?? null,
                'recommendations' => $d['recommendations'] ?? null, 'visited_at' => now(),
            ]);
            $this->syncRx($visit, $d['rx'] ?? []);
            $appointment?->update(['status' => 'done']);

            return $visit;
        });

        return redirect()->route('app.visits.show', $visit)->with('success', __('Tashrif saqlandi.'));
    }

    public function show(Visit $visit)
    {
        $visit->load(['patient', 'doctor', 'prescriptions', 'appointment.service']);

        return view('app.visits.show', compact('visit'));
    }

    public function printRx(Visit $visit)
    {
        $visit->load(['patient', 'doctor', 'prescriptions']);

        return view('app.print.prescription', compact('visit'));
    }

    public function printSummary(Visit $visit)
    {
        $visit->load(['patient', 'doctor', 'prescriptions']);

        return view('app.print.summary', compact('visit'));
    }

    public function edit(Request $r, Visit $visit)
    {
        $this->authorizeEdit($r, $visit);
        $visit->load(['patient', 'prescriptions']);

        return view('app.visits.form', ['visit' => $visit, 'patient' => $visit->patient, 'appointment' => null, 'doctors' => User::clinicDoctors()->get()]);
    }

    public function update(Request $r, Visit $visit)
    {
        $this->authorizeEdit($r, $visit);
        $d = $r->validate($this->rules());
        DB::transaction(function () use ($visit, $d) {
            $visit->update(collect($d)->except('rx')->all());
            $this->syncRx($visit, $d['rx'] ?? []);
        });

        return redirect()->route('app.visits.show', $visit)->with('success', __('Saqlandi.'));
    }

    protected function authorizeEdit(Request $r, Visit $visit): void
    {
        if ($r->user()->isScopedDoctor()) {
            abort_unless($visit->doctor_id === $r->user()->id, 403);
        }
    }
}
