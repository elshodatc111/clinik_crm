@extends('app.print.layout')
@section('doc_title', __('Retsept'))
@section('page', 'A5')
@section('width', '148mm')
@section('height', '210mm')
@section('docno', 'RX-'.str_pad((string) $visit->id, 6, '0', STR_PAD_LEFT))
@section('body')
<div class="title">{{ __('Retsept') }}</div>
<div class="docno">№ RX-{{ str_pad((string) $visit->id, 6, '0', STR_PAD_LEFT) }} · {{ $visit->visited_at->format('d.m.Y') }}</div>
<div class="grid">
  <div><b>{{ __('Bemor') }}:</b> {{ $visit->patient?->full_name }}</div>
  <div><b>{{ __("Tug'ilgan sana") }}:</b> {{ $visit->patient?->birth_date?->format('d.m.Y') }} @if($visit->patient?->age() !== null)({{ $visit->patient->age() }} {{ __('yosh') }})@endif</div>
  @if($visit->diagnosis_text || $visit->diagnosis_code)<div style="grid-column:1/-1"><b>{{ __('Tashxis') }}:</b> {{ $visit->diagnosis_code }} {{ $visit->diagnosis_text }}</div>@endif
  @if($visit->patient?->allergies)<div style="grid-column:1/-1;color:#b91c1c"><b style="color:#b91c1c">{{ __('Allergiya') }}:</b> {{ $visit->patient->allergies }}</div>@endif
</div>
<div class="rx">℞</div>
<div class="grow">
@if($visit->prescriptions->count())
<table>
  <tr><th style="width:28px">№</th><th>{{ __('Dori') }}</th><th>{{ __('Doza') }}</th><th>{{ __('Necha mahal') }}</th><th>{{ __('Muddati') }}</th></tr>
  @foreach($visit->prescriptions as $i => $p)<tr><td>{{ $i + 1 }}</td><td><b>{{ $p->drug }}</b></td><td>{{ $p->dose }}</td><td>{{ $p->frequency }}</td><td>{{ $p->duration }}</td></tr>@endforeach
</table>
@else<p style="color:#64748b">{{ __("Dori vositalari belgilanmagan") }}</p>@endif
@if($visit->recommendations)<div class="box"><h4>{{ __('Tavsiyalar') }}</h4>{!! nl2br(e($visit->recommendations)) !!}</div>@endif
</div>
<div class="sign">
  <div>{{ __('Shifokor') }}: <b>{{ $visit->doctor?->name }}</b><br><span style="color:#64748b">{{ $visit->doctor?->specialty }}@if($visit->doctor?->room) · {{ __('Kabinet') }} {{ $visit->doctor->room }}@endif</span><br><br>{{ __('Imzo') }}: <span class="line"></span></div>
  <div class="stamp">{{ __("M.O'.") }}</div>
</div>
@endsection
