<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\EmbeddedProductEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\BikeQuote;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Models\UAELicenseHeldFor;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BikeCQFQuoteStorageService extends BaseCQFQuoteStorageService
{
    private ?array $bikeQuoteColumns = null;

    public function __construct(
        BikeCQFQuoteMappingService $mappingService
    ) {
        parent::__construct($mappingService);
    }

    private function getBikeQuoteColumns(): array
    {
        return $this->bikeQuoteColumns ??= Schema::getColumnListing((new BikeQuote)->getTable());
    }

    public function storeRenewalQuote(
        Model $quote,
        RenewalsUploadLeads $renewalsUploadLeads,
        array &$epCodes = []
    ): ?Model {
        if ($quote instanceof CarQuote) {
            return $this->storeRenewalQuoteFromCarQuote($quote, $renewalsUploadLeads, $epCodes);
        }

        if ($quote instanceof PersonalQuote) {
            return parent::storeRenewalQuote($quote, $renewalsUploadLeads, $epCodes);
        }

        return null;
    }

    protected function getLobName(): string
    {
        return 'bike';
    }

    protected function getQuoteTypeId(): int
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
     * Collect RDX embedded product codes from old quote (PersonalQuote or CarQuote) when selected + captured; same process as Car MDX.
     *
     * @param  array<int, string>  $epCodes
     */
    protected function collectEmbeddedProductCodes(Model $oldQuote, PersonalQuote $newQuote, array &$epCodes): void
    {
        if (! method_exists($oldQuote, 'embeddedTransactions')) {
            return;
        }

        $oldQuote->loadMissing('embeddedTransactions');

        foreach ($oldQuote->embeddedTransactions ?? [] as $embeddedTransaction) {
            $isSelected = $embeddedTransaction->is_selected == 1;
            $isPaymentCaptured = $embeddedTransaction->payment_status_id === PaymentStatusEnum::CAPTURED;
            $isRdxProduct = str_contains($embeddedTransaction->code ?? '', EmbeddedProductEnum::RDX);

            if ($isSelected && $isPaymentCaptured && $isRdxProduct) {
                $epCodes[] = EmbeddedProductEnum::RDX.'-'.$newQuote->code;
                LoggerService::info(self::class.' - Embedded transaction (RDX) found for bike renewal quote', [
                    'embeddedTransaction' => [
                        'code' => $embeddedTransaction->code,
                        'is_selected' => $embeddedTransaction->is_selected,
                        'payment_status_id' => $embeddedTransaction->payment_status_id,
                        'previous_quote_uuid' => $oldQuote->uuid ?? null,
                        'new_quote_uuid' => $newQuote->uuid,
                    ],
                ]);
            }
        }
    }

    /**
     * Create Bike renewal lead from CarQuote (car_quote_request where vehicle_type_id is Bike).
     *
     * @param  array<int, string>  $epCodes
     */
    protected function storeRenewalQuoteFromCarQuote(
        CarQuote $quote,
        RenewalsUploadLeads $renewalsUploadLeads,
        array &$epCodes = []
    ): ?Model {
        LoggerService::info(self::class.' - Storing bike CQF renewal quote from car_quote_request');

        $quoteUuid = $this->mappingService->generateUUID();
        if ($quoteUuid === null) {
            LoggerService::error(self::class.' - Failed to generate UUID for bike renewal quote (from car)');

            return null;
        }

        $quoteData = $this->mappingService->mapRenewalQuote($quote, $renewalsUploadLeads, $quoteUuid);

        return DB::transaction(function () use ($quoteData, $quote, &$epCodes) {
            $newQuote = PersonalQuote::create($quoteData);
            $newQuote->quoteDetail()->create([]);
            $this->copyCarQuoteToBikeQuoteDetail($newQuote, $quote);
            app(EmbeddedProductRepository::class)->saveEmbeddedTransaction($newQuote, QuoteTypeId::Bike);
            $this->collectEmbeddedProductCodes($quote, $newQuote, $epCodes);

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
        $data = $this->alignCopiedLobRowWithRenewalPersonalQuote($data, $newQuote); // no old BikeQuote to pass — migrating from CarQuote
        $data = $this->remapCarColumnsToBike($data);
        $data['bike_value'] = null;
        $data['claim_history_id'] = null;
        $data['has_ncd_supporting_documents'] = null;
        $data = array_intersect_key($data, array_flip($this->getBikeQuoteColumns()));
        BikeQuote::create($data);

        LoggerService::info(self::class.' - Bike quote detail copied from car quote for renewal');
    }

    /**
     * Return the next active UAE license held-for ID (one step up the ordered list).
     * Caps at the highest active entry ("5 years and above") — returns current ID when already at the top.
     */
    private function incrementLicenseHeldForId(?int $currentId): ?int
    {
        if ($currentId === null) {
            return null;
        }

        $nextId = UAELicenseHeldFor::withActive()
            ->where('id', '>', $currentId)
            ->orderBy('id')
            ->value('id');

        return $nextId ?? $currentId;
    }

    /**
     * Rename CarQuote-specific columns to their BikeQuote equivalents before mass-assigning.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function remapCarColumnsToBike(array $data): array
    {
        foreach ([
            'car_make_id' => 'make_id',
            'car_model_id' => 'model_id',
            'car_model_detail_id' => 'model_detail_id',
            'car_value' => 'bike_value',
            'car_value_tier' => 'bike_value_tier',
            'car_type_insurance_id' => 'insurance_type_id',
        ] as $from => $to) {
            if (array_key_exists($from, $data)) {
                $data[$to] = $data[$from];
                unset($data[$from]);
            }
        }

        return $data;
    }

    protected function copyBikeQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldBikeQuote = $oldQuote->bikeQuote;

        if ($oldBikeQuote === null) {
            LoggerService::info(self::class.' - No bike quote detail found for old quote');

            return;
        }

        $data = $this->copyableAttributes($oldBikeQuote->getAttributes(), $newQuote->id, $newQuote->uuid, $newQuote->code);
        $data = $this->alignCopiedLobRowWithRenewalPersonalQuote($data, $newQuote, $oldBikeQuote);
        $data['bike_value'] = null;
        $data['claim_history_id'] = null;
        $data['has_ncd_supporting_documents'] = null;
        $data['uae_license_held_for_id'] = $this->incrementLicenseHeldForId($oldBikeQuote->uae_license_held_for_id);
        $data['back_home_license_held_for_id'] = $this->incrementLicenseHeldForId($oldBikeQuote->back_home_license_held_for_id);
        BikeQuote::create($data);

        LoggerService::info(self::class.' - Bike quote detail copied for renewal quote');
    }
}
