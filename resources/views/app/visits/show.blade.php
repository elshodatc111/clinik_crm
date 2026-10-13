@extends('layouts.app')
@section('title', __('Tashrif').' — '.$visit->visited_at->format('d.m.Y'))
@section('content')
<div class="card p-6 max-w-4xl">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a class="link text-lg font-bold" href="{{ route('app.patients.show', $visit->patient_id) }}">{{ $visit->patient?->full_name }}</a>
            <div class="text-sm text-slate-500">{{ $visit->visited_at->format('d.m.Y H:i') }} · {{ $visit->doctor?->name }}</div>
        </div>
        <div class="flex gap-2 print:hidden">
            <a class="btn btn-sm" href="{{ route('app.visits.edit', $visit) }}">{{ __('Tahrirlash') }}</a>
            <a class="btn btn-sm" href="{{ route('app.invoices.create', ['visit' => $visit->id]) }}"><x-icon name="plus" class="w-4 h-4" />{{ __('Hisob-faktura') }}</a>
            <a class="btn btn-sm" href="{{ route('app.lab.create', ['patient' => $visit->patient_id]) }}"><x-icon name="plus" class="w-4 h-4" />{{ __('Tahlil') }}</a>
            <a class="btn btn-sm" target="_blank" href="{{ route('app.visits.print.rx', $visit) }}"><x-icon name="printer" class="w-4 h-4" />{{ __('Retsept') }}</a>
            <a class="btn btn-sm" target="_blank" href="{{ route('app.visits.print.summary', $visit) }}"><x-icon name="printer" class="w-4 h-4" />{{ __("Ma'lumotnoma") }}</a>
        </div>
    </div>
    <dl class="mt-5 space-y-4 text-sm">
        @foreach(['complaint' => 'Shikoyat', 'anamnesis' => 'Anamnez', 'examination' => "Ob'ektiv ko'rik", 'recommendations' => 'Tavsiyalar'] as $k => $l)
            @if($visit->$k)<div><dt class="font-semibold text-slate-600">{{ __($l) }}</dt><dd class="whitespace-pre-line">{{ $visit->$k }}</dd></div>@endif
        @endforeach
        @if($visit->diagnosis_text || $visit->diagnosis_code)<div><dt class="font-semibold text-slate-600">{{ __('Tashxis') }}</dt><dd>{{ $visit->diagnosis_code }} {{ $visit->diagnosis_text }}</dd></div>@endif
    </dl>
    @if($visit->prescriptions->count())
        <div class="mt-5 font-semibold">{{ __('Retsept') }}</div>
        <table class="w-full mt-1"><thead><tr><th class="th">{{ __('Dori') }}</th><th class="th">{{ __('Doza') }}</th><th class="th">{{ __('Necha mahal') }}</th><th class="th">{{ __('Muddati') }}</th></tr></thead>
        @foreach($visit->prescriptions as $p)<tr><td class="td">{{ $p->drug }}</td><td class="td">{{ $p->dose }}</td><td class="td">{{ $p->frequency }}</td><td class="td">{{ $p->duration }}</td></tr>@endforeach</table>
    @endif
</div>
@endsection
