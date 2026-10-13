<?php

use Illuminate\Support\Facades\Schedule;

// Har kuni tunda: obuna, demo, bloklash va o'chirish jarayonlari
Schedule::command('billing:run')->dailyAt('02:00');

// Har 10 daqiqada: qabul eslatmalari (klinikaning o'z Eskiz akkaunti orqali)
Schedule::command('reminders:send')->everyTenMinutes();

// Har daqiqada: to'lanmagan onlayn bronlarni bo'shatish
Schedule::call(fn () => \App\Services\BookingService::expireStale())->everyMinute()->name('bookings:expire');

// Har kuni ertalab: klinikalar kamchiliklari bo'yicha ilova ichidagi xabarlar
Schedule::command('clinics:diagnose')->dailyAt('09:00');
