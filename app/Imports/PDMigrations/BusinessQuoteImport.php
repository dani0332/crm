<?php

namespace App\Imports\PDMigrations;

use App\Enums\PDMigrations\DealStageEnum;
use App\Enums\PDMigrations\DealStageInsuranceTypes;
use App\Enums\PDMigrations\PDDealStatus;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Models\BusinessQuote;
use App\Traits\PersonalQuoteSyncTrait;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BusinessQuoteImport implements ToModel, WithChunkReading, WithHeadingRow
{
    use PersonalQuoteSyncTrait;

    public function model(array $row)
    {
        $fullName = $row['person_name']; // Adjust the key based on your column name
        [$firstName, $lastName] = $this->splitName($fullName);
        if (isset($row['deal_cdb_id'])) {
            [, $value] = explode('-', $row['deal_cdb_id']);

            $data = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'previous_quote_policy_number' => $row['deal_policy_number_renewal'],
                'mobile_no' => $row['person_phone_work'],
                'email' => $row['person_email_work'],
                'code' => $row['deal_cdb_id'],
                'uuid' => $value,
                'premium' => str_replace(" AED", "", $row['deal_value']),
                'quote_status_id' => $this->getQuoteStatusId($row['deal_status'], $row['deal_stage']),
                'business_type_of_insurance_id' => $this->getBusinessInsurance($row['deal_types_of_insurance']),
            ];

            $business = BusinessQuote::updateOrCreate(['uuid' => $data['uuid']], $data);
            info('----------- Business Lead Imported  -----------' . $data['code']);
            $this->syncQuote($business, $business->toArray());
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
                DealStageEnum::QUOTES_SENT => QuoteStatusEnum::Quoted,
                DealStageEnum::HOT_POT => QuoteStatusEnum::InNegotiation,
                DealStageEnum::FOR_PAYMENT => QuoteStatusEnum::PaymentPending,
                DealStageEnum::POLICY_ISSUED => QuoteStatusEnum::PolicyIssued,
                DealStageEnum::TEST_LEADS => QuoteStatusEnum::Fake,
                DealStageEnum::LEAD => QuoteStatusEnum::NewLead,
                DealStageEnum::QUALIFIED => QuoteStatusEnum::NewLead,
                DealStageEnum::TERMS_AVAILABLE => QuoteStatusEnum::RenewalTermsReceived,
                DealStageEnum::RENEWAL_TERMS_RECEIVED => QuoteStatusEnum::RenewalTermsReceived,
                DealStageEnum::TERMS_SENT => QuoteStatusEnum::RenewalTermsSent,
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
                DealStageEnum::JAN => QuoteStatusEnum::Lost,
                DealStageEnum::LOST => QuoteStatusEnum::Lost,
                DealStageEnum::FEB => QuoteStatusEnum::Lost,
                DealStageEnum::MARCH => QuoteStatusEnum::Lost,
                DealStageEnum::APR => QuoteStatusEnum::Lost,
                DealStageEnum::MAY => QuoteStatusEnum::Lost,
                DealStageEnum::JULY => QuoteStatusEnum::Lost,
                DealStageEnum::AUG => QuoteStatusEnum::Lost,
                DealStageEnum::SEPT => QuoteStatusEnum::Lost,
                DealStageEnum::DEC => QuoteStatusEnum::Lost,
                DealStageEnum::UNADDRESSED => QuoteStatusEnum::Lost,
                DealStageEnum::PENDING_RENEWAL_INFO => QuoteStatusEnum::PendingRenewalInformation,
                DealStageEnum::DEATILS_PROPOSAL_FROM_REQUESTED => QuoteStatusEnum::ProposalFormRequested,
                DealStageEnum::PROPOSAL_FROM_SENT => QuoteStatusEnum::ProposalFormRequested,
                DealStageEnum::PROPOSAL_SENT => QuoteStatusEnum::ProposalFormRequested,
                DealStageEnum::PROPOSAL_FROM_RECIVIED => QuoteStatusEnum::ProposalFormReceived,
                DealStageEnum::ADDITIONAL_INFORMATION_REQUESTED => QuoteStatusEnum::AdditionalInformationRequested,
                DealStageEnum::QUOTES_RENEWAL_TERMS_REQ => QuoteStatusEnum::QuoteRequested,
                DealStageEnum::QUOTES_REQUEST_UW => QuoteStatusEnum::QuoteRequested,
                DealStageEnum::LEAD_ALLOCATED => QuoteStatusEnum::Allocated,
                DealStageEnum::REMINDER_SENT => QuoteStatusEnum::FollowedUp,
                DealStageEnum::AWAITING_DOCUMENTS => QuoteStatusEnum::MissingDocumentsRequested,
                DealStageEnum::FINALIZING_TERMS_CONDITIONS => QuoteStatusEnum::FinalizingTerms,
                DealStageEnum::PENDING_POLICY_DOCUMENTS => QuoteStatusEnum::PolicyDocumentsPending,

            ];

            return $dealStageToQuoteStatus[$dealStage];
        }
    }

    private function getBusinessInsurance($insuranceType)
    {
        if (!$insuranceType) {
            return quoteBusinessTypeCode::getId(quoteBusinessTypeCode::several);
        }

        $mapping = [
            DealStageInsuranceTypes::WC => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::workmens),
            DealStageInsuranceTypes::OFFICE_INSURANCE_PACKAGE => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::office),
            DealStageInsuranceTypes::PROPERTY => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::property),
            DealStageInsuranceTypes::PAR => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::property),
            DealStageInsuranceTypes::PUBLIC_LIABILITY => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::publicLiability),
            DealStageInsuranceTypes::PRODUCT_LIABILITY_INSURANCE => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::publicLiability),
            DealStageInsuranceTypes::PL => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::publicLiability),
            DealStageInsuranceTypes::PI => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::proIndemnity),
            DealStageInsuranceTypes::PROFESSIONAL_INDEMNITY => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::proIndemnity),
            DealStageInsuranceTypes::CAR_FLEET => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::carFleet),
            DealStageInsuranceTypes::MARINE_CARGO => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::marineCargo),
            DealStageInsuranceTypes::MARINE_HULL => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::marineHull),
            DealStageInsuranceTypes::MARINE_YACHT => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::marineHull),
            DealStageInsuranceTypes::BUSINESS_INTERRUPTION => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::businessInterruption),
            DealStageInsuranceTypes::MACHINERY_BREAKDOWN => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::machineryBreakdown),
            DealStageInsuranceTypes::DEFENCE_BASED_ACT => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::defenceBased),
            DealStageInsuranceTypes::CAR => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::contractorsRisk),
            DealStageInsuranceTypes::CONTRACTORS_ALL_RISKS => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::contractorsRisk),
            DealStageInsuranceTypes::ENG_ANNUAL => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::contractorsRisk),
            DealStageInsuranceTypes::ERECTION_ALL_RISKS => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::erection),
            DealStageInsuranceTypes::TRADE_CREDIT_INSURANCE => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::tradeCredit),
            DealStageInsuranceTypes::TC => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::tradeCredit),
            DealStageInsuranceTypes::JEWELLERS_BLOCK => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::jewellersBlock),
            DealStageInsuranceTypes::MEDMAL => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::medicalMalpractices),
            DealStageInsuranceTypes::MEDICAL_MALPRACTICES => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::medicalMalpractices),
            DealStageInsuranceTypes::KIDNAP_RANSOM => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::kidnapRansom),
            DealStageInsuranceTypes::DIRECTORS_OFFICERS_LIABILITY => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::directorsOfficers),
            DealStageInsuranceTypes::EXTENDED_WARRANTIES => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::extendedWarranties),
            DealStageInsuranceTypes::DRONE_INSURANCE => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::drone),
            DealStageInsuranceTypes::BANCASSURANCE => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::bancassurance),
            DealStageInsuranceTypes::CYBER_INSURANCE => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::cyber),
            DealStageInsuranceTypes::PHOTO => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::photographers),
            DealStageInsuranceTypes::SME => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::smeInsurance),
            DealStageInsuranceTypes::MONEY_INSURANCE => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::moneyInsurance),
            DealStageInsuranceTypes::MARINE_OPEN_COVER => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::marineCargoOpenCover),
            DealStageInsuranceTypes::HOLIDAY_HOMES => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::holidayHomes),
            DealStageInsuranceTypes::GROUP_LIFE => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupLife),
            DealStageInsuranceTypes::I_NEED_SEVERAL_INSURANCES_FOR_MY_BUSINESS => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::several),
        ];

        return $mapping[$insuranceType];
    }
}
