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
        $carQuote = CarQuote::query()->where('uuid', 'NKTETUTW')->first();
        if (! $carQuote) {
            $this->warn('Car quote not found.');

            return self::FAILURE;
        }

        $voucherCode = MACRMService::generateMotorRevivalVoucherForQuote($carQuote);
        if ($voucherCode === null) {
            $this->warn('Voucher could not be generated (see logs).');

            return self::FAILURE;
        }

        dd($voucherCode);
    }
}
