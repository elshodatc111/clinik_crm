<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\User;
use App\Services\BillingService;
use App\Services\SmsService;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LabController extends Controller
{
    public function index(Request $r)
    {
        $items = LabOrder::with(['patient', 'doctor'])->latest('id')
            ->when($r->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($r->query('patient_id'), fn ($q, $p) => $q->where('patient_id', $p))
            ->paginate(25)->withQueryString();

        return view('app.lab.index', compact('items'));
    }

    public function create(Request $r)
    {
        $patient = $r->query('patient') ? Patient::findOrFail($r->query('patient')) : null;

        return view('app.lab.create', ['patient' => $patient, 'doctors' => User::clinicDoctors()->get()]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'patient_id' => 'required|integer', 'test_name' => 'required|string|max:200', 'doctor_id' => 'nullable|integer',
        ]);
        $patient = Patient::findOrFail($d['patient_id']);
        $doctorId = $r->user()->isScopedDoctor() ? $r->user()->id : ($d['doctor_id'] ?? null);
        if ($doctorId) {
            User::clinicDoctors()->findOrFail($doctorId);
        }
        LabOrder::create([
            'patient_id' => $patient->id, 'doctor_id' => $doctorId, 'test_name' => $d['test_name'],
            'status' => 'ordered', 'ordered_at' => now(), 'ordered_by' => $r->user()->id,
        ]);

        return redirect()->route('app.lab.index')->with('success', __("Tahlil yo'llanmasi yaratildi."));
    }

    public function edit(LabOrder $lab)
    {
        $lab->load('patient');

        return view('app.lab.edit', ['lab' => $lab]);
    }

    public function update(Request $r, LabOrder $lab)
    {
        $d = $r->validate([
            'result_value' => 'nullable|string|max:5000',
            'norm' => 'nullable|string|max:200',
            'result_note' => 'nullable|string|max:3000',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $update = ['result_value' => $d['result_value'] ?? null, 'norm' => $d['norm'] ?? null, 'result_note' => $d['result_note'] ?? null];
        if ($r->hasFile('file')) {
            if ($lab->file_path) {
                Storage::disk('local')->delete($lab->file_path);
            }
            $update['file_path'] = $r->file('file')->store('clinics/'.Tenant::id().'/lab', 'local');
        }
        $hasResult = ! empty($update['result_value']) || ! empty($update['file_path'] ?? $lab->file_path);
        $wasDone = $lab->status === 'done';
        if ($hasResult) {
            $update += ['status' => 'done', 'completed_at' => $lab->completed_at ?? now(), 'completed_by' => $r->user()->id];
        }
        $lab->update($update);

        if ($hasResult && ! $wasDone && $r->boolean('notify') && $lab->patient?->phone) {
            SmsService::event($lab->clinic_id, 'lab_ready', $lab->patient->phone, ['name' => $lab->patient->first_name]);
        }

        return redirect()->route('app.lab.index')->with('success', __('Natija saqlandi.'));
    }

    public function file(LabOrder $lab)
    {
        abort_unless($lab->file_path && Storage::disk('local')->exists($lab->file_path), 404);

        return Storage::disk('local')->response($lab->file_path);
    }

    /** Tahlilni faqat direktor o'chira oladi. */
    public function destroy(Request $r, LabOrder $lab)
    {
        abort_unless($r->user()->isDirector(), 403, __("Tahlilni faqat direktor o'chira oladi."));
        if ($lab->file_path) {
            Storage::disk('local')->delete($lab->file_path);
        }
        $lab->delete();

        return redirect()->route('app.lab.index')->with('success', __("Tahlil o'chirildi."));
    }
}
