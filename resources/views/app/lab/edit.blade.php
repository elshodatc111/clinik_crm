@extends('layouts.app')
@section('title', __('Tahlil natijasi'))
@section('content')
<form method="POST" action="{{ route('app.lab.update', $lab) }}" enctype="multipart/form-data" class="card p-6 max-w-3xl space-y-4">
    @csrf @method('PUT')
    <div><div class="font-bold text-lg">{{ $lab->test_name }}</div><div class="text-sm text-slate-500">{{ $lab->patient?->full_name }} · {{ $lab->ordered_at->format('d.m.Y H:i') }}</div></div>
    <x-field name="result_value" type="textarea" :label="__('Natija')" :value="$lab->result_value" rows="5" />
    <x-field name="norm" :label="__('Me\'yor')" :value="$lab->norm" />
    <x-field name="result_note" type="textarea" :label="__('Izoh')" :value="$lab->result_note" rows="2" />
    <div>
        <x-field name="file" type="file" :label="__('Natija fayli (PDF/rasm, 5 MB gacha)')" attrs='accept=".pdf,.jpg,.jpeg,.png"' />
        @if($lab->file_path)<a class="link text-sm" target="_blank" href="{{ route('app.lab.file', $lab) }}">{{ __('Joriy faylni ochish') }}</a>@endif
    </div>
    @if($lab->status !== 'done')<x-field name="notify" type="checkbox" :label="__('Natija tayyor bo\'lganda bemorga SMS yuborish')" />@endif
    <div class="flex gap-2"><button class="btn btn-primary">{{ __('Saqlash') }}</button><a class="btn" href="{{ route('app.lab.index') }}">{{ __('Bekor qilish') }}</a></div>
</form>
@endsection
