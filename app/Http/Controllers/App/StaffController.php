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
        return view('app.staff.index', [
            'staff' => User::clinicStaff()->orderBy('role')->orderBy('name')->get(),
            'matrix' => \App\Support\Menu::matrix(),
        ]);
    }

    public function create()
    {
        return view('app.staff.form', ['staff' => new User(['is_active' => true, 'role' => User::RECEPTION, 'position' => 'admin', 'percent' => 0, 'pay_type' => 'none', 'pay_scope' => 'own', 'pay_base' => 0, 'pay_percent' => 0])]);
    }

    protected function data(Request $r, ?User $staff = null): array|\Illuminate\Http\RedirectResponse
    {
        $d = $r->validate([
            'name' => 'required|string|max:150',
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\(\)]{9,20}$/'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($staff?->id)],
            'role' => ['required', Rule::in(array_keys(User::clinicRoles()))],
            'password' => [$staff ? 'nullable' : 'required', 'string', 'min:8'],
            'position' => ['nullable', Rule::in(['director', 'admin'])],
            'extra_roles' => 'nullable|array',
            'extra_roles.*' => [Rule::in(array_keys(User::assignableRoles()))],
            'specialty' => 'nullable|string|max:150',
            'room' => 'nullable|string|max:50',
            'pay_type' => ['required', Rule::in(array_keys(\App\Services\PayrollService::types()))],
            'pay_base' => 'nullable|integer|min:0|max:100000000000',
            'pay_percent' => 'nullable|numeric|min:0|max:100',
            'pay_scope' => ['nullable', Rule::in(['own', 'clinic'])],
        ]);
        $d['phone'] = SmsService::normalize($d['phone']);
        $taken = User::where('phone', $d['phone'])->when($staff, fn ($q) => $q->where('id', '!=', $staff->id))->exists();
        if ($taken) {
            return back()->withInput()->withErrors(['phone' => __('Bu telefon raqami allaqachon band.')]);
        }
        $d['is_active'] = $r->boolean('is_active');
        if ($d['role'] === User::ADMIN) {
            $d['position'] = ($d['position'] ?? 'admin') === 'director' ? 'director' : 'admin';
            // Faqat direktor yangi direktor tayinlay oladi
            if ($d['position'] === 'director' && ! $r->user()->isDirector() && ! ($staff && $staff->isDirector())) {
                return back()->withInput()->with('error', __('Direktor lavozimini faqat direktor bera oladi.'));
            }
        } else {
            $d['position'] = null;
        }
        // Qo'shimcha rollar: asosiy roldan tashqari; admin uchun kerak emas (u hamma narsani ko'radi)
        $extra = array_values(array_diff($d['extra_roles'] ?? [], [$d['role']]));
        $d['extra_roles'] = $d['role'] === User::ADMIN ? [] : $extra;
        $d['pay_base'] = (int) ($d['pay_base'] ?? 0);
        $d['pay_percent'] = (float) ($d['pay_percent'] ?? 0);
        $d['pay_scope'] = $d['pay_scope'] ?? 'own';
        $d['percent'] = (int) round($d['pay_percent']);
        $d['online_enabled'] = $r->boolean('online_enabled');
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
        // Direktor akkauntini faqat direktor tahrirlay oladi
        abort_if($staff->role === User::ADMIN && $staff->position !== 'admin' && ! auth()->user()->isDirector() && $staff->id !== auth()->id(), 403, __("Direktor akkauntini faqat direktor o'zgartira oladi."));

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
        // Oxirgi faol direktorni olib tashlab bo'lmaydi
        $wasDirector = $staff->role === User::ADMIN && $staff->position !== 'admin' && $staff->is_active;
        $stillDirector = $d['role'] === User::ADMIN && $d['position'] === 'director' && $d['is_active'];
        if ($wasDirector && ! $stillDirector) {
            $others = User::clinicStaff()->where('id', '!=', $staff->id)->where('role', User::ADMIN)->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('position')->orWhere('position', '!=', 'admin'))->exists();
            if (! $others) {
                return back()->withInput()->with('error', __("Klinikada kamida bitta faol direktor bo'lishi kerak."));
            }
        }
        if ($staff->id === $r->user()->id && (! $d['is_active'] || $d['role'] !== User::ADMIN)) {
            return back()->withInput()->with('error', __("O'zingizning rolingizni o'zgartira yoki akkauntingizni o'chira olmaysiz."));
        }
        $staff->update($d);

        return redirect()->route('app.staff.index')->with('success', __('Saqlandi.'));
    }
}
