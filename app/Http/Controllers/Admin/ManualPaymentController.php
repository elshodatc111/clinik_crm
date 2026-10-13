<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualPayment;
use App\Services\ManualPaymentService;
use Illuminate\Http\Request;

/** Super Admin: klinikalarning karta to'lovi cheklarini tekshirish. */
class ManualPaymentController extends Controller
{
    public function index(Request $r)
    {
        $status = in_array($r->query('status'), ['pending', 'approved', 'rejected', 'canceled', 'all'], true) ? $r->query('status') : 'pending';
        $q = ManualPayment::with('clinic', 'user', 'reviewer')->latest('id');
        if ($status !== 'all') {
            $q->where('status', $status);
        }
        if ($c = (int) $r->query('clinic_id')) {
            $q->where('clinic_id', $c);
        }

        return view('admin.payments', [
            'items' => $q->paginate(20)->withQueryString(), 'status' => $status,
            'counts' => ManualPayment::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'card' => ManualPaymentService::card(), 'enabled' => ManualPaymentService::enabled(),
        ]);
    }

    public function receipt(ManualPayment $payment)
    {
        return ManualPaymentService::receiptResponse($payment);
    }

    public function approve(Request $r, ManualPayment $payment)
    {
        $d = $r->validate(['amount' => 'required|integer|min:1000|max:500000000']);
        $err = ManualPaymentService::approve($payment, $r->user(), (int) $d['amount']);

        return back()->with($err ? 'error' : 'success', $err ?: __("To'lov tasdiqlandi, klinika balansi to'ldirildi."));
    }

    public function reject(Request $r, ManualPayment $payment)
    {
        $d = $r->validate(['reason' => 'required|string|min:5|max:500'], ['reason.required' => __("Bekor qilish sababini yozing."), 'reason.min' => __("Sababni batafsilroq yozing (kamida 5 belgi).")]);
        $err = ManualPaymentService::reject($payment, $r->user(), $d['reason']);

        return back()->with($err ? 'error' : 'success', $err ?: __("To'lov bekor qilindi, klinikaga sabab ko'rsatildi."));
    }
}
