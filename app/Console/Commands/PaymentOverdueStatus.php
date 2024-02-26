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
        // get payments where payment status is not paid/captured and collection date is less than current date
        $currentTime = now()->format('Y-m-d').' 00:00:00';
        
        $payments = Payment::where('payment_status_id', PaymentStatusEnum::NEW)
            ->where('collection_date', '<', $currentTime)
            ->where('total_payments', '>', 0)
            ->get();

        // update payment status to overdue
        $payments->each(function ($payment) {
            $payment->update(['payment_status_id' => PaymentStatusEnum::OVERDUE]);
        });

        // get all split payments where payment status is not paid/captured and collection date is less than current date
        $splitPayments = PaymentSplits::where('payment_status_id', PaymentStatusEnum::NEW)
            ->where('due_date', '<', $currentTime)
            ->get();

        // update payment status to overdue
        $splitPayments->each(function ($splitPayment) {
            $splitPayment->update(['payment_status_id' => PaymentStatusEnum::OVERDUE]);
        });
        info('PaymentOverdueStatus Command Ends');
    }
}
