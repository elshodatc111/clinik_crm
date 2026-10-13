<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\SmsLog;
use App\Services\Notify;
use App\Services\SmsService;
use App\Support\SmsEvents;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\ClinicTestCase;

class SmsNotificationsTest extends ClinicTestCase
{
    private function connect($clinic, array $extra = []): void
    {
        $clinic->update(['settings' => array_merge(['eskiz_email' => 'a@b.uz', 'eskiz_password' => Crypt::encryptString('pw')], $extra)]);
    }

    public function test_unapproved_template_is_not_sent_but_logged(): void
    {
        [$clinic] = $this->makeClinic();
        $this->connect($clinic);
        Http::fake();

        $this->assertFalse(SmsService::event($clinic->fresh(), 'lab_ready', '+998901234567', ['name' => 'Ali']));
        $this->assertSame('skipped', SmsLog::first()->status);
        Http::assertNothingSent();
    }

    public function test_approved_template_is_sent_and_master_switch_blocks(): void
    {
        [$clinic] = $this->makeClinic();
        Http::fake(['notify.eskiz.uz/*' => Http::response(['data' => ['token' => 't'], 'status' => 'success'], 200)]);
        $this->connect($clinic, ['sms_tpls' => ['lab_ready' => ['status' => 'approved', 'text' => 'Salom {name}']]]);

        $this->assertTrue(SmsService::event($clinic->fresh(), 'lab_ready', '+998901234567', ['name' => 'Ali']));
        $this->assertSame('Salom Ali', SmsLog::where('status', 'sent')->first()->message);

        $this->connect($clinic, ['sms_enabled' => false, 'sms_tpls' => ['lab_ready' => ['status' => 'approved', 'text' => 'Salom {name}']]]);
        $this->assertFalse(SmsService::event($clinic->fresh(), 'lab_ready', '+998901234567', ['name' => 'Ali']));
    }

    public function test_check_template_updates_status_from_eskiz(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $this->connect($clinic, ['sms_tpls' => ['lab_ready' => ['status' => 'moderation', 'eskiz_id' => 77, 'text' => 'Salom {name}']]]);
        Http::fake([
            'notify.eskiz.uz/api/auth/login' => Http::response(['data' => ['token' => 't']], 200),
            'notify.eskiz.uz/api/user/templates' => Http::response(['result' => [['id' => 77, 'template' => 'Salom {name}', 'status' => 'service']]], 200),
        ]);
        $this->actingAs($admin)->get(route('app.sms.settings'))->assertOk()->assertSee('Salom');
        $this->post(route('app.sms.tpl.check', 'lab_ready'))->assertRedirect();
        $this->assertSame('approved', SmsEvents::get($clinic->fresh(), 'lab_ready')['status']);
    }

    public function test_notifications_feed_and_read(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        Notify::toAdmins($clinic->id, 'Sarlavha', 'Matn', 'warning');
        $n = AppNotification::firstOrFail();

        $this->actingAs($admin)->getJson(route('notifications.feed'))->assertOk()->assertJsonPath('unread', 1)->assertJsonPath('items.0.title', 'Sarlavha');
        $this->postJson(route('notifications.read', $n))->assertOk();
        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_super_admin_broadcast_reaches_clinic_admins(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $this->actingAs($this->superAdmin())->post(route('admin.notifications.send'), ['audience' => 'all', 'level' => 'info', 'title' => 'Yangilik', 'body' => 'Matn'])->assertRedirect();
        $this->assertSame(1, AppNotification::where('user_id', $admin->id)->where('title', 'Yangilik')->count());
        $this->get(route('admin.notifications.index'))->assertOk();
        $this->get(route('admin.diagnostics.index'))->assertOk();
    }

    public function test_quick_search_and_manifest(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $this->makePatient($clinic, ['last_name' => 'Qodirov', 'first_name' => 'Sardor']);
        $this->actingAs($admin)->getJson(route('app.search', ['q' => 'Qodi']))->assertOk()->assertJsonPath('patients.0.title', fn ($t) => str_contains($t, 'Qodirov'));
        $this->get(route('pwa.manifest'))->assertOk()->assertJsonPath('display', 'standalone');
    }
}
