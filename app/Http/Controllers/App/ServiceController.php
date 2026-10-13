<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'group' => 'nullable|string|max:100',
            'price' => 'required|integer|min:0',
            'duration_min' => 'required|integer|min:5|max:480',
        ];
    }

    public function index(Request $r)
    {
        $q = Service::query()->orderBy('group')->orderBy('name');
        if ($s = trim((string) $r->query('q'))) {
            $q->where('name', 'like', "%$s%");
        }

        return view('app.services.index', ['services' => $q->paginate(30)->withQueryString()]);
    }

    public function create()
    {
        return view('app.services.form', ['service' => new Service(['duration_min' => 30, 'is_active' => true])]);
    }

    public function store(Request $r)
    {
        Service::create($r->validate($this->rules()) + ['is_active' => $r->boolean('is_active')]);

        return redirect()->route('app.services.index')->with('success', __("Xizmat qo'shildi."));
    }

    public function edit(Service $service)
    {
        return view('app.services.form', compact('service'));
    }

    public function update(Request $r, Service $service)
    {
        $service->update($r->validate($this->rules()) + ['is_active' => $r->boolean('is_active')]);

        return redirect()->route('app.services.index')->with('success', __('Saqlandi.'));
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return back()->with('success', __("Xizmat o'chirildi."));
    }
}
