<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use App\Models\PaymentSplits;
use Illuminate\Console\Command;

class PaymentOverdueStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'PaymentOverdueStatus:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will update payment status to overdue if payment is not paid/captured within Due Date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info('PaymentOverdueStatus Command Started');
        // update payment where payment status is NEW and due date is less than current date
        $currentTime = now()->format('Y-m-d').' 00:00:00';

        Payment::where('payment_status_id', PaymentStatusEnum::NEW)
            ->where('collection_date', '<', $currentTime)
            ->where('total_payments', '>', 0)
            ->update(['payment_status_id' => PaymentStatusEnum::OVERDUE]);

        // update payment splits where payment status is NEW and due date is less than current date
        PaymentSplits::where('payment_status_id', PaymentStatusEnum::NEW)
            ->where('due_date', '<', $currentTime)
            ->update(['payment_status_id' => PaymentStatusEnum::OVERDUE]);

        info('PaymentOverdueStatus Command Ends');
    }
}
