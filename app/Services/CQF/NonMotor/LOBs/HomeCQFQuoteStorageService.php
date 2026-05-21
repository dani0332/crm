<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\AMLStatusCode;
use App\Enums\QuoteTypeId;
use App\Models\HomeQuote;
use App\Models\HomeQuoteRequestDetail;
use App\Models\PersonalQuote;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;

class HomeCQFQuoteStorageService extends BaseCQFQuoteStorageService
{
    public function __construct(
        HomeCQFQuoteMappingService $mappingService,
        EmbeddedProductRepository $embeddedProductRepository
    ) {
        parent::__construct($mappingService, $embeddedProductRepository);
    }

    protected function getLobName(): string
    {
        return 'home';
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Home;
    }

    protected function copyLobQuoteDetail(PersonalQuote $newQuote, Model $oldQuote): void
    {
        if (! $oldQuote instanceof PersonalQuote) {
            return;
        }
        $this->copyHomeQuoteDetail($newQuote, $oldQuote);
    }

    protected function copyHomeQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldHomeQuote = $oldQuote->homeQuote;

        if ($oldHomeQuote === null) {
            LoggerService::info(self::class.' - No home quote detail found for old quote');

            return;
        }

        $newHomeQuote = HomeQuote::create($this->mapLobRenewalDetail($oldHomeQuote, $newQuote));

        if ($oldHomeQuote->homeQuoteRequestDetail) {
            HomeQuoteRequestDetail::create(['home_quote_request_id' => $newHomeQuote->id]);
        }

        LoggerService::info(self::class.' - Home quote detail copied for renewal quote');
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapLobRenewalDetail(HomeQuote $oldLob, PersonalQuote $newQuote): array
    {
        return [
            'personal_quote_id' => $newQuote->id,
            'uuid' => $newQuote->uuid,
            'code' => $newQuote->code,
            'source' => $newQuote->source,
            'quote_status_id' => $newQuote->quote_status_id,
            'advisor_id' => $newQuote->advisor_id,
            'assignment_type' => $newQuote->assignment_type,
            'renewal_batch_id' => $newQuote->renewal_batch_id,
            'previous_quote_policy_number' => $newQuote->previous_quote_policy_number,
            'previous_quote_policy_premium' => $newQuote->previous_quote_policy_premium,
            'previous_quote_policy_commission' => $newQuote->previous_quote_policy_commission,
            'previous_advisor_id' => $newQuote->previous_advisor_id,
            'previous_policy_start_date' => $this->formatPolicyDate($newQuote->previous_policy_start_date),
            'previous_policy_expiry_date' => $this->formatPolicyDate($newQuote->previous_policy_expiry_date),
            'transaction_type_id' => $newQuote->transaction_type_id,
            'transaction_approved_at' => $newQuote->transaction_approved_at,
            'aml_status' => AMLStatusCode::AMLPending,
            'previous_quote_id' => $oldLob->id,
            'first_name' => $oldLob->first_name,
            'last_name' => $oldLob->last_name,
            'email' => $oldLob->email,
            'mobile_no' => $oldLob->mobile_no,
            'gender' => $oldLob->gender,
            'dob' => $oldLob->dob,
            'lang' => $oldLob->lang,
            'customer_id' => $oldLob->customer_id,
            'nationality_id' => $oldLob->nationality_id,
            'ilivein_accommodation_type_id' => $oldLob->ilivein_accommodation_type_id,
            'iam_possesion_type_id' => $oldLob->iam_possesion_type_id,
            'owner_occupancy_type_id' => $oldLob->owner_occupancy_type_id,
            'coverage_type_id' => $oldLob->coverage_type_id,
            'building_value' => $oldLob->building_value,
            'contents_value_id' => $oldLob->contents_value_id,
            'personal_belongings_value_id' => $oldLob->personal_belongings_value_id,
            'sub_area_id' => $oldLob->sub_area_id,
            'is_property_rented_holiday_home' => $oldLob->is_property_rented_holiday_home,
            'previous_building_aed' => $oldLob->previous_building_aed,
            'previous_contents_aed' => $oldLob->previous_contents_aed,
            'previous_personal_belongings_aed' => $oldLob->previous_personal_belongings_aed,
            'has_contents' => $oldLob->has_contents,
            'has_personal_belongings' => $oldLob->has_personal_belongings,
            'has_building' => $oldLob->has_building,
            'contents_aed' => $oldLob->contents_aed,
            'personal_belongings_aed' => $oldLob->personal_belongings_aed,
            'building_aed' => $oldLob->building_aed,
            'company_name' => $oldLob->company_name,
            'company_address' => $oldLob->company_address,
        ];
    }
}
