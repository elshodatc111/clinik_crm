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
Route::get('/lang/{locale}', [LandingController::class, 'lang'])->name('lang');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    Route::get('/register/verify', [AuthController::class, 'verifyForm'])->name('register.verify');
    Route::post('/register/verify', [AuthController::class, 'verify'])->name('register.verify.post');
    Route::post('/register/resend', [AuthController::class, 'resend'])->name('register.resend');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('/dashboard', [AuthController::class, 'home'])->name('dashboard')->middleware('auth');

// Payme Merchant API (JSON-RPC)
Route::post('/payme', [PaymeController::class, 'handle'])->name('payme');

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
    Route::post('offers/{offer}/activate', [Admin\OfferController::class, 'activate'])->name('offers.activate');

    Route::get('patients', [Admin\PatientController::class, 'index'])->name('patients.index');
    Route::delete('patients/{patient}', [Admin\PatientController::class, 'destroy'])->name('patients.destroy');

    Route::get('transactions', [Admin\TransactionController::class, 'index'])->name('transactions.index');
    Route::get('audit', [Admin\AuditController::class, 'index'])->name('audit.index');
    Route::get('sms', [Admin\SmsController::class, 'index'])->name('sms.index');
});

// ---------- Klinika paneli ----------
Route::prefix('app')->name('app.')->middleware(['auth', 'clinic'])->group(function () {
    Route::get('/', [AppC\DashboardController::class, 'index'])->name('dashboard');
    Route::post('impersonate/stop', [Admin\ClinicController::class, 'stopImpersonate'])->name('impersonate.stop');

    // Obuna va balans (bloklangan holatda ham ochiq)
    Route::middleware('role:clinic_admin')->group(function () {
        Route::get('billing', [AppC\BillingController::class, 'index'])->name('billing');
        Route::post('billing/topup', [AppC\BillingController::class, 'topup'])->name('billing.topup');
        Route::post('billing/subscribe', [AppC\BillingController::class, 'subscribe'])->name('billing.subscribe');
        Route::get('export/patients', [AppC\ExportController::class, 'patients'])->name('export.patients');
    });

    // Bemorlar
    Route::middleware('role:clinic_admin,receptionist,doctor,cashier')->get('patients/search', [AppC\PatientController::class, 'search'])->name('patients.search');
    Route::middleware('role:clinic_admin,receptionist')->group(function () {
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
    });

    // Sozlamalar (faqat klinika admini)
    Route::middleware('role:clinic_admin')->group(function () {
        Route::resource('services', AppC\ServiceController::class)->except(['show']);
        Route::resource('staff', AppC\StaffController::class)->except(['show', 'destroy']);
        Route::get('profile', [AppC\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [AppC\ProfileController::class, 'update'])->name('profile.update');
        Route::get('audit', [AppC\AuditController::class, 'index'])->name('audit.index');
        Route::get('sms', [AppC\SmsController::class, 'index'])->name('sms.index');
        Route::get('sms/settings', [AppC\SmsSettingsController::class, 'edit'])->name('sms.settings');
        Route::put('sms/settings', [AppC\SmsSettingsController::class, 'update'])->name('sms.settings.update');
        Route::post('sms/settings/test', [AppC\SmsSettingsController::class, 'test'])->name('sms.settings.test');
    });

    Route::middleware('role:clinic_admin,cashier')->get('reports', [AppC\ReportController::class, 'index'])->name('reports.index');
});
