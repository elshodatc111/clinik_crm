<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<title>@yield('doc_title') — {{ $visit->patient?->full_name }}</title>
<style>
  @page { size: @yield('page', 'A4'); margin: 12mm; }
  * { box-sizing: border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #0f172a; margin: 0; background: #e2e8f0; }
  .toolbar { position: sticky; top: 0; background: #0f172a; color: #fff; padding: 10px 16px; display: flex; gap: 8px; align-items: center; justify-content: space-between; }
  .toolbar button, .toolbar a { background: #2563eb; color: #fff; border: 0; padding: 8px 16px; border-radius: 8px; font-size: 13px; cursor: pointer; text-decoration: none; }
  .toolbar a.sec { background: #334155; }
  .sheet { background: #fff; width: @yield('width', '210mm'); min-height: @yield('height', '297mm'); margin: 16px auto; padding: 14mm 14mm 12mm; position: relative; box-shadow: 0 8px 30px rgba(15,23,42,.15); display: flex; flex-direction: column; }
  .lh { display: flex; gap: 14px; align-items: center; padding-bottom: 10px; border-bottom: 3px solid {{ $brand['color'] }}; }
  .lh img { height: 58px; max-width: 120px; object-fit: contain; }
  .lh .logo-ph { height: 52px; width: 52px; border-radius: 12px; background: {{ $brand['color'] }}; color: #fff; display: grid; place-items: center; font-size: 22px; font-weight: 700; }
  .lh h1 { margin: 0; font-size: 20px; letter-spacing: .2px; color: {{ $brand['color'] }}; }
  .lh .meta { color: #475569; font-size: 11.5px; line-height: 1.5; }
  .title { text-align: center; margin: 16px 0 4px; font-size: 17px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
  .docno { text-align: center; color: #64748b; font-size: 11.5px; margin-bottom: 14px; }
  .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 24px; margin-bottom: 12px; font-size: 12.5px; }
  .grid b { color: #475569; font-weight: 600; }
  .box { border: 1px solid #cbd5e1; border-radius: 8px; padding: 9px 12px; margin: 8px 0; }
  .box h4 { margin: 0 0 4px; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; color: #64748b; }
  table { width: 100%; border-collapse: collapse; margin-top: 6px; }
  th { background: #f1f5f9; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .4px; color: #475569; }
  th, td { border: 1px solid #cbd5e1; padding: 6px 8px; }
  .grow { flex: 1; }
  .sign { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 26px; font-size: 12.5px; }
  .line { display: inline-block; width: 150px; border-bottom: 1px solid #0f172a; margin: 0 6px; }
  .stamp { width: 86px; height: 86px; border: 2px dashed #94a3b8; border-radius: 50%; display: grid; place-items: center; color: #94a3b8; font-size: 10px; text-align: center; }
  .foot { border-top: 1px solid #cbd5e1; margin-top: 16px; padding-top: 8px; color: #64748b; font-size: 10.5px; display: flex; justify-content: space-between; }
  .rx { font-size: 34px; font-weight: 700; color: {{ $brand['color'] }}; font-family: Georgia, serif; line-height: 1; margin: 6px 0; }
  @media print { body { background: #fff; } .toolbar { display: none; } .sheet { margin: 0; box-shadow: none; width: auto; min-height: auto; padding: 0; } }
</style>
</head>
<body>
@php($clinic = view()->shared('clinic'))
<div class="toolbar">
  <span>{{ __('Chop etishga tayyor hujjat') }}</span>
  <span style="display:flex;gap:8px"><a class="sec" href="{{ route('app.visits.show', $visit) }}">← {{ __('Orqaga') }}</a><button onclick="window.print()">{{ __('Chop etish') }}</button></span>
</div>
<div class="sheet">
  <div class="lh">
    @if($clinic?->logoUrl())<img src="{{ $clinic->logoUrl() }}" alt="">@else<div class="logo-ph">{{ mb_substr($clinic?->name ?? 'K', 0, 1) }}</div>@endif
    <div>
      <h1>{{ $clinic?->name }}</h1>
      <div class="meta">
        {{ $clinic?->address }}<br>
        {{ __('Tel') }}: {{ phone_fmt($clinic?->phone) }}@if($clinic?->setting('phone2')), {{ $clinic->setting('phone2') }}@endif
        @if($clinic?->setting('working_hours')) · {{ $clinic->setting('working_hours') }}@endif<br>
        @if($clinic?->setting('inn')){{ __('STIR') }}: {{ $clinic->setting('inn') }} @endif
        @if($clinic?->setting('license_no')) · {{ __('Litsenziya') }}: {{ $clinic->setting('license_no') }}@endif
      </div>
    </div>
  </div>
  @yield('body')
  <div class="foot">
    <span>{{ $clinic?->setting('website') }} @if($clinic?->setting('telegram')) · {{ $clinic->setting('telegram') }} @endif @if($clinic?->setting('instagram')) · {{ $clinic->setting('instagram') }} @endif</span>
    <span>{{ __('Hujjat') }} № @yield('docno') · {{ now()->format('d.m.Y H:i') }}</span>
  </div>
</div>
</body>
</html>
