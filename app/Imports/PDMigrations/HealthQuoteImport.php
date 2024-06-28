<?php

namespace App\Imports;

use App\Enums\PDMigrations\DealStageEnum;
use App\Enums\PDMigrations\PDDealStatus;
use App\Enums\QuoteStatusEnum;
use App\Models\HealthQuote;
use App\Traits\PersonalQuoteSyncTrait;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HealthQuoteImport implements ToModel, WithChunkReading, WithHeadingRow
{
    use PersonalQuoteSyncTrait;

    public function model(array $row)
    {
        if (isset($row['deal_cdb_id'])) {
            [, $value] = explode('-', $row['deal_cdb_id']);
            $quoteStatusId = $this->getQuoteStatusId($row['deal_status'], $row['deal_stage']);
            if ($quoteStatusId) {
                $health = HealthQuote::updateOrCreate(['uuid' => $value], [
                    'first_name' => $row['person_first_name'],
                    'last_name' => $row['person_last_name'],
                    'previous_quote_policy_number' => $row['deal_policy_number'],
                    'mobile_no' => $row['person_phone_work'],
                    'source' => $row['deal_lead_source'],
                    'email' => $row['person_email_work'],
                    'code' => $row['deal_cdb_id'],
                    'uuid' => $value,
                    'premium' => str_replace(" AED", "", $row['deal_value']),
                    'quote_status_id' => $quoteStatusId,
                ]);

                info('----------- Health Lead Imported  -----------' . $row['deal_cdb_id']);
                $this->syncQuote($health, $health->toArray());
            }
        }
    }

    public function chunkSize(): int
    {
        return 5000;
    }

    private function getQuoteStatusId($dealStatus, $dealStage)
    {
        if ($dealStatus === PDDealStatus::LOST && $dealStage === DealStageEnum::FOLLOW_UP) {
            return QuoteStatusEnum::Lost;
        }

        if ($dealStatus === PDDealStatus::WON && $dealStage === DealStageEnum::PENDING_POLICY_DOCUMENTS) {
            return QuoteStatusEnum::TransactionApproved;
        }

        if ($dealStatus === PDDealStatus::OPEN) {

            $dealStageToQuoteStatus = [
                DealStageEnum::LEAD()->getToLowerCase() => QuoteStatusEnum::NewLead,
                DealStageEnum::QUALIFIED()->getToLowerCase() => QuoteStatusEnum::NewLead,
                DealStageEnum::LEAD_IN()->getToLowerCase() => QuoteStatusEnum::NewLead,
                DealStageEnum::TERMS_AVAILABLE()->getToLowerCase() => QuoteStatusEnum::RenewalTermsReceived,
                DealStageEnum::RENEWAL_TERMS_REVD()->getToLowerCase() => QuoteStatusEnum::RenewalTermsReceived,
                DealStageEnum::ALLOCATION()->getToLowerCase() => QuoteStatusEnum::Allocated,
                DealStageEnum::TERMS_SENT()->getToLowerCase() => QuoteStatusEnum::RenewalTermsSent,
                DealStageEnum::RENEWAL_TERMS_SENT()->getToLowerCase() => QuoteStatusEnum::RenewalTermsSent,
                DealStageEnum::QUOTED()->getToLowerCase() => QuoteStatusEnum::Quoted,
                DealStageEnum::QUOTED_TERMS_SENT()->getToLowerCase() => QuoteStatusEnum::Quoted,
                DealStageEnum::ENGAGED()->getToLowerCase() => QuoteStatusEnum::FollowedUp,
                DealStageEnum::FOR_FOLLOW_UP()->getToLowerCase() => QuoteStatusEnum::FollowedUp,
                DealStageEnum::AUTO_FOLLOW_UP()->getToLowerCase() => QuoteStatusEnum::FollowedUp,
                DealStageEnum::FOLLOW_UP()->getToLowerCase() => QuoteStatusEnum::FollowedUp,
                DealStageEnum::LAST_FOLLOW_UP()->getToLowerCase() => QuoteStatusEnum::FollowedUp,
                DealStageEnum::FIRST_FOLLOW_UP()->getToLowerCase() => QuoteStatusEnum::FollowedUp,
                DealStageEnum::IN_NEGOTIATION()->getToLowerCase() => QuoteStatusEnum::InNegotiation,
                DealStageEnum::ACCEPTED()->getToLowerCase() => QuoteStatusEnum::ApplicationPending,
                DealStageEnum::APPLICATION()->getToLowerCase() => QuoteStatusEnum::ApplicationPending,
                DealStageEnum::FOR_APPLICATION()->getToLowerCase() => QuoteStatusEnum::ApplicationPending,
                DealStageEnum::DOCUMENTS_REQUESTED()->getToLowerCase() => QuoteStatusEnum::MissingDocumentsRequested,
                DealStageEnum::WITH_UW()->getToLowerCase() => QuoteStatusEnum::ApplicationSubmitted,
                DealStageEnum::UW_QUOTATION()->getToLowerCase() => QuoteStatusEnum::ApplicationSubmitted,
                DealStageEnum::FOR_PAYMENT()->getToLowerCase() => QuoteStatusEnum::PaymentPending,
                DealStageEnum::PAYMENT_LINK_SENT()->getToLowerCase() => QuoteStatusEnum::PaymentPending,
                DealStageEnum::PENDING_PAYMENT()->getToLowerCase() => QuoteStatusEnum::PaymentPending,
                DealStageEnum::POLICY_DOCUMENTS_PENDING()->getToLowerCase() => QuoteStatusEnum::PolicyDocumentsPending,
                DealStageEnum::PENDING_POLICY()->getToLowerCase() => QuoteStatusEnum::PolicyDocumentsPending,
                DealStageEnum::PENDING_POLICY_DOCS()->getToLowerCase() => QuoteStatusEnum::PolicyDocumentsPending,
                DealStageEnum::DOCUMENTS()->getToLowerCase() => QuoteStatusEnum::PolicyDocumentsPending,
                DealStageEnum::ISSUANCE()->getToLowerCase() => QuoteStatusEnum::PolicyIssued,
                DealStageEnum::GROUP_EBP_SERVICE()->getToLowerCase() => QuoteStatusEnum::PolicyIssued,
                DealStageEnum::LOST_CASES()->getToLowerCase() => QuoteStatusEnum::Lost,
                DealStageEnum::TEST_LEADS()->getToLowerCase() => QuoteStatusEnum::Fake,
                DealStageEnum::HANGING_LEADS()->getToLowerCase() => QuoteStatusEnum::Fake,
                DealStageEnum::WON()->getToLowerCase() => QuoteStatusEnum::TransactionApproved,
                DealStageEnum::LOST()->getToLowerCase() => QuoteStatusEnum::Lost,
            ];

            return $dealStageToQuoteStatus[strtolower($dealStage)];
        }
    }
}
