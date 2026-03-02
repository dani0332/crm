<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Models\YachtQuote;
use App\Models\YachtQuoteRequestDetail;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\Contracts\CQFQuoteStorageInterface;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class YachtCQFQuoteStorageService implements CQFQuoteStorageInterface
{
    public function __construct(
        protected YachtCQFQuoteMappingService $mappingService
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

        LoggerService::info(self::class.' - Storing yacht CQF renewal quote');

        $policyExpiryDate = Carbon::parse($quote->policy_expiry_date);
        $policyStartDate = $policyExpiryDate->copy()->addDays(1);
        $newPolicyExpiryDate = $policyStartDate->copy()->addDays($renewalDaysThreshold);

        $quoteUuid = $this->mappingService->generateUUID();
        if ($quoteUuid === null) {
            LoggerService::error(self::class.' - Failed to generate UUID for yacht renewal quote');

            return null;
        }

        $quoteData = $this->mappingService->mapRenewalQuote($quote, $renewalsUploadLeads, $quoteUuid);
        $quoteData['policy_start_date'] = $policyStartDate;
        $quoteData['policy_expiry_date'] = $newPolicyExpiryDate;

        return DB::transaction(function () use ($quoteData, $quote) {
            $newQuote = PersonalQuote::create($quoteData);
            $newQuote->quoteDetail()->create([]);
            $this->copyYachtQuoteDetail($newQuote, $quote);
            app(EmbeddedProductRepository::class)->saveEmbeddedTransaction($newQuote, QuoteTypeId::Yacht);

            LoggerService::info(self::class.' - Yacht CQF renewal quote created successfully', [
                'previous_quote_uuid' => $quote->uuid,
                'new_quote_uuid' => $newQuote->uuid,
            ]);

            return $newQuote;
        });
    }

    protected function copyYachtQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldYachtQuote = $oldQuote->yachtQuote;

        if ($oldYachtQuote === null) {
            LoggerService::info(self::class.' - No yacht quote detail found for old quote');

            return;
        }

        $data = $this->copyableAttributes($oldYachtQuote->getAttributes(), $newQuote->id, $newQuote->uuid, $newQuote->code);
        $newYachtQuote = YachtQuote::create($data);

        if ($oldYachtQuote->yachtQuoteRequestDetail) {
            $detailAttrs = $oldYachtQuote->yachtQuoteRequestDetail->getAttributes();
            unset($detailAttrs['id'], $detailAttrs['yacht_quote_request_id'], $detailAttrs['created_at'], $detailAttrs['updated_at']);
            $detailAttrs['yacht_quote_request_id'] = $newYachtQuote->id;
            YachtQuoteRequestDetail::create($detailAttrs);
        }

        LoggerService::info(self::class.' - Yacht quote detail copied for renewal quote');
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
