<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\PaymentSplits;
use Illuminate\Database\Seeder;

class PaymentsMoveInNewTableStructure extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {       
        
        $payments = Payment::whereNotIn('paymentable_type', ['App\Models\EmbeddedTransaction'])
        ->orderBy('created_at')
        ->get();
        $masterPayments = [];
        foreach ($payments as $payment) {
            // Extract the code and check if it has child payments
            $code = $payment->code;
            $tempCode = explode('-', $code);
            if(count($tempCode) == 3){
                $code = $tempCode[0].'-'.$tempCode[1];
            }

            $masterPaymentExists = Payment::where('code', $code)->count();
            if ( !($masterPaymentExists > 0) ) {
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
        } 
        //print_r($masterPayments); exit;
/*
        foreach($masterPayments as $paymentId=>$masterCode) {
            echo $masterCode . "\n";            
            
            $payment = Payment::where('code', $masterCode)->first();
            $childPayments = Payment::where('code', 'like', "$code%")->whereNot('code',  $masterCode)->whereNot('paymentable_type','App\Models\EmbeddedTransactions')->get();
            if($childPayments->count()>0) {
                $grandTotal = $childPayments->sum('captured_amount');
                $payment->total_payments = $childPayments->count();
                $payment->frequency = 'custom';
                $payment->total_price = $grandTotal;
                $payment->total_amount = $grandTotal;
                $payment->collection_type = 'broker';
                //$payment->captured_amount = $parentCollectionAmount;
                $payment->collection_date = $payment->updated_at;
                $payment->save();

                $payment_sr_no = 1;
                foreach ($childPayments as $childPayment) {

                    if ($childPayment->payment_status_id == 11) { //draft
                        $childPayment->payment_status_id = 14; //new
                    }
                    // Create a new SplitPayment record
                    $collectionAmount = 0;
                    if ($childPayment->payment_status_id == 10 || $childPayment->payment_status_id == 6 ) { //if paid or captured
                        $collectionAmount = $childPayment->captured_amount;
                        $parentCollectionAmount += $childPayment->captured_amount;
                    }

                    PaymentSplits::create([
                        'sr_no' => $payment_sr_no,
                        'code' => $masterCode,
                        'payment_method' => $childPayment->payment_methods_code,
                        'payment_amount' => $childPayment->captured_amount,
                        'due_date' => $childPayment->updated_at,
                        'payment_status_id' => $childPayment->payment_status_id,
                        'collection_amount' => $collectionAmount,
                        'cc_payment_id' => $childPayment->amount,
                        'cc_payment_gateway' => $childPayment->amount,
                        'payment_link' => $childPayment->payment_link,
                        'payment_link_created_at' => $childPayment->payment_link_created_at,
                        'reference' => $childPayment->reference,
                        'authorized_at' => $childPayment->authorized_at,
                        'captured_at' => $childPayment->captured_at,
                        'premium_authorized' => $childPayment->premium_authorized,
                        'premium_captured' => $childPayment->premium_captured,
                        'payment_status_message' => $childPayment->payment_status_message,
                        'payment_gateway_id' => $childPayment->payment_gateway_id,
                        'customer_payment_instrument_id' => $childPayment->customer_payment_instrument_id,
                        'created_at' => $childPayment->created_at,
                        'updated_at' => $childPayment->updated_at,
                    ]);

                    $payment_sr_no++;
                    // Delete the child payment from the old table
                    ////$childPayment->delete();
                }

            } else {
                $payment->total_payments = 1;
                $payment->frequency = 'custom';
                $payment->total_price = $childPayment->captured_amount;
                $payment->total_amount = $childPayment->captured_amount;
                $payment->collection_type = 'broker';
                //$payment->captured_amount = $parentCollectionAmount;
                $payment->collection_date = $payment->updated_at;
                $payment->save();
                $childPayment = $payment;
                $payment_sr_no = 1;
                
                    if ($childPayment->payment_status_id == 11) { //draft
                        $childPayment->payment_status_id = 14; //new
                    }
                    // Create a new SplitPayment record
                    $collectionAmount = 0;
                    if ($childPayment->payment_status_id == 10 || $childPayment->payment_status_id == 6 ) { //if paid or captured
                        $collectionAmount = $childPayment->captured_amount;
                        $parentCollectionAmount += $childPayment->captured_amount;
                    }

                    PaymentSplits::create([
                        'sr_no' => $payment_sr_no,
                        'code' => $masterCode,
                        'payment_method' => $childPayment->payment_methods_code,
                        'payment_amount' => $childPayment->captured_amount,
                        'due_date' => $childPayment->updated_at,
                        'payment_status_id' => $childPayment->payment_status_id,
                        'collection_amount' => $collectionAmount,
                        'cc_payment_id' => $childPayment->amount,
                        'cc_payment_gateway' => $childPayment->amount,
                        'payment_link' => $childPayment->payment_link,
                        'payment_link_created_at' => $childPayment->payment_link_created_at,
                        'reference' => $childPayment->reference,
                        'authorized_at' => $childPayment->authorized_at,
                        'captured_at' => $childPayment->captured_at,
                        'premium_authorized' => $childPayment->premium_authorized,
                        'premium_captured' => $childPayment->premium_captured,
                        'payment_status_message' => $childPayment->payment_status_message,
                        'payment_gateway_id' => $childPayment->payment_gateway_id,
                        'customer_payment_instrument_id' => $childPayment->customer_payment_instrument_id,
                        'created_at' => $childPayment->created_at,
                        'updated_at' => $childPayment->updated_at,
                    ]);
                }
        }        
        exit;
*/


        // NEED VERIFICATION AT THE END
        $payments = Payment::whereNotIn('paymentable_type', ['App\Models\EmbeddedTransaction'])
        ->orderBy('created_at')
        ->get();

        //echo $payments->count(); exit;
        // App\Models\EmbeddedTransactions $payments = Payment::where('code', 'CAR-GTUKFY49')->orderBy('created_at')->get();

        foreach ($payments as $payment) {
            // Extract the code and check if it has child payments
            $code = $payment->code;

            //echo    $payment->paymentable_type . "\n"; continue; 
            $tempCode = explode('-', $code);
            if(count($tempCode) == 3){
                $code = $tempCode[0].'-'.$tempCode[1];
            }

            //echo $code . "\n"; continue;

            $splitPaymentExists = PaymentSplits::where('code', $code)->count();

            if ($splitPaymentExists > 0) {
                continue;
            }

            // verify master payment exists or not
            $masterPaymentExists = Payment::where('code', $code)->count();
            if ( !($masterPaymentExists > 0) ) {
                echo 'master not exists='.$code . "\n"; 
                continue;               
            }

            $parentCollectionAmount = 0;
            if ($payment->payment_status_id == 10 || $payment->payment_status_id == 6 ) { //if paid or captured
                $parentCollectionAmount = $payment->captured_amount;
            }


            $childPayments = Payment::where('code', 'like', "$code%")->whereNotIn('paymentable_type', ['App\Models\EmbeddedTransaction'])->get();
            
            //echo $code . "==".$childPayments->count()."\n"; //continue;
            
            
            $grandTotal = $childPayments->sum('captured_amount');
            $payment->total_payments = $childPayments->count();
            $payment->frequency = 'split_payments';
            $payment->total_price = $grandTotal;
            $payment->total_amount = $grandTotal;
            $payment->collection_type = 'broker';

            if ($payment->payment_status_id == 11) { //draft
                $payment->payment_status_id = 14; //new
            }
            //$payment->captured_amount = $parentCollectionAmount;
            $payment->collection_date = $payment->updated_at;
            $payment->save();

            if ($childPayments->isNotEmpty()) {
                $payment_sr_no = 1;
                foreach ($childPayments as $childPayment) {

                    if ($childPayment->payment_status_id == 11) { //draft
                        $childPayment->payment_status_id = 14; //new
                    }
                    // Create a new SplitPayment record
                    $collectionAmount = 0;
                    if ($childPayment->payment_status_id == 10 || $childPayment->payment_status_id == 6 ) { //if paid or captured
                        $collectionAmount = $childPayment->captured_amount;
                        $parentCollectionAmount += $childPayment->captured_amount;
                    }

                    PaymentSplits::create([
                        'sr_no' => $payment_sr_no,
                        'code' => $code,
                        'payment_method' => $childPayment->payment_methods_code,
                        'payment_amount' => $childPayment->captured_amount,
                        'due_date' => $childPayment->updated_at,
                        'payment_status_id' => $childPayment->payment_status_id,
                        'collection_amount' => $collectionAmount,
                        'cc_payment_id' => $childPayment->amount,
                        'cc_payment_gateway' => $childPayment->amount,
                        'payment_link' => $childPayment->payment_link,
                        'payment_link_created_at' => $childPayment->payment_link_created_at,
                        'reference' => $childPayment->reference,
                        'authorized_at' => $childPayment->authorized_at,
                        'captured_at' => $childPayment->captured_at,
                        'premium_authorized' => $childPayment->premium_authorized,
                        'premium_captured' => $childPayment->premium_captured,
                        'payment_status_message' => $childPayment->payment_status_message,
                        'payment_gateway_id' => $childPayment->payment_gateway_id,
                        'customer_payment_instrument_id' => $childPayment->customer_payment_instrument_id,
                        'created_at' => $childPayment->created_at,
                        'updated_at' => $childPayment->updated_at,
                    ]);

                    $payment_sr_no++;
                    // Delete the child payment from the old table
                    ////$childPayment->delete();
                }
                if ($payment->code==$code) {
                    ////$payment->captured_amount = $parentCollectionAmount;
                    ////$payment->save();
                }
                
            }

        }

    }
}
