<?php

namespace App\Console\Commands;

use App\Models\CarQuote;
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
        $carQuote = CarQuote::where('uuid', 'NKTETUTW')->first();
        $payload = [
            'voucher_code' => 'string',
            'voucher_type' => 'trial_membership',
            'duration_days' => 1,
            'amount' => 0,
            'valid_from' => '2019-08-24T14:15:22Z',
            'valid_till' => '2019-08-24T14:15:22Z',
            'email' => 'user@example.com',
            'customer_id' => $carQuote->customer_id,
            'max_claims' => 1,
            'promotional_text' => 'string',
            'description' => 'string',
            'source' => 'string',
            'is_active' => true,
            'auto_claim' => true,
        ];

        dd(MACRMService::createVoucher($payload));
    }
}
