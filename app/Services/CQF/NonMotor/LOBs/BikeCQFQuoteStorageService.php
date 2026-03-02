<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\BikeQuote;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\Contracts\CQFQuoteStorageInterface;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BikeCQFQuoteStorageService implements CQFQuoteStorageInterface
{
    public function __construct(
        protected BikeCQFQuoteMappingService $mappingService
    ) {}

    public function storeRenewalQuote(
        Model $quote,
        RenewalsUploadLeads $renewalsUploadLeads,
        int $renewalDaysThreshold,
        array &$epCodes = []
    ): ?Model {
        if ($quote instanceof CarQuote) {
            return $this->storeRenewalQuoteFromCarQuote($quote, $renewalsUploadLeads, $renewalDaysThreshold, $epCodes);
        }

        if (! $quote instanceof PersonalQuote) {
            return null;
        }

        LoggerService::info(self::class.' - Storing bike CQF renewal quote');

        $policyExpiryDate = Carbon::parse($quote->policy_expiry_date);
        $policyStartDate = $policyExpiryDate->copy()->addDays(1);
        $newPolicyExpiryDate = $policyStartDate->copy()->addDays($renewalDaysThreshold);

        LoggerService::info(self::class.' - Policy details', [
            'policyExpiryDate' => $policyExpiryDate,
            'policyStartDate' => $policyStartDate,
            'newPolicyExpiryDate' => $newPolicyExpiryDate,
        ]);

        $quoteUuid = $this->mappingService->generateUUID();
        if ($quoteUuid === null) {
            LoggerService::error(self::class.' - Failed to generate UUID for bike renewal quote');

            return null;
        }

        $quoteData = $this->mappingService->mapRenewalQuote($quote, $renewalsUploadLeads, $quoteUuid);
        $quoteData['policy_start_date'] = $policyStartDate;
        $quoteData['policy_expiry_date'] = $newPolicyExpiryDate;

        return DB::transaction(function () use ($quoteData, $quote) {
            $newQuote = PersonalQuote::create($quoteData);
            $newQuote->quoteDetail()->create([]);
            $this->copyBikeQuoteDetail($newQuote, $quote);
            app(EmbeddedProductRepository::class)->saveEmbeddedTransaction($newQuote, QuoteTypeId::Bike);

            LoggerService::info(self::class.' - Bike CQF renewal quote created successfully', [
                'previous_quote_uuid' => $quote->uuid,
                'new_quote_uuid' => $newQuote->uuid,
                'previous_quote_id' => $quote->id,
                'new_quote_id' => $newQuote->id,
            ]);

            return $newQuote;
        });
    }

    /**
     * Create Bike renewal lead from CarQuote (car_quote_request where vehicle_type_id is Bike).
     *
     * @param  array<int, string>  $epCodes
     */
    protected function storeRenewalQuoteFromCarQuote(
        CarQuote $quote,
        RenewalsUploadLeads $renewalsUploadLeads,
        int $renewalDaysThreshold,
        array &$epCodes = []
    ): ?Model {
        LoggerService::info(self::class.' - Storing bike CQF renewal quote from car_quote_request');

        $policyExpiryDate = Carbon::parse($quote->policy_expiry_date);
        $policyStartDate = $policyExpiryDate->copy()->addDays(1);
        $newPolicyExpiryDate = $policyStartDate->copy()->addDays($renewalDaysThreshold);

        $quoteUuid = $this->mappingService->generateUUID();
        if ($quoteUuid === null) {
            LoggerService::error(self::class.' - Failed to generate UUID for bike renewal quote (from car)');

            return null;
        }

        $quoteData = $this->mappingService->mapRenewalQuote($quote, $renewalsUploadLeads, $quoteUuid);
        $quoteData['policy_start_date'] = $policyStartDate;
        $quoteData['policy_expiry_date'] = $newPolicyExpiryDate;

        return DB::transaction(function () use ($quoteData, $quote) {
            $newQuote = PersonalQuote::create($quoteData);
            $newQuote->quoteDetail()->create([]);
            $this->copyCarQuoteToBikeQuoteDetail($newQuote, $quote);
            app(EmbeddedProductRepository::class)->saveEmbeddedTransaction($newQuote, QuoteTypeId::Bike);

            LoggerService::info(self::class.' - Bike CQF renewal quote created from car quote successfully', [
                'previous_car_quote_id' => $quote->id,
                'new_quote_uuid' => $newQuote->uuid,
                'new_quote_id' => $newQuote->id,
            ]);

            return $newQuote;
        });
    }

    /**
     * Copy CarQuote (Bike vehicle type) detail to new BikeQuote. FR: Car Make → Bike Make, Car Model → Bike Model, etc.
     */
    protected function copyCarQuoteToBikeQuoteDetail(PersonalQuote $newQuote, CarQuote $carQuote): void
    {
        $data = $this->copyableAttributes($carQuote->getAttributes(), $newQuote->id, $newQuote->uuid, $newQuote->code);
        $data['personal_quote_id'] = $newQuote->id;
        BikeQuote::create($data);

        LoggerService::info(self::class.' - Bike quote detail copied from car quote for renewal');
    }

    protected function copyBikeQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldBikeQuote = $oldQuote->bikeQuote;

        if ($oldBikeQuote === null) {
            LoggerService::info(self::class.' - No bike quote detail found for old quote');

            return;
        }

        $data = $this->copyableAttributes($oldBikeQuote->getAttributes(), $newQuote->id, $newQuote->uuid, $newQuote->code);
        BikeQuote::create($data);

        LoggerService::info(self::class.' - Bike quote detail copied for renewal quote');
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
