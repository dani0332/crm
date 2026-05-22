<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Models\YachtQuote;
use App\Models\YachtQuoteRequestDetail;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;

class YachtCQFQuoteStorageService extends BaseCQFQuoteStorageService
{
    public function __construct(
        YachtCQFQuoteMappingService $mappingService,
        EmbeddedProductRepository $embeddedProductRepository
    ) {
        parent::__construct($mappingService, $embeddedProductRepository);
    }

    protected function getLobName(): string
    {
        return 'yacht';
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Yacht;
    }

    protected function copyLobQuoteDetail(PersonalQuote $newQuote, Model $oldQuote): void
    {
        if (! $oldQuote instanceof PersonalQuote) {
            return;
        }
        $this->copyYachtQuoteDetail($newQuote, $oldQuote);
    }

    protected function copyYachtQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldYachtQuote = $oldQuote->yachtQuote;

        if ($oldYachtQuote === null) {
            LoggerService::info(self::class.' - No yacht quote detail found for old quote');

            return;
        }

        $newYachtQuote = YachtQuote::create($this->mapLobRenewalDetail($oldYachtQuote, $newQuote));

        if ($oldYachtQuote->yachtQuoteRequestDetail) {
            YachtQuoteRequestDetail::create(['yacht_quote_request_id' => $newYachtQuote->id]);
        }

        LoggerService::info(self::class.' - Yacht quote detail copied for renewal quote');
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapLobRenewalDetail(YachtQuote $oldLob, PersonalQuote $newQuote): array
    {
        return [
            'personal_quote_id' => $newQuote->id,
            'uuid' => $newQuote->uuid,
            'code' => $newQuote->code,
            'source' => $newQuote->source,
            'quote_status_id' => $newQuote->quote_status_id,
            'advisor_id' => $newQuote->advisor_id,
            'renewal_batch_id' => $newQuote->renewal_batch_id,
            'previous_quote_policy_number' => $newQuote->previous_quote_policy_number,
            'previous_quote_policy_premium' => $newQuote->previous_quote_policy_premium,
            'previous_quote_policy_commission' => $newQuote->previous_quote_policy_commission,
            'previous_advisor_id' => $newQuote->previous_advisor_id,
            'previous_policy_start_date' => $this->formatPolicyDate($newQuote->previous_policy_start_date),
            'previous_policy_expiry_date' => $this->formatPolicyDate($newQuote->previous_policy_expiry_date),
            'transaction_approved_at' => $newQuote->transaction_approved_at,
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
            'boat_details' => $oldLob->boat_details,
            'engine_details' => $oldLob->engine_details,
            'claim_experience' => $oldLob->claim_experience,
            'sum_insured_value' => $newQuote->asset_value,
            'use' => $oldLob->use,
            'operator_experience' => $oldLob->operator_experience,
        ];
    }
}
