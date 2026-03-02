<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\CycleQuote;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\Contracts\CQFQuoteStorageInterface;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CycleCQFQuoteStorageService implements CQFQuoteStorageInterface
{
    public function __construct(
        protected CycleCQFQuoteMappingService $mappingService
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

        LoggerService::info(self::class.' - Storing cycle CQF renewal quote');

        $policyExpiryDate = Carbon::parse($quote->policy_expiry_date);
        $policyStartDate = $policyExpiryDate->copy()->addDays(1);
        $newPolicyExpiryDate = $policyStartDate->copy()->addDays($renewalDaysThreshold);

        $quoteUuid = $this->mappingService->generateUUID();
        if ($quoteUuid === null) {
            LoggerService::error(self::class.' - Failed to generate UUID for cycle renewal quote');

            return null;
        }

        $quoteData = $this->mappingService->mapRenewalQuote($quote, $renewalsUploadLeads, $quoteUuid);
        $quoteData['policy_start_date'] = $policyStartDate;
        $quoteData['policy_expiry_date'] = $newPolicyExpiryDate;

        $newQuote = PersonalQuote::create($quoteData);

        return DB::transaction(function () use ($newQuote, $quote) {
            $newQuote->quoteDetail()->create([]);
            $this->copyCycleQuoteDetail($newQuote, $quote);
            app(EmbeddedProductRepository::class)->saveEmbeddedTransaction($newQuote, QuoteTypeId::Cycle);

            LoggerService::info(self::class.' - Cycle CQF renewal quote created successfully', [
                'previous_quote_uuid' => $quote->uuid,
                'new_quote_uuid' => $newQuote->uuid,
            ]);

            return $newQuote;
        });
    }

    protected function copyCycleQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldCycleQuote = $oldQuote->cycleQuote;

        if ($oldCycleQuote === null) {
            LoggerService::info(self::class.' - No cycle quote detail found for old quote');

            return;
        }

        $data = $this->copyableAttributes($oldCycleQuote->getAttributes(), $newQuote->id, $newQuote->uuid, $newQuote->code);
        LoggerService::info(self::class.' - Cycle quote detail copied for renewal quote', ['data' => $data]);
        CycleQuote::create($data);

        LoggerService::info(self::class.' - Cycle quote detail copied for renewal quote');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function copyableAttributes(array $attributes, int $personalQuoteId, string $newQuoteUuid, string $newQuoteCode): array
    {
        unset($attributes['id'], $attributes['personal_quote_id'], $attributes['created_at'], $attributes['updated_at'], $attributes['uuid'], $attributes['code']);
        $attributes['personal_quote_id'] = $personalQuoteId;
        $attributes['uuid'] = $newQuoteUuid;
        $attributes['code'] = $newQuoteCode;

        return $attributes;
    }
}
