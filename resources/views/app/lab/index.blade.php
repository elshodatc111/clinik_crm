@extends('layouts.app')
@section('title', __('Tahlillar'))
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <form method="GET" class="flex gap-2"><select name="status" class="input" onchange="this.form.submit()"><option value="">{{ __('Barchasi') }}</option><option value="ordered" @selected(request('status') === 'ordered')>{{ __('Kutilmoqda') }}</option><option value="done" @selected(request('status') === 'done')>{{ __('Tayyor') }}</option></select></form>
    @if(auth()->user()->hasRole('clinic_admin', 'receptionist', 'doctor'))<a href="{{ route('app.lab.create') }}" class="btn btn-primary"><x-icon name="plus" class="w-4 h-4" />{{ __("Tahlil yo'llanmasi") }}</a>@endif
</div>
<div class="card overflow-x-auto">
    <table class="w-full">
        <thead><tr><th class="th">{{ __('Sana') }}</th><th class="th">{{ __('Bemor') }}</th><th class="th">{{ __('Tahlil') }}</th><th class="th">{{ __('Shifokor') }}</th><th class="th">{{ __('Holat') }}</th><th class="th"></th></tr></thead>
        @forelse($items as $l)
            <tr><td class="td whitespace-nowrap">{{ $l->ordered_at->format('d.m.Y H:i') }}</td><td class="td"><a class="link" href="{{ route('app.patients.show', $l->patient_id) }}">{{ $l->patient?->full_name }}</a></td>
                <td class="td">{{ $l->test_name }}<div class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($l->result_value, 80) }}</div></td><td class="td">{{ $l->doctor?->name }}</td>
                <td class="td"><x-badge :color="$l->status === 'done' ? 'green' : 'yellow'">{{ $l->status === 'done' ? __('Tayyor') : __('Kutilmoqda') }}</x-badge></td>
                <td class="td text-right whitespace-nowrap">@if($l->file_path)<a class="btn btn-sm" href="{{ route('app.lab.file', $l) }}" target="_blank">{{ __('Fayl') }}</a>@endif <a class="btn btn-sm" href="{{ route('app.lab.edit', $l) }}">{{ $l->status === 'done' ? __("Ko'rish/tahrir") : __('Natija kiritish') }}</a>
                    @if(auth()->user()->isDirector())<form method="POST" action="{{ route('app.lab.destroy', $l) }}" class="inline" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Tahlilni o\'chirasizmi? Bu amalni qaytarib bo\'lmaydi.')) }})">@csrf @method('DELETE')<button class="btn btn-sm !text-red-600" title="{{ __('O\'chirish') }}"><x-icon name="trash" class="w-3.5 h-3.5" /></button></form>@endif</td></tr>
        @empty<tr><td class="td text-slate-500" colspan="6">{{ __('Tahlillar yo\'q') }}</td></tr>@endforelse
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
