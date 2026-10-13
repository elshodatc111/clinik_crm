<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Patient;
use App\Support\Menu;
use Illuminate\Http\Request;

/** Tezkor qidiruv (Ctrl+K): bemorlar, hisob-fakturalar va sahifalar. Rol bo'yicha cheklangan. */
class GlobalSearchController extends Controller
{
    public function __invoke(Request $r)
    {
        $u = $r->user();
        $q = trim((string) $r->query('q'));
        $out = ['patients' => [], 'invoices' => [], 'pages' => []];
        if (mb_strlen($q) < 2) {
            return response()->json($out);
        }

        if ($u->hasRole('clinic_admin', 'receptionist', 'doctor', 'cashier')) {
            $digits = preg_replace('/\D+/', '', $q);
            $query = Patient::query();
            if ($digits !== '' && strlen($digits) >= 3 && ! preg_match('/\p{L}/u', $q)) {
                $query->where(fn ($w) => $w->where('phone', 'like', '%'.$digits.'%')->orWhere('pinfl', 'like', $digits.'%'));
            } else {
                foreach (preg_split('/\s+/u', $q) as $word) {
                    $query->where(fn ($w) => $w->where('last_name', 'like', $word.'%')->orWhere('first_name', 'like', $word.'%')->orWhere('middle_name', 'like', $word.'%'));
                }
            }
            $out['patients'] = $query->orderBy('last_name')->limit(6)->get()->map(fn ($p) => [
                'title' => $p->full_name, 'sub' => trim(phone_fmt($p->phone).($p->birth_date ? ' · '.$p->birth_date->format('d.m.Y') : '')), 'url' => route('app.patients.show', $p),
            ])->values();
        }

        if ($u->hasRole('clinic_admin', 'receptionist', 'cashier')) {
            $out['invoices'] = Invoice::with('patient')->where('number', 'like', '%'.$q.'%')->latest('id')->limit(4)->get()->map(fn ($i) => [
                'title' => '№ '.$i->number, 'sub' => $i->patient?->full_name, 'url' => route('app.invoices.show', $i),
            ])->values();
        }

        $needle = mb_strtolower($q);
        foreach (Menu::clinic() as $g) {
            foreach ($g['items'] as [$route, $label, $roles]) {
                if ((in_array('*', $roles, true) || $u->hasRole(...$roles)) && str_contains(mb_strtolower(__($label)), $needle)) {
                    $out['pages'][] = ['title' => __($label), 'sub' => $g['label'] ? __($g['label']) : '', 'url' => route($route)];
                }
            }
        }
        $out['pages'] = array_slice($out['pages'], 0, 5);

        return response()->json($out);
    }
}
