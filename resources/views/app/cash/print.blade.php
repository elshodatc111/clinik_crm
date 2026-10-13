<!doctype html><html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><title>{{ __('Kunlik kassa hisoboti') }} {{ $date }}</title>
<style>
@page{size:A4;margin:12mm}*{box-sizing:border-box}body{font-family:"Segoe UI",Arial,sans-serif;font-size:12.5px;color:#0f172a;margin:0;background:#e2e8f0}
.bar{position:sticky;top:0;background:#0f172a;color:#fff;padding:10px 16px;display:flex;justify-content:space-between}.bar button{background:#2563eb;color:#fff;border:0;padding:8px 16px;border-radius:8px;cursor:pointer}
.sheet{background:#fff;width:210mm;min-height:297mm;margin:16px auto;padding:14mm;box-shadow:0 8px 30px rgba(15,23,42,.15)}
h1{margin:0 0 2px;font-size:18px;color:{{ $brand["color"] }} }h2{font-size:14px;text-transform:uppercase;letter-spacing:.5px;margin:4px 0 12px}
table{width:100%;border-collapse:collapse;margin:8px 0 16px}th{background:#f1f5f9;font-size:11px;text-transform:uppercase;text-align:left;color:#475569}th,td{border:1px solid #cbd5e1;padding:6px 8px}.r{text-align:right}
.sum{display:grid;grid-template-columns:1fr 1fr;gap:4px 24px;margin-bottom:14px}.sum div{display:flex;justify-content:space-between;border-bottom:1px dashed #cbd5e1;padding:3px 0}
.sign{display:flex;justify-content:space-between;margin-top:40px}.line{display:inline-block;width:160px;border-bottom:1px solid #0f172a}
@media print{body{background:#fff}.bar{display:none}.sheet{margin:0;box-shadow:none;width:auto;min-height:auto;padding:0}}
</style>
</head><body>
@php($clinic = view()->shared('clinic'))
@php($methods = \App\Models\Invoice::methods())
<div class="bar"><span>{{ __('Chop etishga tayyor hujjat') }}</span><button onclick="window.print()">{{ __('Chop etish') }}</button></div>
<div class="sheet">
<h1>{{ $clinic?->name }}</h1><div style="color:#64748b;margin-bottom:10px">{{ $clinic?->address }}</div>
<h2>{{ __('Kunlik kassa hisoboti') }} — {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}</h2>
<div class="sum">
<div><span>{{ __('Kun boshidagi qoldiq') }}</span><b>{{ money($opening) }}</b></div>
<div><span>{{ __('Jami tushum') }}</span><b>{{ money($total) }}</b></div>
<div><span>{{ __('Naqd tushum') }}</span><b>{{ money($cashIn) }}</b></div>
<div><span>{{ __('Naqdsiz tushum') }}</span><b>{{ money($nonCash) }}</b></div>
<div><span>{{ __('Chiqimlar (naqd)') }}</span><b>{{ money($cashOut) }}</b></div>
<div><span>{{ __('Kassada bo\'lishi kerak') }}</span><b>{{ money($expected) }}</b></div>
@if($closing)<div><span>{{ __('Sanalgan naqd') }}</span><b>{{ money($closing->counted_cash) }}</b></div><div><span>{{ __('Farq') }}</span><b>{{ money($closing->difference) }}</b></div>@endif
</div>
<h2>{{ __('To\'lovlar') }}</h2>
<table><thead><tr><th>{{ __('Vaqt') }}</th><th>{{ __('Bemor') }}</th><th>{{ __('Hisob-faktura') }}</th><th>{{ __('Usul') }}</th><th>{{ __('Kassir') }}</th><th class="r">{{ __('Summa') }}</th></tr></thead>
@foreach($payments as $p)<tr><td>{{ $p->paid_at->format('H:i') }}</td><td>{{ $p->ownerPatient()?->full_name }}</td><td>{{ $p->invoice?->number ?? __('Avans') }}</td><td>{{ __($methods[$p->method] ?? $p->method) }}</td><td>{{ $p->cashier?->name }}</td><td class="r">{{ money($p->amount) }}</td></tr>@endforeach
</table>
<h2>{{ __('Kassa harakatlari') }}</h2>
<table><thead><tr><th>{{ __('Turi') }}</th><th>{{ __('Izoh') }}</th><th>{{ __('Usul') }}</th><th class="r">{{ __('Summa') }}</th></tr></thead>
@foreach($moves as $m)<tr><td>{{ __(\App\Models\CashMovement::categories()[$m->category] ?? $m->category) }}</td><td>{{ $m->note }}</td><td>{{ $m->method }}</td><td class="r">{{ $m->direction === 'in' ? '+' : '-' }} {{ money($m->amount) }}</td></tr>@endforeach
</table>
<div class="sign"><span>{{ __('Kassir') }}: <span class="line"></span></span><span>{{ __('Direktor') }}: <span class="line"></span></span></div>
</div></body></html>
