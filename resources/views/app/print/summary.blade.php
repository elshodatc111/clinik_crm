@extends('app.print.layout')
@section('doc_title', __("Tibbiy ma'lumotnoma"))
@section('docno', 'MM-'.str_pad((string) $visit->id, 6, '0', STR_PAD_LEFT))
@section('body')
<div class="title">{{ __("Tibbiy ma'lumotnoma") }}</div>
<div class="docno">№ MM-{{ str_pad((string) $visit->id, 6, '0', STR_PAD_LEFT) }} · {{ $visit->visited_at->format('d.m.Y') }}</div>
<div class="grid">
  <div><b>{{ __('Bemor') }}:</b> {{ $visit->patient?->full_name }}</div>
  <div><b>{{ __("Tug'ilgan sana") }}:</b> {{ $visit->patient?->birth_date?->format('d.m.Y') }} @if($visit->patient?->age() !== null)({{ $visit->patient->age() }} {{ __('yosh') }})@endif</div>
  <div><b>{{ __('Jinsi') }}:</b> {{ $visit->patient?->gender === 'm' ? __('Erkak') : ($visit->patient?->gender === 'f' ? __('Ayol') : '—') }}</div>
  <div><b>{{ __('JSHSHIR') }}:</b> {{ $visit->patient?->pinfl ?: '—' }}</div>
  <div><b>{{ __('Manzil') }}:</b> {{ $visit->patient?->address ?: '—' }}</div>
  <div><b>{{ __('Qon guruhi') }}:</b> {{ $visit->patient?->blood_group ?: '—' }}</div>
  <div><b>{{ __('Murojaat sanasi') }}:</b> {{ $visit->visited_at->format('d.m.Y H:i') }}</div>
  <div><b>{{ __('Shifokor') }}:</b> {{ $visit->doctor?->name }} @if($visit->doctor?->specialty)({{ $visit->doctor->specialty }})@endif</div>
</div>
<div class="grow">
  @foreach(['complaint' => 'Shikoyat', 'anamnesis' => 'Anamnez', 'examination' => "Ob'ektiv ko'rik"] as $k => $l)
    @if($visit->$k)<div class="box"><h4>{{ __($l) }}</h4>{!! nl2br(e($visit->$k)) !!}</div>@endif
  @endforeach
  @if($visit->diagnosis_text || $visit->diagnosis_code)<div class="box" style="border-color:{{ $brand['color'] }}"><h4>{{ __('Yakuniy tashxis') }}</h4><b>{{ $visit->diagnosis_code }}</b> {{ $visit->diagnosis_text }}</div>@endif
  @if($visit->prescriptions->count())
  <div class="box"><h4>{{ __('Tayinlangan davo') }}</h4>
    <table><tr><th>{{ __('Dori') }}</th><th>{{ __('Doza') }}</th><th>{{ __('Necha mahal') }}</th><th>{{ __('Muddati') }}</th></tr>
    @foreach($visit->prescriptions as $p)<tr><td>{{ $p->drug }}</td><td>{{ $p->dose }}</td><td>{{ $p->frequency }}</td><td>{{ $p->duration }}</td></tr>@endforeach</table></div>
  @endif
  @if($visit->recommendations)<div class="box"><h4>{{ __('Tavsiyalar') }}</h4>{!! nl2br(e($visit->recommendations)) !!}</div>@endif
</div>
<div class="sign">
  <div>{{ __('Shifokor') }}: <b>{{ $visit->doctor?->name }}</b> <span class="line"></span></div>
  <div style="text-align:center"><div class="stamp">{{ __("M.O'.") }}</div></div>
  <div>{{ __('Klinika rahbari') }}: <span class="line" style="width:110px"></span></div>
</div>
@endsection
