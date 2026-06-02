<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\AMLStatusCode;
use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\QuoteTypeId;
use App\Models\BusinessQuote;
use App\Models\BusinessQuoteRequestDetail;
use App\Models\CustomerMembers;
use App\Models\PersonalQuote;
use App\Models\QuoteRequestEntityMapping;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;

class BusinessCQFQuoteStorageService extends BaseCQFQuoteStorageService
{
    public function __construct(
        BusinessCQFQuoteMappingService $mappingService,
        EmbeddedProductRepository $embeddedProductRepository
    ) {
        parent::__construct($mappingService, $embeddedProductRepository);
    }

    protected function getLobName(): string
    {
        return 'business';
    }

    protected function shouldCopyInsured(): bool
    {
        return false;
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Business;
    }

    protected function copyLobQuoteDetail(PersonalQuote $newQuote, Model $oldQuote): void
    {
        if (! $oldQuote instanceof PersonalQuote) {
            return;
        }
        $this->copyBusinessQuoteDetail($newQuote, $oldQuote);
    }

    protected function copyBusinessQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldBusinessQuote = $oldQuote->businessQuote;

        if ($oldBusinessQuote === null) {
            LoggerService::info(self::class.' - No business quote detail found for old quote');

            return;
        }

        $businessQuote = BusinessQuote::create($this->mapLobRenewalDetail($oldBusinessQuote, $newQuote));
        $newQuote->businessQuote()->associate($businessQuote);
        $newQuote->save();

        if ($oldBusinessQuote->businessQuoteRequestDetail) {
            BusinessQuoteRequestDetail::create(['business_quote_request_id' => $businessQuote->id]);
        }
        $this->copyEntityMapping($oldBusinessQuote, $businessQuote);
        $this->copyCustomerMembers($oldBusinessQuote, $businessQuote);

        LoggerService::info(self::class.' - Business quote detail copied for renewal quote');
    }

    private function copyEntityMapping(BusinessQuote $oldQuote, BusinessQuote $newQuote): void
    {
        $oldMapping = $oldQuote->quoteRequestEntityMapping;
        if ($oldMapping === null) {
            return;
        }

        QuoteRequestEntityMapping::create([
            'quote_type_id' => QuoteTypeId::Business,
            'quote_request_id' => $newQuote->id,
            'entity_id' => $oldMapping->entity_id,
            'entity_type_code' => $oldMapping->entity_type_code,
        ]);
    }
    private function copyCustomerMembers(BusinessQuote $oldQuote, BusinessQuote $newQuote): void
    {
        $oldQuote->customerMembers->each(function (CustomerMembers $member) use ($newQuote) {
            $attrs = $member->getAttributes();
            unset($attrs['id'], $attrs['created_at'], $attrs['updated_at'], $attrs['deleted_at']);
            $attrs['quote_id'] = $newQuote->id;
            CustomerMembers::create($attrs);
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapLobRenewalDetail(BusinessQuote $oldLob, PersonalQuote $newQuote): array
    {
        $data = [
            'personal_quote_id' => $newQuote->id,
            'uuid' => $newQuote->uuid,
            'code' => $newQuote->code,
            'source' => $newQuote->source,
            'quote_status_id' => $newQuote->quote_status_id,
            'advisor_id' => null,
            'assignment_type' => null,
            'renewal_batch_id' => $newQuote->renewal_batch_id,
            'previous_quote_policy_number' => $newQuote->previous_quote_policy_number,
            'previous_quote_policy_premium' => $newQuote->previous_quote_policy_premium,
            'previous_quote_policy_commission' => $newQuote->previous_quote_policy_commission,
            'previous_advisor_id' => $newQuote->previous_advisor_id,
            'previous_policy_start_date' => $this->formatPolicyDate($newQuote->previous_policy_start_date),
            'previous_policy_expiry_date' => $this->formatPolicyDate($newQuote->previous_policy_expiry_date),
            'transaction_type_id' => $newQuote->transaction_type_id,
            'transaction_approved_at' => null,
            'aml_status' => AMLStatusCode::AMLPending,
            'previous_quote_id' => $oldLob->id,
            'first_name' => $oldLob->first_name,
            'last_name' => $oldLob->last_name,
            'email' => $oldLob->email,
            'mobile_no' => $oldLob->mobile_no,
            'gender' => $oldLob->gender,
            'customer_id' => $oldLob->customer_id,
            'nationality_id' => $oldLob->nationality_id,
            'business_type_of_insurance_id' => $oldLob->business_type_of_insurance_id,
            'company_name' => $oldLob->company_name,
            'brief_details' => $oldLob->brief_details,
            'number_of_employees' => $oldLob->number_of_employees,
            'emirate_of_registration_id' => $oldLob->emirate_of_registration_id,
            'group_medical_type_id' => $oldLob->group_medical_type_id,
            'emirates_id' => $oldLob->emirates_id,
        ];

        if ($oldLob->business_type_of_insurance_id !== BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL) {
            $data['company_address'] = $oldLob->company_address;
            $data['premium'] = $oldLob->premium;
        }

        return $data;
    }
}
