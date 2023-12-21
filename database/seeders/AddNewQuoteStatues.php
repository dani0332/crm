<?php

namespace Database\Seeders;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\QuoteStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddNewQuoteStatues extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! QuoteStatus::where('id', QuoteStatusEnum::PolicyCancelled)->first()) {
            $policyCancelled = QuoteStatus::create([
                'id' => QuoteStatusEnum::PolicyCancelled,
                'text' => 'Policy Cancelled',
                'text_ar' => 'Policy Cancelled',
                'code' => 'PolicyCancelled',
                'sort_order' => 19,
                'is_active' => 1,
                'created_by' => 'usman.ejaz@insurancemarket.ae',
                'updated_by' => 'usman.ejaz@insurancemarket.ae',
            ]);

            $policyCancelled->quoteStatusMap()->create([
                'quote_type_id' => QuoteTypeId::Car,
                'sort_order' => 19,
                'created_by' => 'usman.ejaz@insurancemarket.ae',
                'updated_by' => 'usman.ejaz@insurancemarket.ae',
            ]);
        }

        if (! QuoteStatus::where('id', QuoteStatusEnum::CancellationPending)->first()) {
            $cancellationPending = QuoteStatus::create([
                'id' => QuoteStatusEnum::CancellationPending,
                'text' => 'Cancellation Pending',
                'text_ar' => 'Cancellation Pending',
                'code' => 'CancellationPending',
                'sort_order' => 19,
                'is_active' => 1,
                'created_by' => 'usman.ejaz@insurancemarket.ae',
                'updated_by' => 'usman.ejaz@insurancemarket.ae',
            ]);

            $cancellationPending->quoteStatusMap()->create([
                'quote_type_id' => QuoteTypeId::Car,
                'sort_order' => 19,
                'created_by' => 'usman.ejaz@insurancemarket.ae',
                'updated_by' => 'usman.ejaz@insurancemarket.ae',
            ]);
        }

        if (! QuoteStatus::where('id', QuoteStatusEnum::PolicyBooked)->first()) {
            $policyBooked = QuoteStatus::create([
                'id' => QuoteStatusEnum::PolicyBooked,
                'text' => 'Policy Booked',
                'text_ar' => 'Policy Booked',
                'code' => 'PolicyBooked',
                'sort_order' => 19,
                'is_active' => 1,
                'created_by' => 'usman.ejaz@insurancemarket.ae',
                'updated_by' => 'usman.ejaz@insurancemarket.ae',
            ]);

            $policyBooked->quoteStatusMap()->create([
                'quote_type_id' => QuoteTypeId::Car,
                'sort_order' => 19,
                'created_by' => 'usman.ejaz@insurancemarket.ae',
                'updated_by' => 'usman.ejaz@insurancemarket.ae',
            ]);
        }
    }
}
