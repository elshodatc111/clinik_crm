<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #111; margin: 24px; max-width: 760px; }
        h1 { font-size: 18px; margin: 0; } table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #999; padding: 6px 8px; text-align: left; } th { background: #f0f0f0; }
        .r { text-align: right; } .head { display: flex; gap: 14px; align-items: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .muted { color: #555; font-size: 12px; } .sign { margin-top: 40px; display: flex; justify-content: space-between; }
        @media print { .noprint { display: none; } }
    </style>
</head>
<body>
@php($clinic = view()->shared('clinic'))
<div class="noprint" style="margin-bottom:12px"><button onclick="window.print()">{{ __('Chop etish') }}</button></div>
<div class="head">
    @if($clinic?->logoUrl())<img src="{{ $clinic->logoUrl() }}" style="height:60px" alt="">@endif
    <div>
        <h1>{{ $clinic?->name }}</h1>
        <div class="muted">{{ $clinic?->address }} · {{ phone_fmt($clinic?->phone) }} @if($clinic?->setting('phone2')) · {{ $clinic->setting('phone2') }} @endif</div>
        <div class="muted">@if($clinic?->setting('inn')){{ __('STIR') }}: {{ $clinic->setting('inn') }} @endif @if($clinic?->setting('license_no')) · {{ __('Litsenziya') }}: {{ $clinic->setting('license_no') }} @endif</div>
        @if($clinic?->setting('invoice_header'))<div class="muted">{{ $clinic->setting('invoice_header') }}</div>@endif
    </div>
</div>
<p><b>{{ __('Hisob-faktura') }} № {{ $invoice->number }}</b> — {{ $invoice->created_at->format('d.m.Y H:i') }}<br>{{ __('Bemor') }}: {{ $invoice->patient?->full_name }}</p>
<table>
    <tr><th>{{ __('Xizmat') }}</th><th>{{ __('Soni') }}</th><th>{{ __('Narx') }}</th><th class="r">{{ __('Summa') }}</th></tr>
    @foreach($invoice->items as $it)<tr><td>{{ $it->name }}</td><td>{{ $it->qty }}</td><td>{{ money($it->price) }}</td><td class="r">{{ money($it->qty * $it->price) }}</td></tr>@endforeach
    <tr><td colspan="3" class="r">{{ __('Jami') }}</td><td class="r">{{ money($invoice->total) }}</td></tr>
    @if($invoice->discount)<tr><td colspan="3" class="r">{{ __('Chegirma') }}</td><td class="r">−{{ money($invoice->discount) }}</td></tr>@endif
    <tr><td colspan="3" class="r"><b>{{ __("To'lanishi kerak") }}</b></td><td class="r"><b>{{ money($invoice->netTotal()) }}</b></td></tr>
    <tr><td colspan="3" class="r">{{ __("To'langan") }}</td><td class="r">{{ money($invoice->paid) }}</td></tr>
    <tr><td colspan="3" class="r">{{ __('Qoldiq') }}</td><td class="r">{{ money($invoice->remaining()) }}</td></tr>
</table>
<div class="sign"><span>{{ __('Kassir') }}: ____________</span><span>{{ __('Bemor') }}: ____________</span></div>
</body>
</html>
