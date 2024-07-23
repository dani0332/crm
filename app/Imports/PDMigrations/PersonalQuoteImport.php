<?php

namespace App\Imports\PDMigrations;

use App\Enums\PDMigrations\DealStageEnum;
use App\Enums\PDMigrations\PDDealStatus;
use App\Enums\QuoteStatusEnum;
use App\Models\HomeQuote;
use App\Traits\PersonalQuoteSyncTrait;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PersonalQuoteImport implements ToModel, WithChunkReading, WithHeadingRow
{
    use PersonalQuoteSyncTrait;

    public function model(array $row)
    {
        $dealBatch = Date::excelToDateTimeObject($row['deal_batch'])->format('MY');
        if ((isset($row['deal_cdb_id']) || isset($row['deal_policy_number']))) {
            $leadStatusId = $this->getQuoteStatusId($row['deal_status'], $row['deal_stage']);
            if($leadStatusId){
                $data = [
                    'previous_quote_policy_number' => $row['deal_policy_number'] ?? null,
                    'quote_status_id' => $leadStatusId,
                ];
    
                $searchCriteria = [];
    
                $searchCriteria = ['renewal_batch' => strtoupper($dealBatch)];
    
                if (isset($row['deal_cdb_id'])) {
                    [, $value] = explode('-', $row['deal_cdb_id']);
                    $searchCriteria['uuid'] = $value;
                } elseif (isset($row['deal_policy_number'])) {
                    $searchCriteria['previous_quote_policy_number'] = $row['deal_policy_number'];
                }
    
                $lead = HomeQuote::where($searchCriteria)->first();
                if ($lead && $lead->quote_status_id != QuoteStatusEnum::TransactionApproved) {
                    $lead->update($data);
                    info('Personal/Home Qoute Import - Quote found: '.$lead->uuid.' - Quote updated');
                }
            }
            info('Personal/Home Qoute Import - Quote status not defined');
           
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    private function getQuoteStatusId($dealStatus, $dealStage)
    {
        if ($dealStatus === PDDealStatus::LOST) {
            return QuoteStatusEnum::Lost;
        }

        if ($dealStatus === PDDealStatus::WON) {
            return QuoteStatusEnum::TransactionApproved;
        }

        if ($dealStatus === PDDealStatus::OPEN) {
            $dealStageToQuoteStatus = [
                DealStageEnum::LEAD_IN => QuoteStatusEnum::NewLead,
                DealStageEnum::QUOTED_TERMS_SENT => QuoteStatusEnum::RenewalTermsSent,
                DealStageEnum::ALLOCATION => QuoteStatusEnum::Allocated,
                DealStageEnum::QUOTED => QuoteStatusEnum::Quoted,
                DealStageEnum::HOT_POT => QuoteStatusEnum::InNegotiation,
                DealStageEnum::FOR_PAYMENT => QuoteStatusEnum::PaymentPending,
                DealStageEnum::POLICY_ISSUED => QuoteStatusEnum::PolicyIssued,
                DealStageEnum::TEST_LEADS => QuoteStatusEnum::Fake,
                DealStageEnum::LEAD => QuoteStatusEnum::NewLead,
                DealStageEnum::QUALIFIED => QuoteStatusEnum::NewLead,
                DealStageEnum::TERMS_AVAILABLE => QuoteStatusEnum::RenewalTermsReceived,
                DealStageEnum::RENEWAL_TERMS_RECEIVED => QuoteStatusEnum::RenewalTermsReceived,
                DealStageEnum::TERMS_SENT => QuoteStatusEnum::RenewalTermsSent,
                DealStageEnum::RENEWAL_TERMS_SENT => QuoteStatusEnum::RenewalTermsSent,
                DealStageEnum::ENGAGED => QuoteStatusEnum::FollowedUp,
                DealStageEnum::FOR_FOLLOW_UP => QuoteStatusEnum::FollowedUp,
                DealStageEnum::AUTO_FOLLOW_UP => QuoteStatusEnum::FollowedUp,
                DealStageEnum::FOLLOW_UP => QuoteStatusEnum::FollowedUp,
                DealStageEnum::LAST_FOLLOW_UP => QuoteStatusEnum::FollowedUp,
                DealStageEnum::FIRST_FOLLOW_UP => QuoteStatusEnum::FollowedUp,
                DealStageEnum::IN_NEGOTIATION => QuoteStatusEnum::InNegotiation,
                DealStageEnum::ACCEPTED => QuoteStatusEnum::ApplicationPending,
                DealStageEnum::APPLICATION => QuoteStatusEnum::ApplicationPending,
                DealStageEnum::FOR_APPLICATION => QuoteStatusEnum::ApplicationPending,
                DealStageEnum::DOCUMENTS_REQUESTED => QuoteStatusEnum::MissingDocumentsRequested,
                DealStageEnum::WITH_UW => QuoteStatusEnum::ApplicationSubmitted,
                DealStageEnum::UW_QUOTATION => QuoteStatusEnum::ApplicationSubmitted,
                DealStageEnum::PAYMENT_LINK_SENT => QuoteStatusEnum::PaymentPending,
                DealStageEnum::PENDING_PAYMENT => QuoteStatusEnum::PaymentPending,
                DealStageEnum::POLICY_DOCUMENTS_PENDING => QuoteStatusEnum::PolicyDocumentsPending,
                DealStageEnum::PENDING_POLICY => QuoteStatusEnum::PolicyDocumentsPending,
                DealStageEnum::DOCUMENTS => QuoteStatusEnum::PolicyDocumentsPending,
                DealStageEnum::ISSUANCE => QuoteStatusEnum::PolicyIssued,
                DealStageEnum::LOST_CASES => QuoteStatusEnum::Lost,
                DealStageEnum::HANGING_LEADS => QuoteStatusEnum::Fake,
                DealStageEnum::WON => QuoteStatusEnum::TransactionApproved,
                DealStageEnum::LOST => QuoteStatusEnum::Lost,
            ];

            return $dealStageToQuoteStatus[$dealStage];
        }
    }
}
