<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class BillingRun extends Command
{
    protected $signature = 'billing:run';

    protected $description = "Obuna, demo, bloklash va ma'lumotlarni o'chirish jarayonlarini bajaradi (kunlik)";

    public function handle(): int
    {
        BillingService::runDaily();
        $this->info('Billing jarayoni tugadi.');

        return self::SUCCESS;
    }
}
