@extends('layouts.app')
@section('title', __("Bemorlar (Super Admin)"))
@section('content')
<p class="text-sm text-slate-600 mb-3">{{ __("Bemorni faqat Super Admin o'chira oladi. O'chirilgan bemor arxivga tushadi va audit jurnalida qoladi.") }}</p>
<form method="GET" class="flex gap-2 mb-4"><input name="q" value="{{ request('q') }}" class="input max-w-sm" placeholder="{{ __('F.I.Sh., telefon, JSHSHIR') }}"><button class="btn"><x-icon name="search" class="w-4 h-4" />{{ __('Qidirish') }}</button></form>
<div class="card overflow-x-auto">
    <table class="w-full">
        <thead><tr><th class="th">{{ __('Bemor') }}</th><th class="th">{{ __('JSHSHIR') }}</th><th class="th">{{ __('Telefon') }}</th><th class="th">{{ __('Klinika') }}</th><th class="th"></th></tr></thead>
        @forelse($patients as $p)
            <tr><td class="td">{{ $p->full_name }}<div class="text-xs text-slate-500">{{ $p->birth_date?->format('d.m.Y') }}</div></td><td class="td">{{ $p->pinfl }}</td><td class="td">{{ phone_fmt($p->phone) }}</td><td class="td">{{ $p->clinic?->name }}</td>
            <td class="td"><form method="POST" action="{{ route('admin.patients.destroy', $p) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Bemorni o\'chirasizmi?')) }})">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">{{ __("O'chirish") }}</button></form></td></tr>
        @empty<tr><td class="td text-slate-500" colspan="5">{{ __('Topilmadi') }}</td></tr>@endforelse
    </table>
</div>
<div class="mt-4">{{ $patients->links() }}</div>
@endsection
