<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Patient;

class ExportController extends Controller
{
    /** Bemorlar ro'yxatini CSV (Excel) ga eksport — bloklangan holatda ham ochiq. */
    public function patients()
    {
        $name = 'bemorlar_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', 'JSHSHIR', 'Familiya', 'Ism', 'Otasining ismi', "Tug'ilgan sana", 'Jinsi', 'Telefon', 'Manzil', 'Qon guruhi', 'Allergiya', 'Surunkali kasalliklar', "Qo'shilgan"]);
            Patient::query()->orderBy('id')->cursor()->each(function ($p) use ($out) {
                fputcsv($out, [$p->id, $p->pinfl, $p->last_name, $p->first_name, $p->middle_name, $p->birth_date?->format('Y-m-d'),
                    $p->gender, $p->phone, $p->address, $p->blood_group, $p->allergies, $p->chronic_diseases, $p->created_at]);
            });
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
