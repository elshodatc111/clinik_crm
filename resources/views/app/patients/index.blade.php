@extends('layouts.app')
@section('title', __('Bemorlar'))
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <form method="GET" class="flex gap-2"><input name="q" value="{{ request('q') }}" class="input w-72" placeholder="{{ __('F.I.Sh., telefon yoki JSHSHIR') }}"><button class="btn"><x-icon name="search" class="w-4 h-4" />{{ __('Qidirish') }}</button></form>
    @if(auth()->user()->hasRole('clinic_admin', 'receptionist'))<a href="{{ route('app.patients.create') }}" class="btn btn-primary"><x-icon name="plus" class="w-4 h-4" />{{ __('Yangi bemor') }}</a>@endif
</div>
<div class="card overflow-x-auto">
    <table class="w-full">
        <thead><tr><th class="th">{{ __('Bemor') }}</th><th class="th">{{ __("Tug'ilgan sana") }}</th><th class="th">{{ __('Telefon') }}</th><th class="th">{{ __('JSHSHIR') }}</th><th class="th"></th></tr></thead>
        @forelse($patients as $p)
            <tr><td class="td"><a class="link font-medium" href="{{ route('app.patients.show', $p) }}">{{ $p->full_name }}</a></td>
                <td class="td">{{ $p->birth_date?->format('d.m.Y') }} @if($p->age() !== null)<span class="text-xs text-slate-500">({{ $p->age() }})</span>@endif</td>
                <td class="td">{{ phone_fmt($p->phone) }}</td><td class="td">{{ $p->pinfl }}</td>
                <td class="td text-right"><a class="btn btn-sm" href="{{ route('app.patients.show', $p) }}">{{ __('Karta') }}</a></td></tr>
        @empty<tr><td class="td text-slate-500" colspan="5">{{ __('Bemorlar topilmadi') }}</td></tr>@endforelse
    </table>
</div>
<div class="mt-4">{{ $patients->links() }}</div>
@endsection
