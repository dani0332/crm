<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Models\SavingsQuote;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\Contracts\CQFQuoteStorageInterface;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SavingsCQFQuoteStorageService implements CQFQuoteStorageInterface
{
    public function __construct(
        protected SavingsCQFQuoteMappingService $mappingService
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

        LoggerService::info(self::class.' - Storing savings CQF renewal quote');

        $policyExpiryDate = Carbon::parse($quote->policy_expiry_date);
        $policyStartDate = $policyExpiryDate->copy()->addDays(1);
        $newPolicyExpiryDate = $policyStartDate->copy()->addDays($renewalDaysThreshold);

        $quoteUuid = $this->mappingService->generateUUID();
        if ($quoteUuid === null) {
            LoggerService::error(self::class.' - Failed to generate UUID for savings renewal quote');

            return null;
        }

        $quoteData = $this->mappingService->mapRenewalQuote($quote, $renewalsUploadLeads, $quoteUuid);
        $quoteData['policy_start_date'] = $policyStartDate;
        $quoteData['policy_expiry_date'] = $newPolicyExpiryDate;

        $newQuote = PersonalQuote::create($quoteData);

        if ($newQuote) {
            $newQuote->quoteDetail()->create([]);
            $this->copySavingsQuoteDetail($newQuote, $quote);
            app(EmbeddedProductRepository::class)->saveEmbeddedTransaction($newQuote, QuoteTypeId::Savings);

            LoggerService::info(self::class.' - Savings CQF renewal quote created successfully', [
                'previous_quote_uuid' => $quote->uuid,
                'new_quote_uuid' => $newQuote->uuid,
            ]);
        }

        return $newQuote;
    }

    protected function copySavingsQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldSavingsQuote = $oldQuote->savingsQuote;

        if ($oldSavingsQuote === null) {
            LoggerService::info(self::class.' - No savings quote detail found for old quote');

            return;
        }

        $data = $this->copyableAttributes($oldSavingsQuote->getAttributes(), $newQuote->id);
        SavingsQuote::create($data);

        LoggerService::info(self::class.' - Savings quote detail copied for renewal quote');
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
