<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Visit;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $r)
    {
        $q = Invoice::with('patient')->latest('id')
            ->when($r->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($r->query('from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($r->query('to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
        if ($s = trim((string) $r->query('q'))) {
            $q->where(fn ($w) => $w->where('number', 'like', "%$s%")->orWhereHas('patient', fn ($p) => $p->where('last_name', 'like', "%$s%")->orWhere('first_name', 'like', "%$s%")));
        }

        return view('app.invoices.index', ['invoices' => $q->paginate(25)->withQueryString()]);
    }

    public function create(Request $r)
    {
        $visit = $r->query('visit') ? Visit::with(['appointment.service', 'patient'])->findOrFail($r->query('visit')) : null;
        $patient = $visit?->patient ?? ($r->query('patient') ? Patient::findOrFail($r->query('patient')) : null);
        $services = Service::where('is_active', true)->orderBy('name')->get(['id', 'name', 'price']);

        $rows = [];
        if ($svc = $visit?->appointment?->service) {
            $rows[] = ['service_id' => $svc->id, 'name' => $svc->name, 'qty' => 1, 'price' => $svc->price];
        }

        return view('app.invoices.form', compact('patient', 'visit', 'services', 'rows'));
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'patient_id' => 'required|integer',
            'visit_id' => 'nullable|integer',
            'discount' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1|max:50',
            'items.*.service_id' => 'nullable|integer',
            'items.*.name' => 'required|string|max:200',
            'items.*.qty' => 'required|integer|min:1|max:1000',
            'items.*.price' => 'required|integer|min:0',
        ]);
        $patient = Patient::findOrFail($d['patient_id']);
        $visit = $d['visit_id'] ? Visit::findOrFail($d['visit_id']) : null;

        $total = collect($d['items'])->sum(fn ($i) => $i['qty'] * $i['price']);
        $discount = (int) ($d['discount'] ?? 0);
        if ($discount > $total) {
            return back()->withInput()->withErrors(['discount' => __('Chegirma umumiy summadan katta bo\'lishi mumkin emas.')]);
        }

        $invoice = DB::transaction(function () use ($d, $patient, $visit, $total, $discount, $r) {
            $inv = Invoice::create([
                'patient_id' => $patient->id, 'visit_id' => $visit?->id, 'doctor_id' => $visit?->doctor_id,
                'number' => Invoice::nextNumber(Tenant::id()), 'total' => $total, 'discount' => $discount,
                'paid' => 0, 'status' => $total - $discount <= 0 ? 'paid' : 'unpaid',
                'notes' => $d['notes'] ?? null, 'created_by' => $r->user()->id,
            ]);
            foreach ($d['items'] as $i) {
                $serviceId = ! empty($i['service_id']) ? Service::find($i['service_id'])?->id : null;
                InvoiceItem::create(['invoice_id' => $inv->id, 'service_id' => $serviceId, 'name' => $i['name'], 'qty' => $i['qty'], 'price' => $i['price']]);
            }

            return $inv;
        });

        return redirect()->route('app.invoices.show', $invoice)->with('success', __('Hisob-faktura yaratildi.'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['patient', 'items', 'payments.cashier', 'doctor']);

        return view('app.invoices.show', compact('invoice'));
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['patient', 'items', 'payments']);

        return view('app.invoices.print', compact('invoice'));
    }

    public function pay(Request $r, Invoice $invoice)
    {
        $d = $r->validate([
            'amount' => 'required|integer|min:1',
            'method' => 'required|in:'.implode(',', array_keys(Invoice::methods())),
            'note' => 'nullable|string|max:200',
            'overpay' => 'nullable|boolean',
        ]);

        $err = DB::transaction(function () use ($invoice, $d, $r) {
            $inv = Invoice::whereKey($invoice->id)->lockForUpdate()->first();
            if ($inv->status === 'canceled') {
                return __('Bekor qilingan hisob-fakturaga to\'lov qabul qilinmaydi.');
            }
            $excess = max(0, $d['amount'] - $inv->remaining());
            if ($excess > 0 && ! $r->boolean('overpay')) {
                return __("To'lov summasi qoldiqdan oshib ketdi. Qoldiq: :s. Ortiqchasini avans sifatida qoldirish uchun belgini qo'ying.", ['s' => \App\Support\Tenant::money($inv->remaining())]);
            }
            $toInvoice = $d['amount'] - $excess;
            if ($toInvoice > 0) {
                Payment::create([
                    'invoice_id' => $inv->id, 'amount' => $toInvoice, 'method' => $d['method'],
                    'cashier_id' => $r->user()->id, 'paid_at' => now(), 'note' => $d['note'] ?? null,
                ]);
            }
            if ($excess > 0) {
                // Ortiqcha to'lov bemorning avansiga o'tadi
                Payment::create([
                    'invoice_id' => null, 'patient_id' => $inv->patient_id, 'amount' => $excess, 'method' => $d['method'],
                    'cashier_id' => $r->user()->id, 'paid_at' => now(), 'note' => __('Ortiqcha to\'lov').' '.$inv->number,
                ]);
            }
            $inv->paid += $toInvoice;
            $inv->status = $inv->remaining() === 0 ? 'paid' : 'partial';
            $inv->save();

            return null;
        });

        return back()->with($err ? 'error' : 'success', $err ?: __("To'lov qabul qilindi."));
    }

    public function cancel(Request $r, Invoice $invoice)
    {
        abort_unless($r->user()->hasRole('clinic_admin'), 403);
        if ($invoice->paid > 0) {
            return back()->with('error', __("To'lov qabul qilingan hisob-fakturani bekor qilib bo'lmaydi."));
        }
        $invoice->update(['status' => 'canceled']);

        return back()->with('success', __('Hisob-faktura bekor qilindi.'));
    }
}
