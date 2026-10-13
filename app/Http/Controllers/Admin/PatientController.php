<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

/** Bemorni faqat Super Admin o'chira oladi. */
class PatientController extends Controller
{
    public function index(Request $r)
    {
        $q = Patient::query()->with('clinic')->latest('id');
        if ($s = trim((string) $r->query('q'))) {
            $q->where(fn ($w) => $w->where('last_name', 'like', "%$s%")->orWhere('first_name', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%")->orWhere('pinfl', 'like', "%$s%"));
        }
        if ($c = $r->query('clinic_id')) {
            $q->where('clinic_id', $c);
        }

        return view('admin.patients', ['patients' => $q->paginate(25)->withQueryString()]);
    }

    public function destroy(Patient $patient)
    {
        $patient->delete(); // soft delete; audit jurnaliga yoziladi

        return back()->with('success', __("Bemor o'chirildi."));
    }
}
