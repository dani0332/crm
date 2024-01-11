<?php

namespace Database\Seeders;

use App\Enums\QuoteTypeId;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ActivitySchedulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quoteTypeIds = [QuoteTypeId::Health, QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Cycle];
        $activitySchedule = [
            'Allocated' => [
                'text' => 'Allocated',
                'mapped_with' => $quoteTypeIds,
            ],
            'RenewalTermsReceived' => [
                'text' => 'Renewal Terms Received',
                'mapped_with' => $quoteTypeIds,
            ],
            'ProposalFormRequested' => [
                'text' => 'Proposal Form Requested',
                'mapped_with' => [QuoteTypeId::Business],
            ],
            'ProposalFormReceived' => [
                'text' => 'Proposal Form Received',
                'mapped_with' => [QuoteTypeId::Business],
            ],
            'PendingRenewalInformation' => [
                'text' => 'Pending Renewal Information',
                'mapped_with' => [QuoteTypeId::Business],
            ],
            'AdditionalInformationRequested' => [
                'text' => 'Additional Information Requested',
                'mapped_with' => [QuoteTypeId::Business],
            ],
            'QuoteRequested' => [
                'text' => 'Quote Requested',
                'mapped_with' => [QuoteTypeId::Business],
            ],
            'FinalizingTerms' => [
                'text' => 'Finalizing Terms',
                'mapped_with' => [QuoteTypeId::Business],
            ],
            'QuotedByUW' => [
                'text' => 'Quote by UW',
                'mapped_with' => $quoteTypeIds,
            ],
            'SentForTransactionApproval' => [
                'text' => 'Sent for Transaction Approval',
                'mapped_with' => $quoteTypeIds,
            ],
            'CancellationPending' => [
                'text' => 'Cancellation Pending',
                'mapped_with' => $quoteTypeIds,
            ],
            'PolicySentToCustomer' => [
                'text' => 'Policy sent to customer',
                'mapped_with' => $quoteTypeIds,
            ],
            'PolicyBooked' => [
                'text' => 'Policy Booked',
                'mapped_with' => $quoteTypeIds,
            ],
            'PolicyCancelled' => [
                'text' => 'Policy Cancelled',
                'mapped_with' => $quoteTypeIds,
            ],
        ];

        foreach ($quoteTypeIds as $quoteTypeId) {

        }
    }
}
