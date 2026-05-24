<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Services\CQF\NonMotor\BaseCQFQuoteMappingService;
use Illuminate\Database\Eloquent\Model;

class BusinessCQFQuoteMappingService extends BaseCQFQuoteMappingService
{
    protected function getQuoteType(): QuoteTypes
    {
        return QuoteTypes::BUSINESS;
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Business;
    }

    protected function getProductName(): string
    {
        return 'Business insurance';
    }

    public function mapRenewalQuote(Model $quote, RenewalsUploadLeads $renewalsUploadLeads, string $quoteUuid): array
    {
        $data = parent::mapRenewalQuote($quote, $renewalsUploadLeads, $quoteUuid);
        if ($data && $quote instanceof PersonalQuote) {
            if ($quote->business_type_of_insurance_id !== BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL) {
                $data['policy_number'] = $quote->policy_number;
                $data['premium'] = $quote->premium;
                $data['company_address'] = $quote->company_address;
            }
            $data['company_name'] = $quote->company_name;
        }

        return $data;
    }

    protected function resolveLobPayment(PersonalQuote $quote): ?object
    {
        $quote->loadMissing('businessQuote.payments');

        return $quote->businessQuote?->payments->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getFailedQuoteDataExtra(PersonalQuote $quote): array
    {
        return [];
    }
}
