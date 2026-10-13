<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\App as AppC;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PaymeController;
use Illuminate\Support\Facades\Route;

// ---------- Ommaviy qism ----------
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/offer', [LandingController::class, 'offer'])->name('offer');
Route::get('/privacy', [LandingController::class, 'privacy'])->name('privacy');
Route::get('/lang/{locale}', [LandingController::class, 'lang'])->name('lang');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:60,1')->name('login.post');
    Route::get('/login/2fa', [AuthController::class, 'twoFactorForm'])->name('login.2fa');
    Route::post('/login/2fa', [AuthController::class, 'twoFactor'])->middleware('throttle:20,1')->name('login.2fa.post');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:20,10')->name('register.post');
    Route::get('/register/verify', [AuthController::class, 'verifyForm'])->name('register.verify');
    Route::post('/register/verify', [AuthController::class, 'verify'])->middleware('throttle:30,10')->name('register.verify.post');
    Route::post('/register/resend', [AuthController::class, 'resend'])->middleware('throttle:6,10')->name('register.resend');
});

// Xodim ofertani qabul qilish (kirgandan keyin majburiy)
Route::middleware('auth')->group(function () {
    Route::get('/offer/accept', [\App\Http\Controllers\OfferAcceptController::class, 'show'])->name('offer.accept');
    Route::post('/offer/accept', [\App\Http\Controllers\OfferAcceptController::class, 'accept'])->middleware('throttle:20,1')->name('offer.accept.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('/dashboard', [AuthController::class, 'home'])->name('dashboard')->middleware('auth');

// Payme Merchant API (JSON-RPC): platforma obunalari uchun
Route::post('/payme', [PaymeController::class, 'handle'])->name('payme');
// Har bir klinikaning o'z Payme kassasi (onlayn qabul to'lovlari)
Route::post('/payme/c/{slug}', [\App\Http\Controllers\ClinicPaymeController::class, 'handle'])->name('payme.clinic');

// Klinikaning ommaviy onlayn qabul sahifasi
Route::prefix('k/{slug}')->name('booking.')->group(function () {
    $b = \App\Http\Controllers\BookingController::class;
    Route::get('/', [$b, 'show'])->name('show');
    Route::get('/offer', [$b, 'offer'])->name('offer');
    Route::get('/slots', [$b, 'slots'])->middleware('throttle:60,1')->name('slots');
    Route::post('/book', [$b, 'book'])->middleware('throttle:10,1')->name('book');
    Route::get('/t/{token}', [$b, 'ticket'])->name('ticket');
    Route::get('/t/{token}/status', [$b, 'status'])->middleware('throttle:60,1')->name('status');
    Route::post('/t/{token}/simulate', [$b, 'simulate'])->name('simulate');
});

// Shaxsiy profil (har bir xodim va super admin uchun)
Route::middleware(['auth', 'offer'])->prefix('account')->name('account.')->group(function () {
    $a = \App\Http\Controllers\AccountController::class;
    Route::get('/', [$a, 'edit'])->name('edit');
    Route::put('/', [$a, 'update'])->name('update');
    Route::put('password', [$a, 'password'])->name('password');
    Route::delete('avatar', [$a, 'removeAvatar'])->name('avatar.delete');
    $t = \App\Http\Controllers\TwoFactorController::class;
    Route::post('2fa/start', [$t, 'start'])->name('2fa.start');
    Route::post('2fa/confirm', [$t, 'confirm'])->middleware('throttle:10,1')->name('2fa.confirm');
    Route::post('2fa/disable', [$t, 'disable'])->name('2fa.disable');
    Route::post('2fa/recovery', [$t, 'recovery'])->middleware('throttle:5,1')->name('2fa.recovery');
});

// Bildirishnomalar (klinika xodimlari va super admin) va ilovani o'rnatish (PWA)
Route::get('/manifest.webmanifest', [\App\Http\Controllers\PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/offline', [\App\Http\Controllers\PwaController::class, 'offline'])->name('pwa.offline');
Route::middleware(['auth', 'offer'])->prefix('notifications')->name('notifications.')->group(function () {
    $n = \App\Http\Controllers\NotificationController::class;
    Route::get('/', [$n, 'index'])->name('index');
    Route::get('feed', [$n, 'feed'])->name('feed');
    Route::post('read-all', [$n, 'readAll'])->name('readall');
    Route::post('{notification}/read', [$n, 'read'])->name('read');
});

// ---------- Super Admin ----------
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super_admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('clinics', Admin\ClinicController::class);
    Route::post('clinics/{clinic}/status', [Admin\ClinicController::class, 'status'])->name('clinics.status');
    Route::post('clinics/{clinic}/extend', [Admin\ClinicController::class, 'extend'])->name('clinics.extend');
    Route::post('clinics/{clinic}/balance', [Admin\ClinicController::class, 'balance'])->name('clinics.balance');
    Route::post('clinics/{clinic}/hold', [Admin\ClinicController::class, 'hold'])->name('clinics.hold');
    Route::post('clinics/{clinic}/impersonate', [Admin\ClinicController::class, 'impersonate'])->name('clinics.impersonate');

    Route::get('plans', [Admin\PlanController::class, 'index'])->name('plans.index');
    Route::post('plans', [Admin\PlanController::class, 'store'])->name('plans.store');
    Route::put('plans/{plan}', [Admin\PlanController::class, 'update'])->name('plans.update');

    Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');

    Route::get('offers', [Admin\OfferController::class, 'index'])->name('offers.index');
    Route::post('offers', [Admin\OfferController::class, 'store'])->name('offers.store');
    Route::put('offers/privacy', [Admin\OfferController::class, 'privacy'])->name('offers.privacy');
    Route::post('offers/{offer}/activate', [Admin\OfferController::class, 'activate'])->name('offers.activate');

    Route::get('patients', [Admin\PatientController::class, 'index'])->name('patients.index');
    Route::delete('patients/{patient}', [Admin\PatientController::class, 'destroy'])->name('patients.destroy');

    Route::get('transactions', [Admin\TransactionController::class, 'index'])->name('transactions.index');
    Route::get('audit', [Admin\AuditController::class, 'index'])->name('audit.index');
    Route::get('sms', [Admin\SmsController::class, 'index'])->name('sms.index');
    // Platforma SMS matnlari
    Route::prefix('sms-matnlari')->name('sms.platform.')->group(function () {
        $s = Admin\PlatformSmsController::class;
        Route::get('/', [$s, 'index'])->name('index');
        Route::post('settings', [$s, 'settings'])->name('settings');
        Route::post('connection', [$s, 'connection'])->name('connection');
        Route::post('test', [$s, 'test'])->middleware('throttle:10,1')->name('test');
        Route::post('{event}', [$s, 'save'])->name('save');
        Route::post('{event}/submit', [$s, 'submit'])->name('submit');
        Route::post('{event}/check', [$s, 'check'])->name('check');
        Route::post('{event}/mark', [$s, 'mark'])->name('mark');
        Route::post('{event}/reset', [$s, 'reset'])->name('reset');
    });
    // Kartaga qo'lda to'lovlar (cheklar)
    Route::get('payments', [Admin\ManualPaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{payment}/receipt', [Admin\ManualPaymentController::class, 'receipt'])->name('payments.receipt');
    Route::post('payments/{payment}/approve', [Admin\ManualPaymentController::class, 'approve'])->name('payments.approve');
    Route::post('payments/{payment}/reject', [Admin\ManualPaymentController::class, 'reject'])->name('payments.reject');
    Route::post('system/run/{task}', [Admin\SystemController::class, 'run'])->middleware('throttle:6,1')->name('system.run');
    Route::get('system', [Admin\SystemController::class, 'index'])->name('system.index');
    Route::get('notifications', [Admin\BroadcastController::class, 'index'])->name('notifications.index');
    Route::post('notifications', [Admin\BroadcastController::class, 'send'])->name('notifications.send');
    Route::get('diagnostics', [Admin\BroadcastController::class, 'diagnostics'])->name('diagnostics.index');
    Route::post('diagnostics/all', [Admin\BroadcastController::class, 'notifyAll'])->name('diagnostics.all');
    Route::post('diagnostics/{clinic}', [Admin\BroadcastController::class, 'notifyClinic'])->name('diagnostics.clinic');
    Route::delete('system/errors', [Admin\SystemController::class, 'clear'])->name('system.clear');
});

// ---------- Klinika paneli ----------
Route::prefix('app')->name('app.')->middleware(['auth', 'offer', 'clinic'])->group(function () {
    Route::get('/', [AppC\DashboardController::class, 'index'])->name('dashboard');
    Route::get('search', AppC\GlobalSearchController::class)->name('search');
    Route::post('impersonate/stop', [Admin\ClinicController::class, 'stopImpersonate'])->name('impersonate.stop');

    // Obuna va balans (bloklangan holatda ham ochiq)
    Route::middleware('role:clinic_admin')->group(function () {
        Route::get('billing', [AppC\BillingController::class, 'index'])->name('billing');
        Route::post('billing/topup', [AppC\BillingController::class, 'topup'])->name('billing.topup');
        Route::post('billing/subscribe', [AppC\BillingController::class, 'subscribe'])->name('billing.subscribe');
        Route::post('billing/manual', [AppC\BillingController::class, 'manualStore'])->middleware('throttle:10,60')->name('billing.manual');
        Route::get('billing/manual/{payment}/receipt', [AppC\BillingController::class, 'manualReceipt'])->name('billing.manual.receipt');
        Route::delete('billing/manual/{payment}', [AppC\BillingController::class, 'manualCancel'])->name('billing.manual.cancel');
        Route::get('export/patients', [AppC\ExportController::class, 'patients'])->name('export.patients');
    });

    // Bemorlar
    Route::middleware('role:clinic_admin,receptionist,doctor,cashier')->get('patients/search', [AppC\PatientController::class, 'search'])->name('patients.search');
    Route::middleware('role:clinic_admin,receptionist')->group(function () {
        Route::get('patients/lookup', [AppC\PatientController::class, 'lookup'])->middleware('throttle:30,1')->name('patients.lookup');
        Route::get('patients/create', [AppC\PatientController::class, 'create'])->name('patients.create');
        Route::post('patients', [AppC\PatientController::class, 'store'])->name('patients.store');
        Route::get('patients/{patient}/edit', [AppC\PatientController::class, 'edit'])->name('patients.edit');
        Route::put('patients/{patient}', [AppC\PatientController::class, 'update'])->name('patients.update');
    });
    Route::middleware('role:clinic_admin,receptionist,doctor,cashier')->group(function () {
        Route::get('patients', [AppC\PatientController::class, 'index'])->name('patients.index');
        Route::get('patients/{patient}', [AppC\PatientController::class, 'show'])->name('patients.show');
    });

    // Bemor rasmlari
    Route::middleware('role:clinic_admin,receptionist,doctor')->group(function () {
        Route::post('patients/{patient}/photos', [AppC\PatientPhotoController::class, 'store'])->name('photos.store');
        Route::get('photos/{photo}', [AppC\PatientPhotoController::class, 'show'])->name('photos.show');
    });
    Route::middleware('role:clinic_admin,doctor')->delete('photos/{photo}', [AppC\PatientPhotoController::class, 'destroy'])->name('photos.destroy');

    // Qabullar
    Route::middleware('role:clinic_admin,receptionist,doctor')->group(function () {
        Route::get('calendar', [AppC\CalendarController::class, 'index'])->name('calendar');
        Route::get('appointments', [AppC\AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('appointments/create', [AppC\AppointmentController::class, 'create'])->name('appointments.create');
        Route::post('appointments', [AppC\AppointmentController::class, 'store'])->name('appointments.store');
        Route::get('appointments/{appointment}/edit', [AppC\AppointmentController::class, 'edit'])->name('appointments.edit');
        Route::put('appointments/{appointment}', [AppC\AppointmentController::class, 'update'])->name('appointments.update');
        Route::post('appointments/{appointment}/status', [AppC\AppointmentController::class, 'status'])->name('appointments.status');
    });

    // Tashriflar (EMR)
    Route::middleware('role:clinic_admin,doctor')->group(function () {
        Route::get('visits', [AppC\VisitController::class, 'index'])->name('visits.index');
        Route::get('visits/create', [AppC\VisitController::class, 'create'])->name('visits.create');
        Route::post('visits', [AppC\VisitController::class, 'store'])->name('visits.store');
        Route::get('visits/{visit}', [AppC\VisitController::class, 'show'])->name('visits.show');
        Route::get('visits/{visit}/print/prescription', [AppC\VisitController::class, 'printRx'])->name('visits.print.rx');
        Route::get('visits/{visit}/print/summary', [AppC\VisitController::class, 'printSummary'])->name('visits.print.summary');
        Route::get('visits/{visit}/edit', [AppC\VisitController::class, 'edit'])->name('visits.edit');
        Route::put('visits/{visit}', [AppC\VisitController::class, 'update'])->name('visits.update');
    });

    // Hisob-fakturalar va kassa
    Route::middleware('role:clinic_admin,receptionist,cashier')->group(function () {
        Route::get('invoices', [AppC\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/create', [AppC\InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('invoices', [AppC\InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('invoices/{invoice}', [AppC\InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/print', [AppC\InvoiceController::class, 'print'])->name('invoices.print');
        Route::post('invoices/{invoice}/payments', [AppC\InvoiceController::class, 'pay'])->name('invoices.pay');
        Route::post('invoices/{invoice}/apply-credit', [AppC\PatientBalanceController::class, 'applyCredit'])->name('invoices.apply-credit');
        Route::post('patients/{patient}/deposit', [AppC\PatientBalanceController::class, 'deposit'])->name('patients.deposit');
        Route::post('patients/{patient}/refund', [AppC\PatientBalanceController::class, 'refund'])->name('patients.refund');
        Route::post('invoices/{invoice}/cancel', [AppC\InvoiceController::class, 'cancel'])->name('invoices.cancel');
    });

    // Tahlillar
    Route::middleware('role:clinic_admin,receptionist,doctor')->group(function () {
        Route::get('lab/create', [AppC\LabController::class, 'create'])->name('lab.create');
        Route::post('lab', [AppC\LabController::class, 'store'])->name('lab.store');
    });
    Route::middleware('role:clinic_admin,receptionist,doctor,laborant')->group(function () {
        Route::get('lab', [AppC\LabController::class, 'index'])->name('lab.index');
        Route::get('lab/{lab}/edit', [AppC\LabController::class, 'edit'])->name('lab.edit');
        Route::put('lab/{lab}', [AppC\LabController::class, 'update'])->name('lab.update');
        Route::get('lab/{lab}/file', [AppC\LabController::class, 'file'])->name('lab.file');
        Route::delete('lab/{lab}', [AppC\LabController::class, 'destroy'])->name('lab.destroy');
    });

    // Sozlamalar (faqat klinika admini)
    Route::middleware('role:clinic_admin')->group(function () {
        Route::resource('services', AppC\ServiceController::class)->except(['show']);
        Route::resource('staff', AppC\StaffController::class)->except(['show', 'destroy']);
        Route::get('profile', [AppC\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [AppC\ProfileController::class, 'update'])->name('profile.update');
        Route::get('audit', [AppC\AuditController::class, 'index'])->name('audit.index');
        Route::get('payroll', [AppC\PayrollController::class, 'index'])->name('payroll.index');
        Route::get('payroll/print', [AppC\PayrollController::class, 'print'])->name('payroll.print');
        Route::put('payroll/{payroll}/days', [AppC\PayrollController::class, 'days'])->name('payroll.days');
        Route::post('payroll/{payroll}/pay', [AppC\PayrollController::class, 'pay'])->name('payroll.pay');
        Route::post('payroll/adjust/{user}', [AppC\PayrollController::class, 'adjust'])->name('payroll.adjust');
        Route::delete('payroll/adjustment/{adjustment}', [AppC\PayrollController::class, 'destroyAdjustment'])->name('payroll.adjustment.destroy');
        Route::get('sms', [AppC\SmsController::class, 'index'])->name('sms.index');
        Route::get('online', [AppC\OnlineSettingsController::class, 'edit'])->name('online.edit');
        Route::put('online', [AppC\OnlineSettingsController::class, 'update'])->name('online.update');
        Route::get('pinfl', [AppC\PinflSettingsController::class, 'edit'])->name('pinfl.edit');
        Route::put('pinfl', [AppC\PinflSettingsController::class, 'update'])->name('pinfl.update');
        Route::post('pinfl/test', [AppC\PinflSettingsController::class, 'test'])->name('pinfl.test');
        Route::get('sms/settings', [AppC\SmsSettingsController::class, 'edit'])->name('sms.settings');
        Route::put('sms/settings', [AppC\SmsSettingsController::class, 'update'])->name('sms.settings.update');
        Route::post('sms/settings/test', [AppC\SmsSettingsController::class, 'test'])->name('sms.settings.test');
        Route::post('sms/settings/toggle', [AppC\SmsSettingsController::class, 'toggle'])->name('sms.settings.toggle');
        Route::post('sms/settings/connection', [AppC\SmsSettingsController::class, 'connection'])->name('sms.settings.connection');
        Route::post('sms/templates/{event}', [AppC\SmsSettingsController::class, 'save'])->name('sms.tpl.save');
        Route::post('sms/templates/{event}/submit', [AppC\SmsSettingsController::class, 'submit'])->name('sms.tpl.submit');
        Route::post('sms/templates/{event}/check', [AppC\SmsSettingsController::class, 'check'])->name('sms.tpl.check');
        Route::post('sms/templates/{event}/mark', [AppC\SmsSettingsController::class, 'mark'])->name('sms.tpl.mark');
    });

    Route::middleware('role:clinic_admin,cashier')->group(function () {
        Route::get('cash', [AppC\CashController::class, 'index'])->name('cash.index');
        Route::get('cash/print', [AppC\CashController::class, 'print'])->name('cash.print');
        Route::post('cash/movements', [AppC\CashController::class, 'store'])->name('cash.store');
        Route::delete('cash/movements/{movement}', [AppC\CashController::class, 'destroy'])->name('cash.destroy');
        Route::post('cash/close', [AppC\CashController::class, 'close'])->name('cash.close');
        Route::post('cash/reopen', [AppC\CashController::class, 'reopen'])->name('cash.reopen');
    });

    Route::get('guides', [AppC\GuideController::class, 'index'])->name('guides.index');
    Route::get('guides/{key}', [AppC\GuideController::class, 'show'])->name('guides.show');

    Route::middleware('role:clinic_admin,cashier')->get('reports', [AppC\ReportController::class, 'index'])->name('reports.index');
});
