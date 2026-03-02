<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\LifeQuote;
use App\Models\LifeQuoteRequestDetail;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\Contracts\CQFQuoteStorageInterface;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LifeCQFQuoteStorageService implements CQFQuoteStorageInterface
{
    public function __construct(
        protected LifeCQFQuoteMappingService $mappingService
    ) {}

    public function storeRenewalQuote(
        Model $quote,
        RenewalsUploadLeads $renewalsUploadLeads,
        int $renewalDaysThreshold,
        array &$epCodes = []
    ): ?Model {
        if (! $quote instanceof PersonalQuote) {
            return null;
        }

        LoggerService::info(self::class.' - Storing life CQF renewal quote');

        $policyExpiryDate = Carbon::parse($quote->policy_expiry_date);
        $policyStartDate = $policyExpiryDate->copy()->addDays(1);
        $newPolicyExpiryDate = $policyStartDate->copy()->addDays($renewalDaysThreshold);

        $quoteUuid = $this->mappingService->generateUUID();
        if ($quoteUuid === null) {
            LoggerService::error(self::class.' - Failed to generate UUID for life renewal quote');

            return null;
        }

        $quoteData = $this->mappingService->mapRenewalQuote($quote, $renewalsUploadLeads, $quoteUuid);
        $quoteData['policy_start_date'] = $policyStartDate;
        $quoteData['policy_expiry_date'] = $newPolicyExpiryDate;

        return DB::transaction(function () use ($quoteData, $quote) {
            $newQuote = PersonalQuote::create($quoteData);
            $newQuote->quoteDetail()->create([]);
            $this->copyLifeQuoteDetail($newQuote, $quote);
            app(EmbeddedProductRepository::class)->saveEmbeddedTransaction($newQuote, QuoteTypeId::Life);

            LoggerService::info(self::class.' - Life CQF renewal quote created successfully', [
                'previous_quote_uuid' => $quote->uuid,
                'new_quote_uuid' => $newQuote->uuid,
            ]);

            return $newQuote;
        });
    }

    protected function copyLifeQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldLifeQuote = $oldQuote->lifeQuote;

        if ($oldLifeQuote === null) {
            LoggerService::info(self::class.' - No life quote detail found for old quote');

            return;
        }

        $data = $this->copyableAttributes($oldLifeQuote->getAttributes(), $newQuote->id, $newQuote->uuid, $newQuote->code);
        $newLifeQuote = LifeQuote::create($data);

        if ($oldLifeQuote->lifeQuoteRequestDetail) {
            $detailAttrs = $oldLifeQuote->lifeQuoteRequestDetail->getAttributes();
            unset($detailAttrs['id'], $detailAttrs['life_quote_request_id'], $detailAttrs['created_at'], $detailAttrs['updated_at']);
            $detailAttrs['life_quote_request_id'] = $newLifeQuote->id;
            LifeQuoteRequestDetail::create($detailAttrs);
        }

        LoggerService::info(self::class.' - Life quote detail copied for renewal quote');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function copyableAttributes(array $attributes, int $personalQuoteId, string $newQuoteUuid, string $newQuoteCode): array
    {
        unset($attributes['id'], $attributes['personal_quote_id'], $attributes['quote_id'], $attributes['created_at'], $attributes['updated_at'], $attributes['uuid'], $attributes['code']);
        $attributes['personal_quote_id'] = $personalQuoteId;
        $attributes['uuid'] = $newQuoteUuid;
        $attributes['code'] = $newQuoteCode;

        return $attributes;
    }
}
