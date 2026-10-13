@if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif
@if($errors->any() && ! $errors->has('duplicate'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <div class="font-medium mb-1">{{ __("Maydonlarni to'g'ri to'ldiring:") }}</div>
        <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
@if($errors->has('duplicate'))
    <div class="mb-4 rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
        {{ $errors->first('duplicate') }}
        @if(session('duplicate_id'))
            <a class="link font-medium" href="{{ route('app.patients.show', session('duplicate_id')) }}">{{ __("Mavjud bemorni ko'rish") }}</a>
        @endif
    </div>
@endif
