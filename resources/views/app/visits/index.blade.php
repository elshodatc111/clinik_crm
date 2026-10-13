@extends('layouts.app')
@section('title', __('Tashriflar (tibbiy karta)'))
@section('content')
<div class="card overflow-x-auto">
    <table class="w-full">
        <thead><tr><th class="th">{{ __('Sana') }}</th><th class="th">{{ __('Bemor') }}</th><th class="th">{{ __('Shifokor') }}</th><th class="th">{{ __('Tashxis') }}</th><th class="th"></th></tr></thead>
        @forelse($visits as $v)
            <tr><td class="td whitespace-nowrap">{{ $v->visited_at->format('d.m.Y H:i') }}</td>
                <td class="td"><a class="link" href="{{ route('app.patients.show', $v->patient_id) }}">{{ $v->patient?->full_name }}</a></td>
                <td class="td">{{ $v->doctor?->name }}</td><td class="td">{{ $v->diagnosis_code }} {{ \Illuminate\Support\Str::limit($v->diagnosis_text, 80) }}</td>
                <td class="td text-right"><a class="btn btn-sm" href="{{ route('app.visits.show', $v) }}">{{ __("Ko'rish") }}</a></td></tr>
        @empty<tr><td class="td text-slate-500" colspan="5">{{ __("Tashriflar yo'q") }}</td></tr>@endforelse
    </table>
</div>
<div class="mt-4">{{ $visits->links() }}</div>
@endsection
