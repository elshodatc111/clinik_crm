@props(['icon' => 'activity', 'label', 'value', 'tone' => 'blue', 'hint' => null, 'href' => null])
@php
$tones = [
  'blue' => 'bg-blue-50 text-blue-600', 'green' => 'bg-emerald-50 text-emerald-600', 'amber' => 'bg-amber-50 text-amber-600',
  'red' => 'bg-red-50 text-red-600', 'violet' => 'bg-violet-50 text-violet-600', 'slate' => 'bg-slate-100 text-slate-600', 'cyan' => 'bg-cyan-50 text-cyan-600',
];
$tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif class="card p-5 flex items-start gap-4 {{ $href ? 'transition hover:-translate-y-0.5 hover:shadow-md' : '' }}">
    <span class="h-11 w-11 rounded-xl grid place-items-center {{ $tones[$tone] ?? $tones['blue'] }}"><x-icon :name="$icon" class="w-5 h-5" /></span>
    <div class="min-w-0">
        <div class="text-xs font-medium text-slate-500">{{ $label }}</div>
        <div class="mt-0.5 text-2xl font-bold tracking-tight text-slate-900 truncate">{{ $value }}</div>
        @if($hint)<div class="text-xs text-slate-500 mt-0.5">{{ $hint }}</div>@endif
    </div>
</{{ $tag }}>
