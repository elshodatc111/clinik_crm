<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Clinic;
use App\Services\ClinicDiagnostics;
use App\Services\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Super admin: klinika admin/direktorlariga xabar yuborish va klinikalar kamchiliklarini ko'rish. */
class BroadcastController extends Controller
{
    public function index()
    {
        $history = AppNotification::whereNotNull('batch')->selectRaw('batch, MAX(title) title, MAX(level) level, MIN(created_at) sent_at, COUNT(*) total, SUM(CASE WHEN read_at IS NULL THEN 0 ELSE 1 END) readc, COUNT(DISTINCT clinic_id) clinics')
            ->groupBy('batch')->orderByDesc('sent_at')->limit(20)->get();

        return view('admin.notifications', [
            'clinics' => Clinic::orderBy('name')->get(['id', 'name', 'status']),
            'history' => $history,
        ]);
    }

    public function send(Request $r)
    {
        $d = $r->validate([
            'audience' => 'required|in:all,active,trial,problem,selected',
            'clinics' => 'nullable|array', 'clinics.*' => 'integer',
            'level' => 'required|in:info,success,warning,danger',
            'title' => 'required|string|max:160', 'body' => 'nullable|string|max:2000', 'url' => 'nullable|string|max:255',
        ]);
        $q = Clinic::query();
        match ($d['audience']) {
            'active' => $q->where('status', Clinic::ACTIVE),
            'trial' => $q->where('status', Clinic::TRIAL),
            'problem' => $q->whereIn('status', [Clinic::VIEW_ONLY, Clinic::BLOCKED]),
            'selected' => $q->whereIn('id', $d['clinics'] ?? [0]),
            default => null,
        };
        $batch = Notify::newBatch();
        $n = 0;
        $q->get(['id'])->each(function ($c) use ($d, $batch, $r, &$n) {
            $n += Notify::send(Notify::admins($c->id), $d['title'], $d['body'] ?? null, $d['level'], $d['url'] ?? null, 'system', $c->id, null, $batch, $r->user()->id);
        });

        return back()->with($n ? 'success' : 'error', $n ? __(':n ta qabul qiluvchiga yuborildi.', ['n' => $n]) : __('Qabul qiluvchi topilmadi.'));
    }

    public function diagnostics()
    {
        $rows = Clinic::whereIn('status', [Clinic::TRIAL, Clinic::ACTIVE, Clinic::VIEW_ONLY, Clinic::BLOCKED])->orderBy('name')->limit(200)->get()->map(function ($c) {
            $issues = ClinicDiagnostics::run($c);

            return ['clinic' => $c, 'issues' => $issues, 'danger' => collect($issues)->where('level', 'danger')->count(), 'warning' => collect($issues)->where('level', 'warning')->count()];
        })->sortByDesc(fn ($x) => $x['danger'] * 100 + $x['warning'] * 10 + count($x['issues']))->values();

        return view('admin.diagnostics', ['rows' => $rows]);
    }

    public function notifyClinic(Request $r, Clinic $clinic)
    {
        $n = ClinicDiagnostics::notify($clinic, null, $r->user()->id);

        return back()->with('success', $n ? __(':n ta xabar yuborildi.', ['n' => $n]) : __('Yangi xabar yo\'q (avval yuborilgan yoki kamchilik topilmadi).'));
    }

    public function notifyAll(Request $r)
    {
        $total = 0;
        Clinic::whereIn('status', [Clinic::TRIAL, Clinic::ACTIVE, Clinic::VIEW_ONLY, Clinic::BLOCKED])->get()->each(function ($c) use (&$total, $r) {
            $total += ClinicDiagnostics::notify($c, null, $r->user()->id);
        });

        return back()->with('success', __(':n ta xabar yuborildi.', ['n' => $total]));
    }
}
