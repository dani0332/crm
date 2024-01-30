<?php

namespace Database\Seeders;

use App\Enums\QuoteTypeId;
use App\Models\QuoteStatus;
use App\Models\QuoteStatusMap;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuoteStatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $newQuoteSortIter = 70;
        $newQuoteStatuses = [
            ['code' => 'CancellationPending', 'text' => 'Cancellation Pending', 'mapped_with' => [QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Health, QuoteTypeId::Business, QuoteTypeId::Travel, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Cycle]],
            ['code' => 'PolicyCancelled', 'text' => 'Policy Cancelled', 'mapped_with' => [QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Health, QuoteTypeId::Business, QuoteTypeId::Travel, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Cycle]],
            ['code' => 'Allocated', 'text' => 'Allocated', 'mapped_with' => [QuoteTypeId::Health, QuoteTypeId::Business, QuoteTypeId::Travel, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Cycle]],
            ['code' => 'RenewalTermsReceived', 'text' => 'Renewal Terms Received', 'mapped_with' => [QuoteTypeId::Health, QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Cycle]],
            ['code' => 'ProposalFormRequested', 'text' => 'Proposal Form Requested', 'mapped_with' => [QuoteTypeId::Business]],
            ['code' => 'ProposalFormReceived', 'text' => 'Proposal Form Received', 'mapped_with' => [QuoteTypeId::Business]],
            ['code' => 'PendingRenewalInformation', 'text' => 'Pending Renewal Information', 'mapped_with' => [QuoteTypeId::Business]],
            ['code' => 'AdditionalInformationRequested', 'text' => 'Additional Information Requested', 'mapped_with' => [QuoteTypeId::Business]],
            ['code' => 'QuoteRequested', 'text' => 'Quote Requested', 'mapped_with' => [QuoteTypeId::Business]],
            ['code' => 'FinalizingTerms', 'text' => 'Finalizing Terms', 'mapped_with' => [QuoteTypeId::Business]],
            ['code' => 'QuotedByUW', 'text' => 'Quote by UW', 'mapped_with' => [QuoteTypeId::Business, QuoteTypeId::Travel, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Cycle]],
            ['code' => 'SentForTransactionApproval', 'text' => 'Sent for Transaction Approval', 'mapped_with' => [QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Health, QuoteTypeId::Business, QuoteTypeId::Travel, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Cycle]],
        ];

        foreach ($newQuoteStatuses as $quoteStatus) {
            $quoteStatusDetails = QuoteStatus::firstOrCreate(['code' => $quoteStatus['code']], [
                'text' => $quoteStatus['text'],
                'text_ar' => $quoteStatus['text'],
                'is_active' => 1,
                'sort_order' => ++$newQuoteSortIter,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'bilal.saeed@insurancemarket.ae',
                'updated_by' => 'bilal.saeed@insurancemarket.ae',
            ]);

            foreach ($quoteStatus['mapped_with'] as $mapSortingOrder => $mapped) {
                QuoteStatusMap::firstOrCreate(['quote_type_id' => $mapped, 'quote_status_id' => $quoteStatusDetails->id], [
                    'sort_order' => ++$mapSortingOrder,
                    'created_by' => 'bilal.saeed@insurancemarket.ae',
                    'updated_by' => 'bilal.saeed@insurancemarket.ae',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
