<!doctype html><html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><title>{{ __('Ish haqi qaydnomasi') }} {{ $period }}</title>
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
<div class="bar"><span>{{ __('Chop etishga tayyor hujjat') }}</span><button onclick="window.print()">{{ __('Chop etish') }}</button></div>
<div class="sheet">
<h1>{{ $clinic?->name }}</h1><div style="color:#64748b;margin-bottom:10px">{{ $clinic?->address }}</div>
<h2>{{ __('Ish haqi qaydnomasi') }} — {{ $period }}</h2>
<table>
<thead><tr><th>№</th><th>{{ __('F.I.Sh.') }}</th><th>{{ __('Turi') }}</th><th class="r">{{ __('Asosiy') }}</th><th class="r">{{ __('Foiz') }}</th><th class="r">{{ __('Mukofot') }}</th><th class="r">{{ __('Jarima') }}</th><th class="r">{{ __('Avans') }}</th><th class="r">{{ __('To\'lanadi') }}</th><th>{{ __('Imzo') }}</th></tr></thead>
@foreach($rows as $i => $p)
<tr><td>{{ $i + 1 }}</td><td>{{ $p->user?->name }}<br><small style="color:#64748b">{{ $p->user?->roleLabel() }}</small></td><td>{{ __(\App\Services\PayrollService::types()[$p->pay_type] ?? $p->pay_type) }}</td>
<td class="r">{{ money($p->base_amount) }}</td><td class="r">{{ money($p->percent_amount) }}</td><td class="r">{{ money($p->bonus) }}</td><td class="r">{{ money($p->penalty) }}</td><td class="r">{{ money($p->advance) }}</td><td class="r"><b>{{ money($p->net) }}</b></td><td style="width:70px"></td></tr>
@endforeach
<tr><td colspan="8" class="r"><b>{{ __('Jami') }}</b></td><td class="r"><b>{{ money($rows->sum('net')) }}</b></td><td></td></tr>
</table>
<div class="sign"><span>{{ __('Direktor') }}: <span class="line"></span></span><span>{{ __('Buxgalter') }}: <span class="line"></span></span></div>
</div></body></html>
