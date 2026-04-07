<?php

namespace App\Console\Commands;

use App\Services\MACRMService;
use Illuminate\Console\Command;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $payload = [
            'voucher_code' => 'test123bc',
            'voucher_type' => 'trial_membership',
            'duration_days' => 1,
            'amount' => 10,
            'valid_from' => '2026-04-01 14:15:22',
            'valid_till' => '2026-08-01 14:15:22',
            'email' => 'syed.saad@myalfred.com',
            'customer_id' => null,
            'max_claims' => 1,
            'promotional_text' => 'Test Voucher 1',
            'description' => 'Get your 7 days trail',
            'source' => 'imcrm',
            'is_active' => true,
            'auto_claim' => true,
        ];

        dd(MACRMService::createVoucher($payload));
    }
}
