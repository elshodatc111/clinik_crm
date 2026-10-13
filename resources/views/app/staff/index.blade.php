@extends('layouts.app')
@section('title', __('Xodimlar'))
@section('content')
<div class="flex justify-end mb-4"><a href="{{ route('app.staff.create') }}" class="btn btn-primary"><x-icon name="plus" class="w-4 h-4" />{{ __("Xodim qo'shish") }}</a></div>
<div class="card overflow-x-auto">
    <table class="w-full">
        <thead><tr><th class="th">{{ __('F.I.Sh.') }}</th><th class="th">{{ __('Telefon') }}</th><th class="th">{{ __('Rol') }}</th><th class="th">{{ __('Mutaxassislik') }}</th><th class="th">{{ __('Faol') }}</th><th class="th"></th></tr></thead>
        @foreach($staff as $s)
            <tr><td class="td">{{ $s->name }}</td><td class="td">{{ phone_fmt($s->phone) }}</td><td class="td">{{ $s->roleLabel() }}</td><td class="td">{{ $s->specialty }} {{ $s->room ? '· '.__('xona').' '.$s->room : '' }}</td><td class="td">{{ $s->is_active ? '✓' : '✗' }}</td>
            <td class="td text-right"><a class="btn btn-sm" href="{{ route('app.staff.edit', $s) }}">{{ __('Tahrirlash') }}</a></td></tr>
        @endforeach
    </table>
</div>
@endsection
