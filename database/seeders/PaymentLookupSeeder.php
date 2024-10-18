<?php

namespace Database\Seeders;

use App\Enums\LookupsEnum;
use App\Enums\PaymentTooltip;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentLookupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! DB::table('lookups')->where('key', LookupsEnum::PAYMENT_COLLECTION_TYPE)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::PAYMENT_COLLECTION_TYPE, 'code' => 'broker', 'text' => 'Broker', 'description' => PaymentTooltip::COLLECTOR_LIST_BROKER],
                ['key' => LookupsEnum::PAYMENT_COLLECTION_TYPE, 'code' => 'insurer', 'text' => 'Insurer', 'description' => PaymentTooltip::COLLECTOR_LIST_INSURER],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::PAYMENT_FREQUENCY_TYPE)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::PAYMENT_FREQUENCY_TYPE, 'code' => 'upfront', 'text' => 'Upfront', 'description' => PaymentTooltip::FREQUENCY_LIST_UPFRONT],
                ['key' => LookupsEnum::PAYMENT_FREQUENCY_TYPE, 'code' => 'monthly', 'text' => 'Monthly', 'description' => PaymentTooltip::FREQUENCY_LIST_MONTHLY],
                ['key' => LookupsEnum::PAYMENT_FREQUENCY_TYPE, 'code' => 'quarterly', 'text' => 'Quarterly', 'description' => PaymentTooltip::FREQUENCY_LIST_QUARTERLY],
                ['key' => LookupsEnum::PAYMENT_FREQUENCY_TYPE, 'code' => 'semi_annual', 'text' => 'Semi Annual', 'description' => PaymentTooltip::FREQUENCY_LIST_SEMI_ANNUAL],
                ['key' => LookupsEnum::PAYMENT_FREQUENCY_TYPE, 'code' => 'split_payments', 'text' => 'Split Payments', 'description' => PaymentTooltip::FREQUENCY_LIST_SPLIT_PAYMENTS],
                ['key' => LookupsEnum::PAYMENT_FREQUENCY_TYPE, 'code' => 'custom', 'text' => 'Custom', 'description' => PaymentTooltip::FREQUENCY_LIST_CUSTOM],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::PAYMENT_DECLINE_REASON)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::PAYMENT_DECLINE_REASON, 'code' => 'payment_decline_reason_1', 'text' => 'Incorrect payment information, proof of payment or receipt provided'],
                ['key' => LookupsEnum::PAYMENT_DECLINE_REASON, 'code' => 'payment_decline_reason_2', 'text' => 'Payment proof or receipt is not readable'],
                ['key' => LookupsEnum::PAYMENT_DECLINE_REASON, 'code' => 'payment_decline_reason_3', 'text' => 'Insufficient documentation'],
                ['key' => LookupsEnum::PAYMENT_DECLINE_REASON, 'code' => 'payment_decline_reason_4', 'text' => 'No proof of discount approval'],
                ['key' => LookupsEnum::PAYMENT_DECLINE_REASON, 'code' => 'payment_decline_reason_5', 'text' => 'No proof of credit approval'],
                ['key' => LookupsEnum::PAYMENT_DECLINE_REASON, 'code' => 'payment_decline_reason_6', 'text' => 'Other reasons'],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::PAYMENT_CREDIT_APPROVAL_REASON)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::PAYMENT_CREDIT_APPROVAL_REASON, 'code' => 'available_credit_balance', 'text' => 'Available credit balance', 'description' => PaymentTooltip::CREDIT_APPROVAL_LIST_AVAILABLE],
                ['key' => LookupsEnum::PAYMENT_CREDIT_APPROVAL_REASON, 'code' => 'post_dated_cheque_payment', 'text' => 'Post-dated cheque payment', 'description' => PaymentTooltip::CREDIT_APPROVAL_LIST_POSTDATED],
                ['key' => LookupsEnum::PAYMENT_CREDIT_APPROVAL_REASON, 'code' => 'cheque_under_clearance', 'text' => 'Cheque under clearance', 'description' => PaymentTooltip::CREDIT_APPROVAL_LIST_CLEARANCE],
                ['key' => LookupsEnum::PAYMENT_CREDIT_APPROVAL_REASON, 'code' => 'other_reasons', 'text' => 'Other reasons', 'description' => PaymentTooltip::CREDIT_APPROVAL_LIST_REASON],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::PAYMENT_DISCOUNT_TYPE)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::PAYMENT_DISCOUNT_TYPE, 'code' => 'managerial_approval_discount', 'text' => 'Managerial approval discount', 'description' => PaymentTooltip::DISCOUNT_TYPE_LIST_MANAGERIAL],
                ['key' => LookupsEnum::PAYMENT_DISCOUNT_TYPE, 'code' => 'employee_discount', 'text' => 'Employee discount', 'description' => PaymentTooltip::DISCOUNT_TYPE_LIST_EMPLOYEE],
                ['key' => LookupsEnum::PAYMENT_DISCOUNT_TYPE, 'code' => 'family_employee_discount', 'text' => 'Employee family discount', 'description' => PaymentTooltip::DISCOUNT_TYPE_LIST_FAMILY],
                ['key' => LookupsEnum::PAYMENT_DISCOUNT_TYPE, 'code' => LookupsEnum::SYSTEM_ADJUSTED_DISCOUNT, 'text' => 'System adjusted discount', 'description' => 'System generated discount'],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::PAYMENT_DISCOUNT_REASON)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::PAYMENT_DISCOUNT_REASON, 'code' => 'refer_a_friend', 'text' => 'Refer a friend', 'description' => PaymentTooltip::DISCOUNT_TYPE_LIST_REFER],
                ['key' => LookupsEnum::PAYMENT_DISCOUNT_REASON, 'code' => 'promotional_campaign_discount', 'text' => 'Promotional campaign discount', 'description' => PaymentTooltip::DISCOUNT_REASON_LIST_PROMOTIONAL],
                ['key' => LookupsEnum::PAYMENT_DISCOUNT_REASON, 'code' => 'loyalty_reward_discount', 'text' => 'Loyalty reward discount', 'description' => PaymentTooltip::DISCOUNT_REASON_LIST_LOYALTY],
                ['key' => LookupsEnum::PAYMENT_DISCOUNT_REASON, 'code' => 'competitive_pricing_discount', 'text' => 'Competitive pricing discount', 'description' => PaymentTooltip::DISCOUNT_REASON_LIST_COMPETITIVE],
                ['key' => LookupsEnum::PAYMENT_DISCOUNT_REASON, 'code' => 'discount_custom_reason', 'text' => 'Custom discount reason', 'description' => PaymentTooltip::DISCOUNT_REASON_LIST_CUSTOM_REASON],

            ]);
        }
    }
}
