<?php

namespace Database\Seeders;

use App\Enums\PaymentStatusEnum;
use App\Enums\quoteTypeCode;
use App\Services\SplitPaymentService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class PaymentsMoveInNewTableStructure extends Seeder
{
    use GenericQueriesAllLobs;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info('MigratePaymentSeederDate::Total Payments migrated: '.Carbon::now());
        exit;

        $allModelTypes = [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel,
            quoteTypeCode::Home, quoteTypeCode::Yacht, quoteTypeCode::Pet, quoteTypeCode::Cycle,
            quoteTypeCode::Bike, quoteTypeCode::Business, quoteTypeCode::Life,
        ];
        $thirtyDaysOldDate = Carbon::now()->subDays(330)->startOfDay();
        foreach ($allModelTypes as $modelType) {
            $quoteModelObject = $this->getModelObject(strtolower($modelType));
            echo $modelType.'--'.$quoteModelObject."\n";

            if ($quoteModelObject == '') {
                Log::info('MigratePaymentSeeder::Model not found for: '.$modelType);

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
                            Log::info('MigratePaymentSeeder::Payment migrated for: '.$modelObject->code);
                            // //app(SplitPaymentService::class)->migratePayments($oldPayment[0], $modelType);
                        } else {
                            Log::info('MigratePaymentSeeder::Payment migration skipped for: '.$modelObject->code.',having more than 1 child payments');
                        }
                    } else {
                        Log::info('MigratePaymentSeeder::Payment not found for: '.$modelObject->code);
                    }
                }
            } else {
                Log::info('MigratePaymentSeeder::No '.$modelType.' found');
            }
        }
    }
}
