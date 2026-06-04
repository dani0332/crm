<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\CarTypeOfInsuranceIdEnum;
use App\Enums\CustomerTypeEnum;
use App\Enums\EmbeddedProductEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\BikeQuote;
use App\Models\BikeQuoteRequestDetail;
use App\Models\CarQuote;
use App\Models\CustomerInsured;
use App\Models\Insured;
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
        $oldPersonalQuote = PersonalQuote::with('latestInsured')->where('uuid', $quote->uuid)->first();
        $quoteData['previous_quote_id'] = $oldPersonalQuote?->id;

        return DB::transaction(function () use ($quoteData, $quote, $oldPersonalQuote, &$epCodes) {
            $newQuote = PersonalQuote::create($quoteData);
            $newQuote->quoteDetail()->create([]);
            $this->copyCarQuoteToBikeQuoteDetail($newQuote, $quote);
            if ($this->shouldCopyInsured()) {
                $this->copyCarQuoteInsuredToRenewalQuote($newQuote, $quote, $oldPersonalQuote);
            }
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
     * Copy insured name to the new renewal quote.
     * Prefers the latestInsured from the linked PersonalQuote (when it exists); falls back to
     * first_name/last_name on the CarQuote itself for quotes that were never stored with an insured record.
     */
    private function copyCarQuoteInsuredToRenewalQuote(PersonalQuote $newQuote, CarQuote $carQuote, ?PersonalQuote $oldPersonalQuote): void
    {
        if ($oldPersonalQuote?->latestInsured !== null) {
            $this->copyInsuredToRenewalQuote($newQuote, $oldPersonalQuote);

            return;
        }

        if (blank($carQuote->first_name) && blank($carQuote->last_name)) {
            return;
        }

        $newInsured = Insured::create([
            'customer_type' => CustomerTypeEnum::Individual,
            'first_name' => $carQuote->first_name,
            'last_name' => $carQuote->last_name,
        ]);

        CustomerInsured::create([
            'quote_type_id' => $this->getQuoteTypeId(),
            'quote_request_id' => $newQuote->id,
            'insured_id' => $newInsured->id,
            'customer_id' => $newQuote->customer_id,
            'is_active' => true,
        ]);
    }

    /**
     * Copy CarQuote (Bike vehicle type) detail to new BikeQuote. FR: Car Make → Bike Make, Car Model → Bike Model, etc.
     */
    protected function copyCarQuoteToBikeQuoteDetail(PersonalQuote $newQuote, CarQuote $carQuote): void
    {
        $data = $this->mapCarQuoteToBikeRenewalDetail($carQuote, $newQuote);
        $data['insurance_type_id'] = $this->carCQFQuoteMappingService->getCarTypeInsuranceId($carQuote)
            ?? $data['insurance_type_id'];
        $data['current_insurance_status'] = $this->resolveCurrentInsuranceStatus(
            $data['insurance_type_id'],
            $carQuote->current_insurance_status
        );
        $data = array_intersect_key($data, array_flip($this->getBikeQuoteColumns()));
        BikeQuote::create($data);

        LoggerService::info(self::class.' - Bike quote detail copied from car quote for renewal');
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapCarQuoteToBikeRenewalDetail(CarQuote $carQuote, PersonalQuote $newQuote): array
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
            'renewal_batch_id' => null,
            'previous_quote_policy_number' => $newQuote->previous_quote_policy_number,
            'previous_quote_policy_premium' => $newQuote->previous_quote_policy_premium,
            'previous_quote_policy_commission' => $newQuote->previous_quote_policy_commission,
            'previous_advisor_id' => $newQuote->previous_advisor_id,
            'previous_policy_start_date' => $this->formatPolicyDate($newQuote->previous_policy_start_date),
            'previous_policy_expiry_date' => $this->formatPolicyDate($newQuote->previous_policy_expiry_date),
            'transaction_approved_at' => null,
            'currently_insured_with' => $newQuote->currentlyInsuredWith?->text,
            // --- from old CarQuote ---
            'previous_quote_id' => $carQuote->id,
            'first_name' => $carQuote->first_name,
            'last_name' => $carQuote->last_name,
            'email' => $carQuote->email,
            'mobile_no' => $carQuote->mobile_no,
            'gender' => $carQuote->gender,
            'dob' => $carQuote->dob,
            // Columns below collide with same-named hasOne relationships on CarQuote; read raw to avoid attribute→relation recursion when the column isn't loaded.
            'customer_id' => $carQuote->getRawOriginal('customer_id'),
            'nationality_id' => $carQuote->getRawOriginal('nationality_id'),
            'bike_company_to_insure' => null,
            'year_of_manufacture' => $carQuote->year_of_manufacture,
            'make_id' => $carQuote->getRawOriginal('car_make_id'),
            'model_id' => $carQuote->getRawOriginal('car_model_id'),
            'model_detail_id' => $carQuote->car_model_detail_id,
            'cubic_capacity' => null,
            'emirate_of_registration_id' => $carQuote->getRawOriginal('emirate_of_registration_id'),
            'chassis_number' => $carQuote->carQuoteRequestDetail?->chassis_number,
            'vehicle_type_id' => $carQuote->vehicle_type_id,
            'seat_capacity' => $carQuote->seat_capacity,
            'year_of_first_registration' => $carQuote->year_of_first_registration,
            'uae_license_held_for_id' => $this->incrementLicenseHeldForId($carQuote->getRawOriginal('uae_license_held_for_id')),
            'back_home_license_held_for_id' => $this->incrementLicenseHeldForId($carQuote->back_home_license_held_for_id, backHome: true),
            // --- reset on renewal ---
            'bike_value' => null,
            'claim_history_id' => null,
            'bike_value_tier' => null,
            'has_ncd_supporting_documents' => null,
            // insurance_type_id is resolved by the caller using the already-loaded CarQuote to avoid an N+1.
            'insurance_type_id' => $carQuote->getRawOriginal('car_type_insurance_id'),
        ];
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

    private function resolveCurrentInsuranceStatus(?int $insuranceTypeId, ?string $fallback = null): ?string
    {
        return match ((int) $insuranceTypeId) {
            CarTypeOfInsuranceIdEnum::Comprehensive => 'ACTIVE_COMP',
            CarTypeOfInsuranceIdEnum::ThirdPartyOnly => 'ACTIVE_TPL',
            default => $fallback,
        };
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
        $data['current_insurance_status'] = $this->resolveCurrentInsuranceStatus(
            $data['insurance_type_id'],
            $oldBikeQuote->current_insurance_status
        );
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
            'renewal_batch_id' => null,
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
