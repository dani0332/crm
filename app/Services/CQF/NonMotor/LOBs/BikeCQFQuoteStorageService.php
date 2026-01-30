<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\BikeQuote;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CapiRequestService;
use App\Services\CQF\Contracts\CQFQuoteStorageInterface;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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

        $quoteUuid = $this->generateUUID();
        if ($quoteUuid === null) {
            LoggerService::error(self::class.' - Failed to generate UUID for bike renewal quote');

            return null;
        }

        $quoteData = $this->mappingService->mapRenewalQuote($quote, $renewalsUploadLeads, $quoteUuid);
        $quoteData['policy_start_date'] = $policyStartDate;
        $quoteData['policy_expiry_date'] = $newPolicyExpiryDate;

        $newQuote = PersonalQuote::create($quoteData);

        if ($newQuote) {
            $newQuote->quoteDetail()->create([]);

            $this->copyBikeQuoteDetail($newQuote, $quote);

            app(EmbeddedProductRepository::class)->saveEmbeddedTransaction($newQuote, QuoteTypeId::Bike);

            LoggerService::info(self::class.' - Bike CQF renewal quote created successfully', [
                'previous_quote_uuid' => $quote->uuid,
                'new_quote_uuid' => $newQuote->uuid,
                'previous_quote_id' => $quote->id,
                'new_quote_id' => $newQuote->id,
            ]);
        }

        return $newQuote;
    }

    public function generateUUID(): ?string
    {
        if (checkPersonalQuotes(QuoteTypes::BIKE->value)) {
            $response = app(CapiRequestService::class)->getPersonalQuoteUUID(QuoteTypes::BIKE->id());
        } else {
            $response = app(CapiRequestService::class)->getUUID(QuoteTypes::BIKE->id());
        }

        if ($response) {
            return $response->uuid ?? null;
        }

        return null;
    }

    protected function copyBikeQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldBikeQuote = $oldQuote->bikeQuote;

        if ($oldBikeQuote === null) {
            LoggerService::info(self::class.' - No bike quote detail found for old quote');

            return;
        }

        $bikeQuoteData = [
            'personal_quote_id' => $newQuote->id,
            'bike_company_to_insure' => $oldBikeQuote->bike_company_to_insure,
            'year_of_manufacture' => $oldBikeQuote->year_of_manufacture,
            'uae_license_held_for_id' => $oldBikeQuote->uae_license_held_for_id,
            'bike_value_tier' => $oldBikeQuote->bike_value_tier,
            'make_id' => $oldBikeQuote->make_id,
            'model_id' => $oldBikeQuote->model_id,
            'currently_insured_with' => $oldBikeQuote->currently_insured_with,
            'cubic_capacity' => $oldBikeQuote->cubic_capacity,
            'emirate_of_registration_id' => $oldBikeQuote->emirate_of_registration_id,
            'claim_history_id' => $oldBikeQuote->claim_history_id,
            'bike_value' => $oldBikeQuote->bike_value,
        ];

        if (isset($oldBikeQuote->chassis_number)) {
            $bikeQuoteData['chassis_number'] = $oldBikeQuote->chassis_number;
        }

        $newBikeQuote = BikeQuote::create($bikeQuoteData);

        if ($oldBikeQuote->bikeQuoteRequestDetail) {
            $newBikeQuote->bikeQuoteRequestDetail()->create([
                'chassis_number' => $oldBikeQuote->bikeQuoteRequestDetail->chassis_number ?? null,
            ]);
        }

        LoggerService::info(self::class.' - Bike quote detail copied for renewal quote');
    }
}
