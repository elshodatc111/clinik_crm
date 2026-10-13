<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\OnlineBooking;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use App\Services\ClinicPaymeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Klinikaning ommaviy onlayn qabul sahifasi (/k/{slug}). Kirish talab qilinmaydi. */
class BookingController extends Controller
{
    protected function clinic(string $slug): Clinic
    {
        return Clinic::where('slug', $slug)->firstOrFail();
    }

    protected function open(string $slug): Clinic
    {
        $c = $this->clinic($slug);
        abort_unless(BookingService::isOpen($c), 404);

        return $c;
    }

    public function show(string $slug)
    {
        $c = $this->clinic($slug);
        if (! BookingService::isOpen($c)) {
            return response()->view('public.booking-closed', ['clinic' => $c], 404);
        }
        $services = BookingService::services($c)->map(fn (Service $s) => [
            'id' => $s->id, 'name' => $s->name, 'group' => $s->group, 'price' => (int) $s->price, 'duration' => (int) $s->duration_min,
            'doctors' => BookingService::doctorsFor($c, $s)->map(fn (User $d) => ['id' => $d->id, 'name' => $d->name, 'specialty' => $d->specialty])->values(),
        ])->filter(fn ($s) => count($s['doctors']))->values();

        return view('public.booking', [
            'clinic' => $c, 'services' => $services,
            'minDate' => today()->toDateString(), 'maxDate' => today()->addDays((int) BookingService::cfg($c, 'online_days_ahead'))->toDateString(),
        ]);
    }

    public function offer(string $slug)
    {
        $c = $this->clinic($slug);

        return view('public.booking-offer', ['clinic' => $c, 'text' => BookingService::offerText($c)]);
    }

    public function slots(Request $r, string $slug)
    {
        $c = $this->open($slug);
        $d = $r->validate(['service' => 'required|integer', 'doctor' => 'required|integer', 'date' => 'required|date']);
        $service = BookingService::services($c)->firstWhere('id', (int) $d['service']);
        $doctor = $service ? BookingService::doctorsFor($c, $service)->firstWhere('id', (int) $d['doctor']) : null;
        abort_unless($service && $doctor, 404);

        return response()->json(['slots' => BookingService::slots($c, $service, $doctor, Carbon::parse($d['date']))]);
    }

    public function book(Request $r, string $slug)
    {
        $c = $this->open($slug);
        $d = $r->validate([
            'service' => 'required|integer', 'doctor' => 'required|integer', 'date' => 'required|date', 'time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'last_name' => 'required|string|max:100', 'first_name' => 'required|string|max:100', 'middle_name' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date|before_or_equal:today', 'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\(\)]{9,20}$/'],
            'pinfl' => ['nullable', 'digits:14'], 'accept' => 'accepted', 'accept_privacy' => 'accepted',
        ]);
        $service = BookingService::services($c)->firstWhere('id', (int) $d['service']);
        $doctor = $service ? BookingService::doctorsFor($c, $service)->firstWhere('id', (int) $d['doctor']) : null;
        abort_unless($service && $doctor, 404);

        // Spam himoyasi: bir telefondan bir vaqtda 3 tadan ortiq to'lanmagan bron bo'lmasin
        $phone = \App\Services\SmsService::normalize($d['phone']);
        $pending = OnlineBooking::withoutGlobalScopes()->where('clinic_id', $c->id)->where('phone', $phone)->where('status', 'pending')->where('hold_expires_at', '>', now())->count();
        if ($pending >= 3) {
            return response()->json(['message' => __("Juda ko'p to'lanmagan bron. Biroz kuting yoki avval to'lang."), 'errors' => ['phone' => [__("Juda ko'p to'lanmagan bron. Biroz kuting yoki avval to'lang.")]]], 422);
        }

        try {
            $start = Carbon::parse($d['date'].' '.$d['time']);
            $b = BookingService::create($c, $service, $doctor, $start, $d, $r->ip(), app()->getLocale());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => __('Bu vaqt band bo\'lib qoldi. Boshqa vaqt tanlang.'), 'errors' => ['time' => [__('Bu vaqt band bo\'lib qoldi. Boshqa vaqt tanlang.')]]], 422);
        }

        $ticket = route('booking.ticket', [$c->slug, $b->token]);

        return response()->json(['url' => BookingService::paymeConfigured($c) ? BookingService::checkoutUrl($b, $c, $ticket) : $ticket]);
    }

    protected function find(string $slug, string $token): array
    {
        $c = $this->clinic($slug);
        $b = OnlineBooking::withoutGlobalScopes()->where('clinic_id', $c->id)->where('token', $token)->firstOrFail();

        return [$c, $b];
    }

    public function ticket(string $slug, string $token)
    {
        [$c, $b] = $this->find($slug, $token);
        $b->load(['service', 'doctor']);
        $app = $b->appointment_id ? Appointment::withoutGlobalScopes()->find($b->appointment_id) : null;

        return view('public.ticket', [
            'clinic' => $c, 'b' => $b, 'queue' => $app ? BookingService::queueNumber($app) : null,
            'payUrl' => $b->status === 'pending' && BookingService::paymeConfigured($c) ? BookingService::checkoutUrl($b, $c, route('booking.ticket', [$c->slug, $b->token])) : null,
            'sandbox' => $b->status === 'pending' && BookingService::sandbox($c),
        ]);
    }

    public function status(string $slug, string $token)
    {
        [, $b] = $this->find($slug, $token);

        return response()->json(['status' => $b->status]);
    }

    /** Faqat APP_DEBUG yoqilgan va Payme ulanmagan klinikada: to'lovsiz tasdiqlash (sinov uchun). */
    public function simulate(string $slug, string $token)
    {
        [$c, $b] = $this->find($slug, $token);
        abort_unless(BookingService::sandbox($c) && $b->status === 'pending', 403);
        try {
            BookingService::confirm($b);
        } catch (\RuntimeException $e) {
            return back()->with('error', __("Bu vaqt band bo'lib qoldi."));
        }

        return redirect()->route('booking.ticket', [$c->slug, $b->token]);
    }
}
