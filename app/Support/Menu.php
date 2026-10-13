<?php

namespace App\Support;

/** Yon menyu tuzilmasi (guruhlar) va rollar ruxsat matritsasi uchun yagona manba. */
class Menu
{
    /** @return array<int, array{key:string,label:string,icon:string,open:bool,items:array}> item = [route, label, roles, icon] */
    public static function clinic(): array
    {
        $all = ['*'];
        $adm = ['clinic_admin'];

        return [
            ['key' => 'home', 'label' => null, 'icon' => 'home', 'open' => true, 'items' => [
                ['app.dashboard', 'Bosh sahifa', $all, 'home'],
            ]],
            ['key' => 'work', 'label' => 'Ish stoli', 'icon' => 'clipboard', 'open' => true, 'items' => [
                ['app.patients.index', 'Bemorlar', ['clinic_admin', 'receptionist', 'doctor', 'cashier'], 'users'],
                ['app.calendar', 'Qabul kalendari', ['clinic_admin', 'receptionist', 'doctor'], 'calendar'],
                ['app.appointments.index', 'Qabullar', ['clinic_admin', 'receptionist', 'doctor'], 'clipboard'],
                ['app.visits.index', 'Tashriflar (tibbiy karta)', ['clinic_admin', 'doctor'], 'stethoscope'],
                ['app.lab.index', 'Tahlillar', ['clinic_admin', 'receptionist', 'doctor', 'laborant'], 'flask'],
            ]],
            ['key' => 'money', 'label' => 'Moliya', 'icon' => 'wallet', 'open' => true, 'items' => [
                ['app.cash.index', 'Kunlik kassa', ['clinic_admin', 'cashier'], 'banknote'],
                ['app.invoices.index', 'Hisob-fakturalar', ['clinic_admin', 'receptionist', 'cashier'], 'receipt'],
                ['app.reports.index', 'Hisobotlar', ['clinic_admin', 'cashier'], 'chart'],
                ['app.payroll.index', 'Ish haqi', $adm, 'credit-card'],
            ]],
            ['key' => 'manage', 'label' => 'Boshqaruv', 'icon' => 'building', 'open' => false, 'items' => [
                ['app.staff.index', 'Xodimlar va rollar', $adm, 'user'],
                ['app.services.index', 'Xizmatlar va narxlar', $adm, 'tag'],
                ['app.profile.edit', "Klinika ma'lumotlari", $adm, 'building'],
                ['app.billing', 'Obuna va balans', $adm, 'layers'],
            ]],
            ['key' => 'integr', 'label' => 'Integratsiyalar', 'icon' => 'settings', 'open' => false, 'items' => [
                ['app.online.edit', 'Onlayn qabul', $adm, 'calendar'],
                ['app.sms.settings', 'SMS sozlamalari', $adm, 'settings'],
                ['app.sms.index', 'SMS jurnali', $adm, 'message'],
                ['app.pinfl.edit', 'JSHSHIR xizmati', $adm, 'shield'],
            ]],
            ['key' => 'system', 'label' => 'Tizim va yordam', 'icon' => 'book', 'open' => false, 'items' => [
                ['app.guides.index', "Foydalanish yo'riqnomasi", $all, 'book'],
                ['app.audit.index', 'Harakatlar jurnali', $adm, 'history'],
            ]],
        ];
    }

    public static function admin(): array
    {
        $all = ['*'];

        return [
            ['key' => 'main', 'label' => null, 'icon' => 'home', 'open' => true, 'items' => [
                ['admin.dashboard', 'Bosh sahifa', $all, 'home'],
                ['admin.clinics.index', 'Klinikalar', $all, 'building'],
                ['admin.plans.index', 'Tariflar', $all, 'layers'],
                ['admin.payments.index', "To'lov cheklari (karta)", $all, 'receipt'],
                ['admin.transactions.index', "To'lovlar va balans", $all, 'credit-card'],
                ['admin.system.index', 'Tizim holati', $all, 'activity'],
                ['admin.diagnostics.index', 'Klinikalar kamchiliklari', $all, 'alert'],
                ['admin.notifications.index', 'Xabarnomalar yuborish', $all, 'send'],
            ]],
            ['key' => 'content', 'label' => 'Kontent', 'icon' => 'file-text', 'open' => true, 'items' => [
                ['admin.patients.index', "Bemorlar (o'chirish)", $all, 'users'],
                ['admin.offers.index', 'Oferta', $all, 'file-text'],
                ['admin.settings.edit', 'Sozlamalar va aloqa', $all, 'settings'],
            ]],
            ['key' => 'log', 'label' => 'Jurnal', 'icon' => 'history', 'open' => false, 'items' => [
                ['admin.audit.index', 'Harakatlar jurnali', $all, 'history'],
                ['admin.sms.platform.index', 'SMS matnlari', $all, 'message'],
                ['admin.sms.index', 'SMS jurnali', $all, 'message'],
            ]],
        ];
    }

    /** Rollar ruxsat matritsasi: [bo'lim nomi, [role=>bool]] */
    public static function matrix(): array
    {
        $roles = ['clinic_admin', 'receptionist', 'doctor', 'cashier', 'laborant'];
        $rows = [];
        foreach (self::clinic() as $g) {
            foreach ($g['items'] as [$route, $label, $allowed]) {
                $rows[] = [$label, array_map(fn ($r) => in_array('*', $allowed, true) || in_array($r, $allowed, true), array_combine($roles, $roles))];
            }
        }

        return $rows;
    }
}
