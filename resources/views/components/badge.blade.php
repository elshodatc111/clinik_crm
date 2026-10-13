@props(['color' => 'slate'])
@php
$map = [
    'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'red' => 'bg-red-50 text-red-700 ring-red-600/20', 'yellow' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'blue' => 'bg-blue-50 text-blue-700 ring-blue-600/20', 'slate' => 'bg-slate-100 text-slate-600 ring-slate-500/20', 'orange' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
];
@endphp
<span class="badge ring-1 ring-inset {{ $map[$color] ?? $map['slate'] }}">{{ $slot }}</span>
