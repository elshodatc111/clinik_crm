<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public const START_HOUR = 8;

    public const END_HOUR = 20;

    public function index(Request $r)
    {
        $user = $r->user();
        try {
            $date = Carbon::parse($r->query('date', today()->toDateString()))->startOfDay();
        } catch (\Throwable $e) {
            $date = today();
        }
        $view = $r->query('view') === 'week' ? 'week' : 'day';
        $doctors = User::clinicDoctors()->get();
        if ($user->isScopedDoctor()) {
            $doctors = $doctors->where('id', $user->id)->values();
        }
        $doctorId = $user->isScopedDoctor() ? $user->id : ($r->query('doctor_id') ?: $doctors->first()?->id);

        if ($view === 'week') {
            $from = $date->copy()->startOfWeek();
            $days = collect(range(0, 6))->map(fn ($i) => $from->copy()->addDays($i));
            $range = [$from, $from->copy()->addDays(7)];
        } else {
            $days = collect([$date]);
            $range = [$date, $date->copy()->addDay()];
        }

        $apps = Appointment::with(['patient', 'service', 'doctor'])
            ->where('starts_at', '>=', $range[0])->where('starts_at', '<', $range[1])
            ->whereNotIn('status', ['canceled'])
            ->when($view === 'week', fn ($q) => $q->where('doctor_id', $doctorId))
            ->when($view === 'day' && $user->isScopedDoctor(), fn ($q) => $q->where('doctor_id', $user->id))
            ->orderBy('starts_at')->get();

        // Ustunlar: kun ko'rinishida — shifokorlar, hafta ko'rinishida — kunlar
        $columns = $view === 'week'
            ? $days->map(fn ($d) => ['key' => $d->toDateString(), 'title' => $d->translatedFormat('D d.m'), 'date' => $d, 'doctor_id' => $doctorId, 'today' => $d->isToday()])
            : $doctors->map(fn ($d) => ['key' => $d->id, 'title' => $d->name, 'sub' => $d->specialty, 'date' => $date, 'doctor_id' => $d->id, 'today' => false]);

        $byCol = $apps->groupBy(fn ($a) => $view === 'week' ? $a->starts_at->toDateString() : $a->doctor_id);

        return view('app.calendar', [
            'view' => $view, 'date' => $date, 'doctors' => $doctors, 'doctorId' => $doctorId,
            'columns' => $columns, 'byCol' => $byCol,
            'startHour' => self::START_HOUR, 'endHour' => self::END_HOUR,
        ]);
    }
}
