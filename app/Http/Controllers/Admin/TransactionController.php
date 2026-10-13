<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BalanceTransaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $r)
    {
        $q = BalanceTransaction::with('clinic')->latest('id');
        if ($t = $r->query('type')) {
            $q->where('type', $t);
        }
        if ($c = $r->query('clinic_id')) {
            $q->where('clinic_id', $c);
        }

        return view('admin.transactions', ['items' => $q->paginate(30)->withQueryString()]);
    }
}
