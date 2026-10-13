<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:60',
            'min_patients' => 'required|integer|min:1',
            'max_patients' => 'nullable|integer|gte:min_patients',
            'monthly_price' => 'required|integer|min:0',
            'sort' => 'nullable|integer|min:0|max:255',
        ];
    }

    public function index()
    {
        return view('admin.plans', ['plans' => Plan::orderBy('sort')->orderBy('min_patients')->get()]);
    }

    public function store(Request $r)
    {
        $d = $r->validate($this->rules());
        $d['sort'] = $d['sort'] ?? 0;
        Plan::create($d);

        return back()->with('success', __("Tarif qo'shildi."));
    }

    public function update(Request $r, Plan $plan)
    {
        $d = $r->validate($this->rules());
        $d['sort'] = $d['sort'] ?? 0;
        $d['is_active'] = $r->boolean('is_active');
        $plan->update($d);

        return back()->with('success', __('Tarif saqlandi.'));
    }
}
