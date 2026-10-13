<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\ManualPayment;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Services\PlatformSms;
use App\Services\TaskMonitor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\ClinicTestCase;

class ManualPaymentsTasksTest extends ClinicTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Setting::set('card_number', '8600123412341234');
        Setting::set('card_holder', 'Test Egasi');
    }

    protected function submit($admin, ?UploadedFile $file = null, array $over = [])
    {
        return $this->actingAs($admin)->post(route('app.billing.manual'), $over + [
            'amount' => 500000, 'paid_at' => today()->toDateString(), 'payer_card' => '4321',
            'receipt' => $file ?? UploadedFile::fake()->image('chek.jpg', 300, 300),
        ]);
    }

    public function test_clinic_submits_receipt_and_super_admin_is_notified(): void
    {
        $super = $this->superAdmin();
        [$clinic, $admin] = $this->makeClinic();
        $this->actingAs($admin)->get(route('app.billing'))->assertOk()->assertSee('8600 1234 1234 1234')->assertSee("To'lov qildim");

        $this->submit($admin)->assertRedirect(route('app.billing'));
        $p = ManualPayment::first();
        $this->assertSame('pending', $p->status);
        $this->assertSame($clinic->id, $p->clinic_id);
        Storage::disk('local')->assertExists($p->receipt_path);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $super->id, 'type' => 'payment']);
        $this->actingAs($super)->get(route('admin.payments.index'))->assertOk()->assertSee($clinic->name);
    }

    public function test_receipt_is_required_and_duplicates_rejected(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $this->actingAs($admin)->post(route('app.billing.manual'), ['amount' => 500000, 'paid_at' => today()->toDateString()])->assertSessionHasErrors('receipt', null, 'manual');

        $bytes = file_get_contents(UploadedFile::fake()->image('a.jpg', 200, 200)->getRealPath());
        $this->submit($admin, UploadedFile::fake()->createWithContent('a.jpg', $bytes));
        $this->submit($admin, UploadedFile::fake()->createWithContent('b.jpg', $bytes))->assertSessionHas('error');
        $this->assertSame(1, ManualPayment::count());
    }

    public function test_approve_credits_balance_once(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $this->submit($admin);
        $p = ManualPayment::first();
        $super = $this->superAdmin();

        $this->actingAs($super)->post(route('admin.payments.approve', $p), ['amount' => 450000])->assertSessionHas('success');
        $this->assertSame(450000, (int) $clinic->fresh()->balance);
        $this->assertSame('approved', $p->fresh()->status);
        $this->assertSame(450000, (int) $p->fresh()->approved_amount);

        $this->post(route('admin.payments.approve', $p), ['amount' => 450000])->assertSessionHas('error');
        $this->assertSame(450000, (int) $clinic->fresh()->balance);
        $this->assertDatabaseHas('audit_logs', ['action' => 'pay_approve']);
    }

    public function test_reject_needs_reason_and_clinic_sees_it(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $this->submit($admin);
        $p = ManualPayment::first();
        $super = $this->superAdmin();

        $this->actingAs($super)->post(route('admin.payments.reject', $p), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->assertSame('pending', $p->fresh()->status);

        $this->post(route('admin.payments.reject', $p), ['reason' => "Pul kartaga tushmagan"])->assertSessionHas('success');
        $this->assertSame('rejected', $p->fresh()->status);
        $this->assertSame(0, (int) $clinic->fresh()->balance);

        $this->actingAs($admin)->get(route('app.billing'))->assertOk()->assertSee('Pul kartaga tushmagan');
    }

    public function test_receipts_are_private_and_admin_area_is_super_only(): void
    {
        [$c1, $a1] = $this->makeClinic();
        [$c2, $a2] = $this->makeClinic();
        $this->submit($a1);
        $p = ManualPayment::first();

        $this->actingAs($a1)->get(route('app.billing.manual.receipt', $p))->assertOk();
        $this->actingAs($a2)->get(route('app.billing.manual.receipt', $p))->assertNotFound();
        $this->actingAs($a1)->get(route('admin.payments.index'))->assertForbidden();
        $this->actingAs($a1)->post(route('admin.payments.approve', $p), ['amount' => 1000])->assertForbidden();
    }

    public function test_clinic_can_cancel_own_pending_payment(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $this->submit($admin);
        $p = ManualPayment::first();
        $this->actingAs($admin)->delete(route('app.billing.manual.cancel', $p))->assertSessionHas('success');
        $this->assertSame('canceled', $p->fresh()->status);
    }

    public function test_card_section_hidden_when_card_disabled_or_missing(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        Setting::set('card_enabled', '0');
        $this->actingAs($admin)->get(route('app.billing'))->assertOk()->assertDontSee("To'lov qildim");
        $this->submit($admin)->assertSessionHas('error');
        Setting::set('card_enabled', '1');
        Setting::set('card_number', '');
        $this->get(route('app.billing'))->assertOk()->assertDontSee("To'lov qildim");
    }

    public function test_platform_sms_templates_are_editable_and_respect_switches(): void
    {
        $super = $this->superAdmin();
        $this->actingAs($super)->get(route('admin.sms.platform.index'))->assertOk()->assertSee('Obuna faollashtirildi');

        $this->post(route('admin.sms.platform.save', 'renewed'), ['text' => '{app}: YANGI MATN', 'enabled' => '1'])->assertSessionHas('success');
        $this->assertStringContainsString('YANGI MATN', PlatformSms::render('renewed'));

        // OTP matnida {code} bo'lishi shart
        $this->post(route('admin.sms.platform.save', 'otp'), ['text' => 'kodsiz matn'])->assertSessionHas('error');
        $this->assertStringContainsString('{code}', PlatformSms::get('otp')['text']);

        // o'chirilgan xabar yuborilmaydi (jurnalda "skipped")
        $this->post(route('admin.sms.platform.save', 'renewed'), ['text' => '{app}: YANGI MATN']); // enabled yo'q -> o'chiq
        $this->assertFalse(PlatformSms::send('renewed', '998901112233', [], null));
        $this->assertSame('skipped', SmsLog::latest('id')->first()->status);

        // standartga qaytarish
        $this->post(route('admin.sms.platform.reset', 'renewed'));
        $this->assertStringNotContainsString('YANGI MATN', PlatformSms::render('renewed'));
    }

    public function test_global_platform_sms_switch_blocks_everything_except_otp(): void
    {
        $this->actingAs($this->superAdmin())->post(route('admin.sms.platform.settings'), ['enabled' => 0, 'need_approval' => 0]);
        $this->assertFalse(PlatformSms::globalEnabled());
        $this->assertFalse(PlatformSms::send('subscribed', '998901112233'));
        $this->assertSame('skipped', SmsLog::latest('id')->first()->status);
    }

    public function test_task_monitor_tracks_scheduler_heartbeat(): void
    {
        $this->assertSame('bad', TaskMonitor::scheduler()['status']); // hali hech narsa ishlamagan
        TaskMonitor::record('bookings:expire', true, 12);
        $this->assertSame('ok', TaskMonitor::scheduler()['status']);
        TaskMonitor::record('billing:run', false, 5, 'xato');
        $row = collect(TaskMonitor::rows())->firstWhere('name', 'billing:run');
        $this->assertSame('bad', $row['status']);
        $this->assertSame(1, $row['fails']);

        $this->actingAs($this->superAdmin())->get(route('admin.system.index'))->assertOk()->assertSee('bookings:expire');
    }
}
