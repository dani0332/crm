<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\BikeQuote;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BikeCQFQuoteStorageService extends BaseCQFQuoteStorageService
{
    public function __construct(
        BikeCQFQuoteMappingService $mappingService
    ) {
        parent::__construct($mappingService);
    }

    public function storeRenewalQuote(
        Model $quote,
        RenewalsUploadLeads $renewalsUploadLeads,
        int $renewalDaysThreshold,
        array &$epCodes = []
    ): ?Model {
        if ($quote instanceof CarQuote) {
            return $this->storeRenewalQuoteFromCarQuote($quote, $renewalsUploadLeads, $renewalDaysThreshold, $epCodes);
        }

        if ($quote instanceof PersonalQuote) {
            return parent::storeRenewalQuote($quote, $renewalsUploadLeads, $renewalDaysThreshold, $epCodes);
        }

        return null;
    }

    protected function getLobName(): string
    {
        return 'bike';
    }

    protected function getQuoteTypeId(): QuoteTypeId
    {
        return QuoteTypeId::Bike;
    }

    protected function copyLobQuoteDetail(PersonalQuote $newQuote, Model $oldQuote): void
    {
        if (! $oldQuote instanceof PersonalQuote) {
            return;
        }
        $this->copyBikeQuoteDetail($newQuote, $oldQuote);
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

        [$policyStartDate, $newPolicyExpiryDate] = $this->computePolicyDates($quote, $renewalDaysThreshold);

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
}
