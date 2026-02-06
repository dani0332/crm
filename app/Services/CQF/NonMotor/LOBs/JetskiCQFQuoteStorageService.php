<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\JetskiQuote;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\Contracts\CQFQuoteStorageInterface;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class JetskiCQFQuoteStorageService implements CQFQuoteStorageInterface
{
    public function __construct(
        protected JetskiCQFQuoteMappingService $mappingService
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

        LoggerService::info(self::class.' - Storing jetski CQF renewal quote');

        $policyExpiryDate = Carbon::parse($quote->policy_expiry_date);
        $policyStartDate = $policyExpiryDate->copy()->addDays(1);
        $newPolicyExpiryDate = $policyStartDate->copy()->addDays($renewalDaysThreshold);

        $quoteUuid = $this->mappingService->generateUUID();
        if ($quoteUuid === null) {
            LoggerService::error(self::class.' - Failed to generate UUID for jetski renewal quote');

            return null;
        }

        $quoteData = $this->mappingService->mapRenewalQuote($quote, $renewalsUploadLeads, $quoteUuid);
        $quoteData['policy_start_date'] = $policyStartDate;
        $quoteData['policy_expiry_date'] = $newPolicyExpiryDate;

        return DB::transaction(function () use ($quoteData, $quote) {
            $newQuote = PersonalQuote::create($quoteData);
            $newQuote->quoteDetail()->create([]);
            $this->copyJetskiQuoteDetail($newQuote, $quote);
            app(EmbeddedProductRepository::class)->saveEmbeddedTransaction($newQuote, QuoteTypeId::Jetski);

            LoggerService::info(self::class.' - Jetski CQF renewal quote created successfully', [
                'previous_quote_uuid' => $quote->uuid,
                'new_quote_uuid' => $newQuote->uuid,
            ]);

            return $newQuote;
        });
    }

    protected function copyJetskiQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldJetskiQuote = $oldQuote->jetskiQuote;

        if ($oldJetskiQuote === null) {
            LoggerService::info(self::class.' - No jetski quote detail found for old quote');

            return;
        }

        $data = $this->copyableAttributes($oldJetskiQuote->getAttributes(), $newQuote->id);
        JetskiQuote::create($data);

        LoggerService::info(self::class.' - Jetski quote detail copied for renewal quote');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function copyableAttributes(array $attributes, int $personalQuoteId): array
    {
        unset($attributes['id'], $attributes['personal_quote_id'], $attributes['created_at'], $attributes['updated_at']);
        $attributes['personal_quote_id'] = $personalQuoteId;

        return $attributes;
    }
}
