<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\EmbeddedProductEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\BikeQuote;
use App\Models\BikeQuoteRequestDetail;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Models\UAELicenseHeldFor;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\CarCQFQuoteMappingService;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BikeCQFQuoteStorageService extends BaseCQFQuoteStorageService
{
    private ?array $bikeQuoteColumns = null;

    /** @var Collection<int, UAELicenseHeldFor>|null */
    private ?Collection $uaeLicenseRows = null;

    public function __construct(
        BikeCQFQuoteMappingService $mappingService,
        EmbeddedProductRepository $embeddedProductRepository,
        private CarCQFQuoteMappingService $carCQFQuoteMappingService,
    ) {
        parent::__construct($mappingService, $embeddedProductRepository);
    }

    private function getUaeLicenseRows(): Collection
    {
        return $this->uaeLicenseRows ??= UAELicenseHeldFor::orderBy('id')->get();
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
        $quoteData['previous_quote_id'] = PersonalQuote::where('uuid', $quote->uuid)->value('id');

        return DB::transaction(function () use ($quoteData, $quote, &$epCodes) {
            $newQuote = PersonalQuote::create($quoteData);
            $newQuote->quoteDetail()->create([]);
            $this->copyCarQuoteToBikeQuoteDetail($newQuote, $quote);
            $this->embeddedProductRepository->saveEmbeddedTransaction($newQuote, QuoteTypeId::Bike);
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
        $data = $this->alignCopiedLobRowWithRenewalPersonalQuote($data, $newQuote); // no old BikeQuote to pass — migrating from CarQuote
        $data = $this->remapCarColumnsToBike($data);
        $data['insurance_type_id'] = $this->carCQFQuoteMappingService->getCarTypeInsuranceId($carQuote)
            ?? $data['insurance_type_id'] ?? null;
        $data['bike_value'] = null;
        $data['bike_value_tier'] = null;
        $data['claim_history_id'] = null;
        $data['has_ncd_supporting_documents'] = null;
        $data['uae_license_held_for_id'] = $this->incrementLicenseHeldForId($data['uae_license_held_for_id'] ?? null);
        $data['back_home_license_held_for_id'] = $this->incrementLicenseHeldForId($data['back_home_license_held_for_id'] ?? null, backHome: true);
        $data['chassis_number'] = $carQuote->carQuoteRequestDetail?->chassis_number;
        $data = array_intersect_key($data, array_flip($this->getBikeQuoteColumns()));
        BikeQuote::create($data);

        LoggerService::info(self::class.' - Bike quote detail copied from car quote for renewal');
    }

    /**
     * Return the next license held-for ID (one step up the ordered list).
     * UAE caps at ID 7 ("5 years and above"); back-home caps at ID 22 ("20 years+").
     */
    private function incrementLicenseHeldForId(?int $currentId, bool $backHome = false): ?int
    {
        if ($currentId === null) {
            return null;
        }

        $rows = $this->getUaeLicenseRows();

        if ($rows->firstWhere('id', $currentId) === null) {
            return $currentId;
        }

        $activeColumn = $backHome ? 'is_back_home_license_active' : 'is_active';
        $nextId = $rows
            ->where($activeColumn, 1)
            ->where('id', '>', $currentId)
            ->first()?->id;

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

        $oldQuote->loadMissing('carPlan');
        $data = $this->mapLobRenewalDetail($oldBikeQuote, $newQuote);
        $data['insurance_type_id'] = $this->carCQFQuoteMappingService->getCarTypeInsuranceId($oldQuote)
            ?? $oldBikeQuote->insurance_type_id;
        $newBikeQuote = BikeQuote::create($data);

        if ($oldBikeQuote->bikeQuoteRequestDetail) {
            BikeQuoteRequestDetail::create(['bike_quote_request_id' => $newBikeQuote->id]);
        }

        LoggerService::info(self::class.' - Bike quote detail copied for renewal quote');
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapLobRenewalDetail(BikeQuote $oldLob, PersonalQuote $newQuote): array
    {
        return [
            // --- from new PersonalQuote ---
            'personal_quote_id' => $newQuote->id,
            'uuid' => $newQuote->uuid,
            'code' => $newQuote->code,
            'source' => $newQuote->source,
            'quote_status_id' => $newQuote->quote_status_id,
            'advisor_id' => null,
            'assignment_type' => null,
            'renewal_batch_id' => $newQuote->renewal_batch_id,
            'previous_quote_policy_number' => $newQuote->previous_quote_policy_number,
            'previous_quote_policy_premium' => $newQuote->previous_quote_policy_premium,
            'previous_quote_policy_commission' => $newQuote->previous_quote_policy_commission,
            'previous_advisor_id' => $newQuote->previous_advisor_id,
            'previous_policy_start_date' => $this->formatPolicyDate($newQuote->previous_policy_start_date),
            'previous_policy_expiry_date' => $this->formatPolicyDate($newQuote->previous_policy_expiry_date),
            'transaction_approved_at' => null,
            'currently_insured_with' => $newQuote->currentlyInsuredWith?->text,
            // --- from old BikeQuote ---
            'previous_quote_id' => $oldLob->id,
            'first_name' => $oldLob->first_name,
            'last_name' => $oldLob->last_name,
            'email' => $oldLob->email,
            'mobile_no' => $oldLob->mobile_no,
            'gender' => $oldLob->gender,
            'dob' => $oldLob->dob,
            'customer_id' => $oldLob->customer_id,
            'nationality_id' => $oldLob->nationality_id,
            'bike_company_to_insure' => $oldLob->bike_company_to_insure,
            'year_of_manufacture' => $oldLob->year_of_manufacture,
            'make_id' => $oldLob->make_id,
            'model_id' => $oldLob->model_id,
            'model_detail_id' => $oldLob->model_detail_id,
            'cubic_capacity' => $oldLob->cubic_capacity,
            'emirate_of_registration_id' => $oldLob->emirate_of_registration_id,
            'chassis_number' => $oldLob->chassis_number,
            'vehicle_type_id' => $oldLob->vehicle_type_id,
            'seat_capacity' => $oldLob->seat_capacity,
            'year_of_first_registration' => $oldLob->year_of_first_registration,
            'bike_type_insurance_id' => $oldLob->bike_type_insurance_id,
            'uae_license_held_for_id' => $this->incrementLicenseHeldForId($oldLob->uae_license_held_for_id),
            'back_home_license_held_for_id' => $this->incrementLicenseHeldForId($oldLob->back_home_license_held_for_id, backHome: true),
            // --- reset on renewal ---
            'bike_value' => null,
            'claim_history_id' => null,
            'bike_value_tier' => null,
            'has_ncd_supporting_documents' => null,
            // insurance_type_id is resolved by the caller using the already-loaded PersonalQuote to avoid an N+1.
            'insurance_type_id' => $oldLob->insurance_type_id,
        ];
    }
}
