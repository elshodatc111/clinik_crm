@extends('layouts.auth')
@section('title', __("To'xtatilgan"))
@section('content')
<h1 class="text-xl font-bold text-red-700">{{ __("Klinika to'xtatilgan") }}</h1>
<p class="text-sm text-slate-600 mt-2">{{ __("Klinikangiz administrator tomonidan vaqtincha to'xtatilgan. Ma'lumot uchun biz bilan bog'laning.") }}</p>
@if($brand['phone'])<p class="mt-3 font-medium">{{ $brand['phone'] }}</p>@endif
<form method="POST" action="{{ route('logout') }}" class="mt-4" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Tizimdan chiqasizmi?')) }})">@csrf<button class="btn">{{ __('Chiqish') }}</button></form>
@endsection
