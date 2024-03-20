<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatusEnum;
use App\Enums\quoteTypeCode;
use App\Models\Payment;
use App\Services\SplitPaymentService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MigrateOldPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'MigrateOldPayments:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate old payments to new table structure';
    use GenericQueriesAllLobs;
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //Log::info('MigratePaymentCronDate:: Payment Migration Starts: '.Carbon::now());
        //return;

        $allModelTypes = [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel,
            quoteTypeCode::Home, quoteTypeCode::Yacht, quoteTypeCode::Pet, quoteTypeCode::Cycle,
            quoteTypeCode::Bike, quoteTypeCode::Business, quoteTypeCode::Life,
        ];
        $thirtyDaysOldDate = Carbon::now()->subDays(30)->startOfDay(); // 30 days old date
        $thirtyDaysOldDate = '2024-03-15 00:00:00'; //For Testing
        
        
        Log::info('MigratePaymentCronDate:: Payment Migration Starts: '.$thirtyDaysOldDate);
        $totalMigrationCount = 0;
        foreach ($allModelTypes as $modelType) {
            $quoteModelObject = $this->getModelObject(strtolower($modelType));
            //echo $modelType.'--'.$quoteModelObject.'--'.$thirtyDaysOldDate."\n";
            if ($quoteModelObject == '') {
                Log::info('MigratePaymentCron::Model not found for: '.$modelType);

                continue;
            }
            $modelObjects = $quoteModelObject::where('created_at', '>', $thirtyDaysOldDate)->get();

            if ($modelObjects->count() > 0) {
                foreach ($modelObjects as $modelObject) {

                    if ($modelObject->payments()->count() > 0) {
                        $oldPayment = $modelObject->payments()
                            ->where('code', $modelObject->code)
                            ->where('total_payments', null)
                            ->where('frequency', null)
                            ->where('payment_status_id', PaymentStatusEnum::AUTHORISED)
                            ->get();

                        if ($oldPayment->count() == 1) {
                            //echo 'MigratePaymentCron::Payment migrated for: '.$modelObject->code.'-'.$modelObject->id."\n";
                            Log::info('MigratePaymentCron::Payment migrated for: '.$modelObject->code.'-'.$modelObject->id);
                            app(SplitPaymentService::class)->migratePayments($oldPayment[0], $modelType);
                            $totalMigrationCount++;
                        } else {
                            Log::info('MigratePaymentCron::Payment migration skipped for: '.$modelObject->code.',having no/more than 1 child payments');
                        }
                    } else {
                        Log::info('MigratePaymentCron::Payment not found for: '.$modelObject->code);
                    }
                }
            } else {
                Log::info('MigratePaymentCron::No '.$modelType.' found');
            }
        }
        Log::info('MigratePaymentCronDate:: Payment Migration Ends: '.$totalMigrationCount);
    }
}
