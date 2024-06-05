<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodsAddSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $paymentMethods = PaymentMethod::get();

        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod->tool_tip == null) {
                if ($paymentMethod->code == 'BT') {

                    DB::table('payment_methods')
                        ->where('code', 'BT')
                        ->update(['tool_tip' => 'This payment method involves the customer transferring funds directly from their bank account. It can be done electronically or through physical means such as cash or cheque deposits.']);

                }
                if ($paymentMethod->code == 'CSH') {

                    DB::table('payment_methods')
                        ->where('code', 'CSH')
                        ->update(['tool_tip' => 'With this method, the customer provides physical currency as payment. Ensure proper documentation and receipts when dealing with cash transactions to maintain transparency.']);

                }
                if ($paymentMethod->code == 'CHQ') {

                    DB::table('payment_methods')
                        ->where('code', 'CHQ')
                        ->update(['tool_tip' => 'The customer pays using a cheque that has the current date on it. Ensure the cheque details are correctly filled out and verify its authenticity.']);

                }
                if ($paymentMethod->code == 'CC') {

                    DB::table('payment_methods')
                        ->where('code', 'CC')
                        ->update(['tool_tip' => 'The customer settles their payment using a credit card. This can be done in-person or electronically. Ensure to get authorization and proper documentation for such transactions.']);

                }
                if ($paymentMethod->code == 'IN_PL') {

                    DB::table('payment_methods')
                        ->where('code', 'IN_PL')
                        ->update(['tool_tip' => 'This flexible payment option allows the customer to obtain their insurance policy first and then set up an instalment-based payment plan to settle the total amount due.']);

                }
            }
        }

        $recordExists = PaymentMethod::where('code', 'PDC')->first();
        if (! $recordExists) {
            PaymentMethod::create([
                'code' => 'PDC',
                'name' => 'Post Dated Cheque',
                'description' => 'Post Dated Cheque',
                'tool_tip' => 'This is a cheque given by the customer with a future date on it. It\'s a commitment to pay and cannot be cashed until the date mentioned.',
                'is_active' => true,
            ]);
        }

        $recordExists = PaymentMethod::where('code', 'IP')->first();
        if (! $recordExists) {
            PaymentMethod::create([
                'code' => 'IP',
                'name' => 'Insurer Payment',
                'description' => 'Insurer Payment',
                'tool_tip' => 'This indicates a direct payment to the insurance provider. It\'s not a payment to the broker or agency but directly to the company underwriting the insurance.',
                'is_active' => true,
            ]);
        }

        $recordExists = PaymentMethod::where('code', 'PP')->first();
        if (! $recordExists) {
            PaymentMethod::create([
                'code' => 'PP',
                'name' => 'Partial Payment',
                'description' => 'Partial Payment',
                'tool_tip' => 'This payment method is used when the total amount is divided into multiple payments over a set period. It\'s typically chosen for semi-annual, quarterly, or monthly payment frequencies.',
                'is_active' => true,
            ]);
        }

        $recordExists = PaymentMethod::where('code', 'MP')->first();
        if (! $recordExists) {
            PaymentMethod::create([
                'code' => 'MP',
                'name' => 'Multiple Payment',
                'description' => 'Multiple Payment',
                'tool_tip' => 'When the customer opts to use various methods or sources to pay the total amount, this option is chosen. It\'s often used in conjunction with the split payment method.',
                'is_active' => true,
            ]);
        }

        $recordExists = PaymentMethod::where('code', 'CA')->first();
        if (! $recordExists) {
            PaymentMethod::create([
                'code' => 'CA',
                'name' => 'Credit Approval',
                'description' => 'Credit Approval',
                'tool_tip' => 'This indicates a special payment arrangement where there isn\'t an immediate payment. Instead, the advisor seeks permission from higher-ups to issue the policy first, often due to specific circumstances or arrangements.',
                'is_active' => true,
            ]);
        }

        $recordExists = PaymentMethod::where('code', 'PPR')->first();
        if (! $recordExists) {
            PaymentMethod::create([
                'code' => 'PPR',
                'name' => 'Proforma Payment Request',
                'description' => 'Proforma Payment Request',
                'tool_tip' => 'This is a preliminary payment request drafted and shared with the customer for their review or action. Such requests typically need approval from senior management before being finalized or shared.',
                'is_active' => true,
            ]);
        }
    }
}
