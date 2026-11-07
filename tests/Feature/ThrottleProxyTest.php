<?php

namespace Tests\Feature;

use Tests\ClinicTestCase;

class ThrottleProxyTest extends ClinicTestCase
{
    public function test_status_polling_does_not_exhaust_other_forms(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->withSession(['reset' => ['phone' => '998901234567']])->getJson(route('otp.telegram.status', ['token' => 'x']))->assertOk();
        }
        $this->post(route('password.forgot.post'), ['phone' => '+998 90 123 45 67'])->assertRedirect(route('password.verify'));
        $this->post(route('login.post'), ['login' => 'nobody', 'password' => 'x'])->assertSessionHasErrors('login');
    }

    public function test_each_throttled_route_has_its_own_counter(): void
    {
        $prefixes = [];
        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $r) {
            foreach ($r->gatherMiddleware() as $m) {
                if (is_string($m) && str_starts_with($m, 'throttle:') && substr_count($m, ',') === 1) {
                    $this->fail('Throttle prefixsiz: '.$r->uri().' '.$m);
                }
                if (is_string($m) && str_starts_with($m, 'throttle:')) {
                    $prefixes[] = $m;
                }
            }
        }
        $this->assertNotEmpty($prefixes);
        $this->assertSame(count($prefixes), count(array_unique($prefixes)), 'Throttle prefikslari takrorlanmasligi kerak');
    }

    public function test_real_client_ip_is_taken_from_cloudflare_header_behind_proxy(): void
    {
        \Illuminate\Support\Facades\Route::get('/__ip', fn (\Illuminate\Http\Request $r) => $r->ip())->middleware('web');
        $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5'])->get('/__ip', ['CF-Connecting-IP' => '203.0.113.7'])->assertSee('203.0.113.7');
        // ichki bo'lmagan manbadan kelgan sarlavhaga ishonilmaydi (soxtalashtirish)
        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->get('/__ip', ['CF-Connecting-IP' => '203.0.113.7'])->assertSee('8.8.8.8');
    }
}
