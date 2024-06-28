<?php

namespace App\Imports;

use App\Enums\PDMigrations\DealStageEnum;
use App\Enums\PDMigrations\PDDealStatus;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\HomeQuote;
use App\Models\PersonalQuote;
use App\Traits\PersonalQuoteSyncTrait;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PersonalQuoteImport implements ToModel, WithChunkReading, WithHeadingRow
{
    use PersonalQuoteSyncTrait;

    public function model(array $row)
    {
        $fullName = $row['deal_contact_person']; // Adjust the key based on your column name
        [$firstName, $lastName] = $this->splitName($fullName);
        if (isset($row['deal_cdb_id'])) {
            [, $value] = explode('-', $row['deal_cdb_id']);
            $classInstance = null;

            if ($row['deal_type_of_insurance'] === QuoteTypes::HOME->value) {
                $data = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'previous_quote_policy_number' => $row['deal_policy_number'],
                    'mobile_no' => $row['person_phone_work'],
                    'source' => $row['deal_source_of_inquiry'],
                    'email' => $row['person_email_work'],
                    'code' => $row['deal_cdb_id'],
                    'uuid' => $value,
                    'premium' => $row['deal_value'],
                    'quote_status_id' => $this->getQuoteStatusId($row['deal_status'], $row['deal_stage']),
                ];
                $classInstance = HomeQuote::updateOrCreate(['uuid' => $data['uuid']], $data);

            } else {

                $data = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'previous_quote_policy_number' => $row['deal_policy_number'],
                    'mobile_no' => $row['person_phone_work'],
                    'source' => $row['deal_source_of_inquiry'],
                    'email' => $row['person_email_work'],
                    'code' => $row['deal_cdb_id'],
                    'uuid' => $value,
                    'premium' => $row['deal_value'],
                    'quote_status_id' => $this->getQuoteStatusId($row['deal_status'], $row['deal_stage']),
                ];

                $classInstance = PersonalQuote::updateOrCreate(['uuid' => $data['uuid']], $data);

            }

            info('----------- Importing Personal/Home Qoute Lead  -----------' . $row['deal_cdb_id']);
            $this->syncQuote($classInstance, $classInstance->toArray());
        }
    }

    public function chunkSize(): int
    {
        return 5000;
    }
    /**
     * Split the full name into first and last names.
     *
     * @param  string  $fullName
     * @return array
     */
    private function splitName($fullName)
    {
        $nameParts = explode(' ', $fullName);
        $firstName = $nameParts[0];
        $lastName = end($nameParts);

        return [$firstName, $lastName];
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
