<?php

namespace Database\Seeders;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\QuoteStatus;
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
            ['id' => QuoteStatusEnum::CancellationPending, 'code' => 'CancellationPending', 'text' => 'Cancellation Pending', 'mapped_with' => [QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Health, QuoteTypeId::Business, QuoteTypeId::Travel, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Cycle]],
            ['id' => QuoteStatusEnum::PolicyCancelled, 'code' => 'PolicyCancelled', 'text' => 'Policy Cancelled', 'mapped_with' => [QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Health, QuoteTypeId::Business, QuoteTypeId::Travel, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Cycle]],
            ['id' => QuoteStatusEnum::Allocated, 'code' => 'Allocated', 'text' => 'Allocated', 'mapped_with' => [QuoteTypeId::Health, QuoteTypeId::Business, QuoteTypeId::Travel, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Cycle, QuoteTypeId::Yacht]],
            ['id' => QuoteStatusEnum::RenewalTermsReceived, 'code' => 'RenewalTermsReceived', 'text' => 'Renewal Terms Received', 'mapped_with' => [QuoteTypeId::Health, QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Cycle]],
            ['id' => QuoteStatusEnum::ProposalFormRequested, 'code' => 'ProposalFormRequested', 'text' => 'Proposal Form Requested', 'mapped_with' => [QuoteTypeId::Business]],
            ['id' => QuoteStatusEnum::ProposalFormReceived, 'code' => 'ProposalFormReceived', 'text' => 'Proposal Form Received', 'mapped_with' => [QuoteTypeId::Business]],
            ['id' => QuoteStatusEnum::PendingRenewalInformation, 'code' => 'PendingRenewalInformation', 'text' => 'Pending Renewal Information', 'mapped_with' => [QuoteTypeId::Business]],
            ['id' => QuoteStatusEnum::AdditionalInformationRequested, 'code' => 'AdditionalInformationRequested', 'text' => 'Additional Information Requested', 'mapped_with' => [QuoteTypeId::Business]],
            ['id' => QuoteStatusEnum::QuoteRequested, 'code' => 'QuoteRequested', 'text' => 'Quote Requested', 'mapped_with' => [QuoteTypeId::Business]],
            ['id' => QuoteStatusEnum::FinalizingTerms, 'code' => 'FinalizingTerms', 'text' => 'Finalizing Terms', 'mapped_with' => [QuoteTypeId::Business]],
            ['id' => QuoteStatusEnum::QuotedByUW, 'code' => 'QuotedByUW', 'text' => 'Quote by UW', 'mapped_with' => [QuoteTypeId::Business, QuoteTypeId::Travel, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Cycle]],
            ['id' => QuoteStatusEnum::SentForTransactionApproval, 'code' => 'SentForTransactionApproval', 'text' => 'Sent for Transaction Approval', 'mapped_with' => [QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Health, QuoteTypeId::Business, QuoteTypeId::Travel, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Cycle]],
            ['id' => QuoteStatusEnum::RenewalTermsSent, 'code' => 'RenewalTermsSent', 'text' => 'Renewal Terms Sent', 'mapped_with' => [QuoteTypeId::Health, QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Cycle, QuoteTypeId::Yacht]],
        ];

        foreach ($newQuoteStatuses as $quoteStatus) {
            if (! QuoteStatus::where('id', $quoteStatus['id'])->first()) {
                QuoteStatus::create([
                    'id' => $quoteStatus['id'],
                    'text' => $quoteStatus['text'],
                    'text_ar' => $quoteStatus['text'],
                    'code' => $quoteStatus['code'],
                    'sort_order' => ++$newQuoteSortIter,
                    'is_active' => 1,
                    'created_by' => 'bilal.saeed@myalfred.ae',
                    'updated_by' => 'bilal.saeed@myalfred.ae',
                ]);

                foreach ($quoteStatus['mapped_with'] as $mapSortingOrder => $mapped) {
                    $mapping = DB::table('quote_status_map')->where(['quote_type_id' => $mapped, 'quote_status_id' => $quoteStatus['id']])->first();
                    if (! $mapping) {
                        DB::table('quote_status_map')->insert([
                            'quote_type_id' => $mapped,
                            'quote_status_id' => $quoteStatus['id'],
                            'sort_order' => ++$mapSortingOrder,
                            'created_by' => 'bilal.saeed@myalfred.ae',
                            'updated_by' => 'bilal.saeed@myalfred.ae',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}
