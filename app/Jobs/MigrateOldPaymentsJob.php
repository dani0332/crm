<?php

namespace App\Jobs;

use App\Enums\PaymentStatusEnum;
use App\Enums\quoteTypeCode;
use App\Services\SplitPaymentService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MigrateOldPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use GenericQueriesAllLobs;

    public $tries = 1;
    public $timeout = 7200; // 2 hours
    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job to migrate payments of all last 30 days lobs.
     */
    public function handle(): void
    {
        try {
            $allModelTypes = [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel,
                quoteTypeCode::Home, quoteTypeCode::Yacht, quoteTypeCode::Pet, quoteTypeCode::Cycle,
                quoteTypeCode::Bike, quoteTypeCode::Business, quoteTypeCode::Life,
            ];
            $thirtyDaysOldDate = Carbon::now()->subDays(30)->startOfDay(); // 30 days old date
            //$thirtyDaysOldDate = '2024-03-15 00:00:00'; //For Testing

            info('MigratePaymentJobDate:: Payment Migration Starts: '.$thirtyDaysOldDate);
            $totalMigrationCount = 0;
            foreach ($allModelTypes as $modelType) {
                $quoteModelObject = $this->getModelObject(strtolower($modelType));

                if ($quoteModelObject == '') {
                    info('MigratePaymentJob::Model not found for: '.$modelType);

                    continue;
                }
                $modelObjects = $quoteModelObject::whereHas('payments', function ($q) {
                    $q->whereNull('total_payments')
                        ->whereNull('frequency')
                        ->where('payment_status_id', PaymentStatusEnum::AUTHORISED);
                })->where('created_at', '>', $thirtyDaysOldDate)->get();

                if ($modelObjects->count() > 0) {
                    foreach ($modelObjects as $modelObject) {

                        if ($modelObject->payments()->count() == 1) {
                            $oldPayment = $modelObject->payments()->first();
                            if ($oldPayment->code == $modelObject->code) {
                                info('MigratePaymentJob::Payment migrated for code= '.$modelObject->code.' id='.$modelObject->id);
                                app(SplitPaymentService::class)->migratePayments($oldPayment, $modelType);
                                $totalMigrationCount++;
                            } else {
                                info('MigratePaymentJob::Payment migration skipped for: '.$modelObject->code.',having no master payment');
                            }

                        } else {
                            info('MigratePaymentJob::Payment migration skipped for: '.$modelObject->code.',having payments: '.$modelObject->payments()->count());
                        }
                    }
                } else {
                    info('MigratePaymentJob::No '.$modelType.' found');
                }
            }
            info('MigratePaymentJobDate:: Payment Migration Ends: '.$totalMigrationCount);

        } catch (\Exception $e) {
            Log::error('MigratePaymentJob::Error: '.$e->getMessage());
        }
    }
}
