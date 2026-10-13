<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index()
    {
        return view('app.staff.index', ['staff' => User::clinicStaff()->orderBy('role')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('app.staff.form', ['staff' => new User(['is_active' => true, 'role' => User::RECEPTION, 'percent' => 0])]);
    }

    protected function data(Request $r, ?User $staff = null): array|\Illuminate\Http\RedirectResponse
    {
        $d = $r->validate([
            'name' => 'required|string|max:150',
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\(\)]{9,20}$/'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($staff?->id)],
            'role' => ['required', Rule::in(array_keys(User::clinicRoles()))],
            'password' => [$staff ? 'nullable' : 'required', 'string', 'min:8'],
            'specialty' => 'nullable|string|max:150',
            'room' => 'nullable|string|max:50',
            'percent' => 'nullable|integer|min:0|max:100',
        ]);
        $d['phone'] = SmsService::normalize($d['phone']);
        $taken = User::where('phone', $d['phone'])->when($staff, fn ($q) => $q->where('id', '!=', $staff->id))->exists();
        if ($taken) {
            return back()->withInput()->withErrors(['phone' => __('Bu telefon raqami allaqachon band.')]);
        }
        $d['is_active'] = $r->boolean('is_active');
        $d['percent'] = (int) ($d['percent'] ?? 0);
        if (empty($d['password'])) {
            unset($d['password']);
        }

        return $d;
    }

    public function store(Request $r)
    {
        $d = $this->data($r);
        if ($d instanceof \Illuminate\Http\RedirectResponse) {
            return $d;
        }
        User::create($d + ['clinic_id' => Tenant::id(), 'phone_verified_at' => now()]);

        return redirect()->route('app.staff.index')->with('success', __("Xodim qo'shildi."));
    }

    protected function own(User $staff): User
    {
        abort_unless((int) $staff->clinic_id === (int) Tenant::id(), 404);

        return $staff;
    }

    public function edit(User $staff)
    {
        return view('app.staff.form', ['staff' => $this->own($staff)]);
    }

    public function update(Request $r, User $staff)
    {
        $this->own($staff);
        $d = $this->data($r, $staff);
        if ($d instanceof \Illuminate\Http\RedirectResponse) {
            return $d;
        }
        if ($staff->id === $r->user()->id && (! $d['is_active'] || $d['role'] !== User::ADMIN)) {
            return back()->withInput()->with('error', __("O'zingizning rolingizni o'zgartira yoki akkauntingizni o'chira olmaysiz."));
        }
        $staff->update($d);

        return redirect()->route('app.staff.index')->with('success', __('Saqlandi.'));
    }
}
