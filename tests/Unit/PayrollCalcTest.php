<?php

namespace Tests\Unit;

use App\Services\PayrollService;
use PHPUnit\Framework\TestCase;

class PayrollCalcTest extends TestCase
{
    public function test_monthly_is_prorated_by_work_days(): void
    {
        $c = PayrollService::calc('monthly', 3000000, 0, 26, 13, 0);
        $this->assertSame(1500000, $c['base_amount']);
        $this->assertSame(0, $c['percent_amount']);
    }

    public function test_daily_multiplies_days(): void
    {
        $c = PayrollService::calc('daily', 120000, 0, 26, 20, 0);
        $this->assertSame(2400000, $c['base_amount']);
    }

    public function test_percent_uses_revenue(): void
    {
        $c = PayrollService::calc('percent', 0, 30, 26, 26, 10000000);
        $this->assertSame(3000000, $c['percent_amount']);
        $this->assertSame(0, $c['base_amount']);
    }

    public function test_mixed_is_sum(): void
    {
        $c = PayrollService::calc('mixed', 2000000, 5, 26, 26, 20000000);
        $this->assertSame(2000000, $c['base_amount']);
        $this->assertSame(1000000, $c['percent_amount']);
    }

    public function test_plan_days_excludes_sundays(): void
    {
        $this->assertSame(27, PayrollService::planDays('2026-10')); // 31 kun - 4 yakshanba
    }
}
