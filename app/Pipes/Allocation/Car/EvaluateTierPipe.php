<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\CarPlanType;
use App\Enums\CarRegistrationType;
use App\Enums\CarVehicleUse;
use App\Enums\InsuranceProviderEnum;
use App\Enums\TiersEnum;
use App\Enums\TiersIdEnum;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarQuote;
use App\Models\CarQuotePlanDetail;
use App\Models\CommercialKeyword;
use App\Models\InsuranceProvider;
use App\Models\Tier;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Closure;
use Exception;

class EvaluateTierPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $tier = $this->determineTier();

        if (! $tier) {
            LoggerService::info('Tier not found. Skipping allocation.');
            $this->allocationRequest->markAsFailed();
            $this->throw('Tier not found', self::OK);
        }

        // For Tier R, skip allocation
        if ($tier->name == TiersEnum::TIER_R) {
            LoggerService::info(self::class.' - Lead is Tier R. Skipping allocation');
            $this->allocationRequest->markAsFailed();
            $this->throw('Lead is Tier R. Skipping allocation.', self::OK);
        }

        $this->lead->tier_id = $tier->id;
        $this->lead->save();

        $this->allocationRequest->setLead($this->lead);

        $this->allocationRequest->setTier($tier);

        if ($this->allocationRequest->isEvaluateTierOnlyRequest()) {
            $this->stop('Tier evaluated successfully');
        }

        return $next($request);
    }

    private function determineTier()
    {
        $tier = $this->findTier();

        if ($tier) {
            LoggerService::info('Checking if tier update is required');
            $updatedTierId = $this->updateTierBeforeEligibleUserIdentification();

            if (! empty($updatedTierId) && $updatedTierId != $this->lead->tier_id) {
                $this->lead->tier_id = $updatedTierId;
                $this->lead->save();
                $tier = $this->getTier($updatedTierId);
            }
        }

        return $tier;
    }

    private function updateTierBeforeEligibleUserIdentification()
    {
        LoggerService::info("lead payment status is : {$this->lead->payment_status_id} and tier id is : {$this->lead->tier_id} and sic advisor requested is : {$this->lead->sic_advisor_requested}");

        if (($this->lead->isPaymentAuthorizedOrDeclined() || $this->lead->sic_advisor_requested == 1) && $this->lead->tier_id == TiersIdEnum::TIER_R) {
            LoggerService::info('SIC lead payment is made and tier is Tier R');
            $tier = $this->findRenewalLeadTier();
            if (! empty($tier) && $tier->id != $this->lead->tier_id) {
                LoggerService::info('Tier is found and tier name is: '.$tier->name);
                $this->updateLeadTier($tier);

                return $tier->id;
            } else {
                return $this->lead->tier_id;
            }
        }
    }

    private function findRenewalLeadTier(): ?Tier
    {
        $carLead = $this->lead;

        LoggerService::debug(self::class.'::findRenewalLeadTier - Car Lead Info', extra: [
            'car_value' => $carLead->car_value,
            'car_value_tier' => $carLead->car_value_tier,
            'sic_flow_enabled' => $carLead->sic_flow_enabled,
        ]);

        $isSICFlowEnabled = $carLead->sic_flow_enabled;
        $priceValue = $carLead->car_value_tier ?? $carLead->car_value;

        if (empty($priceValue)) {
            return null;
        }

        return Tier::where('is_active', 1)
            ->where('min_price', '<=', $priceValue)
            ->where('max_price', '>=', $priceValue)
            ->where('can_handle_tpl', 0)
            ->where('name', '!=', TiersEnum::TIER_R)
            ->when($isSICFlowEnabled, function ($query) {
                $query->where('name', '!=', TiersEnum::TIER_L);
            })
            ->first();
    }

    private function updateLeadTier($tier): void
    {
        CarQuote::where('id', $this->lead->id)->update([
            'tier_id' => $tier->id,
        ]);

        LoggerService::info("Tier with name : {$tier->name} is assigned");
    }

    private function getTier($tierId)
    {
        return Tier::where('id', $tierId)->first();
    }

    private function findTier(): ?Tier
    {
        if (empty($this->lead->tier_id)) {
            return $this->evaluate();
        }

        return $this->getTier($this->lead->tier_id);
    }

    private function evaluate(): ?Tier
    {
        [$plans, $yearOfManufacture] = $this->getPlansAndYear();

        // Query to get all active tiers.
        $tiersQuery = Tier::where('is_active', 1);

        if ($this->lead->registration_type == CarRegistrationType::COMPANY) {
            $this->getTierBasedOnValue($tiersQuery);
            LoggerService::info(self::class.' - Registration type is company. Calculating tier based on value');

            return $tiersQuery->first();
        }

        // Check if the car's year of manufacture is newer than 15 years.
        if ($this->lead->year_of_manufacture < $yearOfManufacture) {
            // Check if more than one plan is found against the car lead.
            if (count($plans) > 0) {
                LoggerService::info('More than one plan found');
                // Determine the tier based on a value and return the first matching tier.
                LoggerService::info(self::class.'- More than one plan found against Tier based on value is being calculated for the lead');
                $this->getTierBasedOnValue($tiersQuery);

                return $tiersQuery->first();
            } else {
                // Check car value and age to determine the tier.
                if ($this->lead->car_value >= 300000) {
                    info(self::class." - Car value is {$this->lead->car_valu} Tier H is being assigned for the lead");

                    return $tiersQuery->Where('name', TiersEnum::TIER_H)->first();
                }

                LoggerService::debug(self::class.'::findTier - Car Lead Info', extra: [
                    'car_value' => $this->lead->car_value,
                    'car_value_tier' => $this->lead->car_value_tier,
                    'dob' => $this->lead->dob,
                ]);

                try {
                    // Check car value and age to determine the tier.
                    if ($this->lead->car_value >= 300000) {
                        return $tiersQuery->Where('name', TiersEnum::TIER_H)->first();
                    }

                    $userDob = Carbon::createFromFormat('Y-m-d H:i:s', $this->lead->dob);
                    $ageInYears = $userDob->age;

                    if ($this->lead->car_value < 300000 || $ageInYears >= 21) {
                        return $tiersQuery->Where('name', $this->lead->is_ecommerce ? TiersEnum::TIER6_ECOM : TiersEnum::TIER6_NONECOM)->first();
                    }
                } catch (Exception $e) {
                    LoggerService::warning('Error calculating age from DOB', exception: $e);

                    return null;
                }
            }
        } else {
            // Determine the tier based on a value and return the first matching tier.
            LoggerService::info(self::class.' - Tier based on value is being calculated for the lead');

            $this->getTierBasedOnValue($tiersQuery);

            return $tiersQuery->first();
        }

        // Return null if no matching tier is found.
        return null;
    }

    private function getPlansAndYear(): array
    {
        // Retrieve car quote plans with specific conditions.
        $plans = CarQuotePlanDetail::where('quote_uuid', $this->lead->uuid)
            ->where('is_rating_available', true)
            ->where('repair_type', CarPlanType::COMP)
            ->get();

        // Calculate the year of manufacture that is 15 years ago from the current date.
        $yearOfManufacture = now()->subYear(15)->year;

        LoggerService::info(self::class." - yearOfManufacture is: {$yearOfManufacture} and number of plans found are: ".count($plans));

        return [$plans, $yearOfManufacture];
    }

    private function getTierBasedOnValue($tiersQuery): void
    {

        if ($this->lead->car_model_detail_id == null && ! $this->isCommercialLead()) {
            $tiersQuery->where('name', TiersEnum::TIER_L);
        } else {
            if (! $this->isCommercialLead()) {
                $valuations = $this->getValuation($this->lead->car_model_detail_id, $this->lead->year_of_manufacture);
            } else {
                $valuations = [];
                LoggerService::info(self::class.'- Commercial lead. No valuation');
            }

            $axaProvider = InsuranceProvider::where('code', InsuranceProviderEnum::AXA->value)->first();

            $axaValuation = array_filter($valuations, function ($provider) use ($axaProvider) {
                return $provider->providerId == $axaProvider->id;
            });

            $carValue = 0;
            if ($this->lead->registration_type == CarRegistrationType::COMPANY) {
                if ($this->lead->vehicle_use == CarVehicleUse::PRIVATE) {
                    if (! empty($axaValuation)) {
                        $firstAxaValuation = reset($axaValuation); // Get the first element of the array
                        $carValue = $firstAxaValuation->carValue;
                        LoggerService::info(self::class." - Company registration with private use. AXA valuation found. Car value: {$carValue}");
                    }
                } else {
                    $carValue = $this->lead->car_value;
                    LoggerService::info(self::class." - Company registration with non-private use. Car value: {$carValue}");
                }
            } else {
                if (! empty($axaValuation)) {
                    $firstAxaValuation = reset($axaValuation); // Get the first element of the array

                    if (! empty($firstAxaValuation) && property_exists($firstAxaValuation, 'carValue')) {
                        LoggerService::info('car value as per valuation engine for GIG', [
                            'car_value' => $firstAxaValuation->carValue,
                        ]);

                        if ($firstAxaValuation->carValue > 0) {
                            $carValue = $firstAxaValuation->carValue;
                        }
                    }
                }
            }

            LoggerService::info("car value as per valuation engine for GIG is {$carValue}");
            $tiersQuery->where('min_price', '<=', $carValue)->where('max_price', '>=', $carValue);
        }
    }

    private function isCommercialLead()
    {
        $commercialCarMake = CarMake::where('id', $this->lead->car_make_id)
            ->where('is_commercial', true)
            ->select('id')
            ->first();

        $commercialCarModel = CarModel::where('id', $this->lead->car_model_id)
            ->where('is_commercial', true)
            ->select('id')
            ->first();

        if ($commercialCarMake && $commercialCarModel) {
            LoggerService::info(self::class.' - Commercial car make and model found for lead');

            return true;
        }

        $commercialKeywords = CommercialKeyword::select('id', 'name')->get();

        foreach ($commercialKeywords as $keyword) {
            if (str_contains(strtolower(trim($this->lead->full_name)), strtolower(trim($keyword->name)))) {
                LoggerService::info(self::class.' - Commercial keywords found for lead');

                return true;
            }
        }

        return false;
    }
}
