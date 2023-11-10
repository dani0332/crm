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
        // NEED VERIFICATION AT THE END
        //$payments = Payment::all();
        $payments = Payment::where('code', 'CAR-GTUKFY49')->orderBy('created_at')->get();

        foreach ($payments as $payment) {
            // Extract the code and check if it has child payments
            $code = $payment->code;

            $splitPaymentExists = PaymentSplits::where('code', $code)->count();

            if ($splitPaymentExists > 0) {
                continue;
            }
            $childPayments = Payment::where('code', 'like', "$code%")->get();
            $grandTotal = $childPayments->sum('captured_amount');
            $payment->total_payments = $childPayments->count();
            $payment->frequency = 'split_payments';
            $payment->total_price = $grandTotal;
            $payment->total_amount = $grandTotal;
            $payment->collection_type = 'broker';
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
                    if ($childPayment->payment_status_id == 10) { //if paid
                        $collectionAmount = $childPayment->captured_amount;
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
                    ]);

                    $payment_sr_no++;
                    // Delete the child payment from the old table
                    ////$childPayment->delete();
                }
            }
        }

    }
}
