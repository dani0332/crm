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
        $allModelTypes = [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel,
            quoteTypeCode::Home, quoteTypeCode::Yacht, quoteTypeCode::Pet, quoteTypeCode::Cycle,
            quoteTypeCode::Bike, quoteTypeCode::Business, quoteTypeCode::Life,
        ];
        $thirtyDaysOldDate = Carbon::now()->subDays(30)->startOfDay(); // 30 days old date
        //$thirtyDaysOldDate = '2024-03-15 00:00:00'; //For Testing

        echo "MigratePaymentCronDate:: Payment Migration Starts: ".$thirtyDaysOldDate."<br>";
        Log::info('MigratePaymentCronDate:: Payment Migration Starts: '.$thirtyDaysOldDate);
        $totalMigrationCount = 0;
        foreach ($allModelTypes as $modelType) {
            $quoteModelObject = $this->getModelObject(strtolower($modelType));
            //echo $modelType.'--'.$quoteModelObject.'--'.$thirtyDaysOldDate."\n";
            if ($quoteModelObject == '') {
                Log::info('MigratePaymentCron::Model not found for: '.$modelType);

                continue;
            }
            $modelObjects = $quoteModelObject::whereHas('payments', function($q){
                $q->whereNull('total_payments')
                    ->whereNull('frequency')
                    ->where('payment_status_id', PaymentStatusEnum::AUTHORISED);
            })->where('created_at', '>', $thirtyDaysOldDate)->get();
            
            if ($modelObjects->count() > 0) {
                foreach ($modelObjects as $modelObject) {

                    if ($modelObject->payments()->count() == 1) {
                         $oldPayment = $modelObject->payments()->first();
                         if ($oldPayment->code == $modelObject->code) {
                            //echo 'MigratePaymentCron::Payment migrated for code= '.$modelObject->code.' id='.$modelObject->id."<br>";
                            Log::info('MigratePaymentCron::Payment migrated for code= '.$modelObject->code.' id='.$modelObject->id);
                            ////app(SplitPaymentService::class)->migratePayments($oldPayment, $modelType);
                            $totalMigrationCount++;                             
                         } else {
                            Log::info('MigratePaymentCron::Payment migration skipped for: '.$modelObject->code.',having no master payment');                                        
                         }
                        
                    }  else {
                        Log::info('MigratePaymentCron::Payment migration skipped for: '.$modelObject->code.',having payments: '.$modelObject->payments()->count());
                    }                    
                }
            } else {
                Log::info('MigratePaymentCron::No '.$modelType.' found');
            }
        }
        echo "MigratePaymentCronDate:: Payment Migration Ends: ".$totalMigrationCount;
        Log::info('MigratePaymentCronDate:: Payment Migration Ends: '.$totalMigrationCount);
    }
}
