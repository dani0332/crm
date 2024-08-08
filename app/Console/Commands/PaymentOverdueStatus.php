<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use App\Models\PaymentSplits;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
        // update payment where payment status is NEW and due date is less than current date
        $currentTime = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');

        $newMasterPayments = Payment::where('payment_status_id', PaymentStatusEnum::NEW)
            ->where('collection_date', '<', $currentTime)
            ->where('total_payments', '>', 0)
            ->count();

        if ($newMasterPayments > 0) {
            try {
                Payment::where('payment_status_id', PaymentStatusEnum::NEW)
                    ->where('collection_date', '<', $currentTime)
                    ->where('total_payments', '>', 0)
                    ->update(['payment_status_id' => PaymentStatusEnum::OVERDUE]);
                info('Payments successfully updated to overdue status.');
            } catch (Exception $e) {
                // Log the error
                Log::error('Error updating payments to overdue status: '.$e->getMessage());
            }
        }

        // update payment splits where payment status is NEW and due date is less than current date
        $newChildPayments = PaymentSplits::where('payment_status_id', PaymentStatusEnum::NEW)
            ->where('due_date', '<', $currentTime)
            ->count();
        if ($newChildPayments > 0) {
            try {
                PaymentSplits::where('payment_status_id', PaymentStatusEnum::NEW)
                    ->where('due_date', '<', $currentTime)
                    ->update(['payment_status_id' => PaymentStatusEnum::OVERDUE]);
                info('Split payments successfully updated to overdue status.');
            } catch (Exception $e) {
                // Log the error
                Log::error('Error updating split payments to overdue status: '.$e->getMessage());
            }
        }
    }
}
