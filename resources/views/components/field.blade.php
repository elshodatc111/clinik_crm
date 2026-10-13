@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'options' => [], 'required' => false, 'hint' => null, 'rows' => 3, 'placeholder' => null, 'span' => 1, 'attrs' => ''])
@php($val = old($name, $value))
<div @class(['md:col-span-2' => $span == 2])>
    @if($type === 'checkbox')
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="hidden" name="{{ $name }}" value="0">
            <input type="checkbox" name="{{ $name }}" value="1" @checked($val) class="rounded border-slate-300">
            {{ $label }}
        </label>
    @else
        @if($label)
            <label for="f_{{ $name }}" class="block text-sm font-medium text-slate-700 mb-1">{{ $label }}@if($required)<span class="text-red-500"> *</span>@endif</label>
        @endif
        @if($type === 'select')
            <select id="f_{{ $name }}" name="{{ $name }}" class="input" @required($required)>
                @foreach($options as $k => $v)
                    <option value="{{ $k }}" @selected((string) $val === (string) $k)>{{ $v }}</option>
                @endforeach
            </select>
        @elseif($type === 'textarea')
            <textarea id="f_{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" class="input" placeholder="{{ $placeholder }}" @required($required)>{{ $val }}</textarea>
        @elseif($type === 'file')
            <input id="f_{{ $name }}" type="file" name="{{ $name }}" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm" {!! $attrs !!}>
        @else
            <input id="f_{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : $val }}" class="input" placeholder="{{ $placeholder }}" @required($required) {!! $attrs !!}>
        @endif
    @endif
    @if($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
    @error($name)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
