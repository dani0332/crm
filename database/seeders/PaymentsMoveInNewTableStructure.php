<?php

namespace Database\Seeders;

use App\Enums\PaymentStatusEnum;
use App\Enums\quoteTypeCode;
use App\Models\Payment;
use App\Services\SplitPaymentService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class PaymentsMoveInNewTableStructure extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info('MigratePaymentSeeder::Payment migration started');
        $skipEmbededProducts = ['App\Models\EmbeddedTransactions', 'App\Models\EmbeddedTransaction'];
        $payments = Payment::whereNotIn('paymentable_type', $skipEmbededProducts)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->where('total_payments', null)
            ->where('frequency', null)
            ->where('payment_status_id', PaymentStatusEnum::AUTHORISED)
        //->where('code', 'CAR-GTUKFY49')
            ->orderBy('created_at')
            ->get();

        if ($payments->count() > 0) {
            $ecommerceLob = ['P', 'TRA', 'HEA', 'CAR'];
            foreach ($payments as $payment) {
                // Extract the code and check if it has child payments
                $code = $payment->code;
                $tempCode = explode('-', $code);

                if (! in_array($tempCode[0], $ecommerceLob)) {
                    $modelType = 'Car';
                    switch ($tempCode[0]) {
                        case 'BUS':
                            $tempCode[0] = quoteTypeCode::Business;
                            break;
                        case 'PET':
                            $tempCode[0] = quoteTypeCode::Pet;
                            break;
                        case 'CYC':
                            $tempCode[0] = quoteTypeCode::Cycle;
                            break;
                        case 'BIK':
                            $tempCode[0] = quoteTypeCode::Bike;
                            break;
                        case 'YAC':
                            $tempCode[0] = quoteTypeCode::Yacht;
                            break;
                        case 'HOM':
                            $tempCode[0] = quoteTypeCode::Home;
                            break;
                        case 'LIF':
                            $tempCode[0] = quoteTypeCode::Life;
                            break;
                    }
                    //echo $tempCode[0]."\n";
                }
                if (count($tempCode) == 2) {
                    Log::info('MigratePaymentSeeder::Payment migration for Payment Code: '.$payment->code);
                    //app(SplitPaymentService::class)->migratePayments($payment,$tempCode[0]);
                }
            }
        }
        Log::info('MigratePaymentSeeder::Total Payments migrated: '.$payments->count());

        /* IF PARENT DOES NOT EXISTS THEN CREATE A NEW PAYMENT */
        /*$payments = Payment::whereNotIn('paymentable_type', ['App\Models\EmbeddedTransaction'])
            ->orderBy('created_at')
            ->get();
        $masterPayments = [];
        foreach ($payments as $payment) {
            // Extract the code and check if it has child payments
            $code = $payment->code;
            $tempCode = explode('-', $code);
            if (count($tempCode) == 3) {
                $code = $tempCode[0].'-'.$tempCode[1];
            }

            $masterPaymentExists = Payment::where('code', $code)->count();
            if (! ($masterPaymentExists > 0)) {
                //echo 'master not exists='.$code . "\n";
                $childPayments = Payment::where('code', 'like', "$code%")->whereNotIn('paymentable_type', ['App\Models\EmbeddedTransaction'])->first();
                $newPayment = Payment::create([
                    'code' => $code,
                    'paymentable_id' => $childPayments->paymentable_id,
                    'paymentable_type' => $childPayments->paymentable_type,
                    'payment_status_id' => $childPayments->payment_status_id,
                    'payment_methods_code' => $childPayments->payment_methods_code,
                    'insurance_provider_id' => $childPayments->insurance_provider_id,
                    'created_at' => $childPayments->created_at,
                    'updated_at' => $childPayments->updated_at,
                ]);
            //continue;
            } else {
                //$masterPayments[$code] = $code;
            }
            $masterPayments[$code] = $code;
        }*/
    }
}
