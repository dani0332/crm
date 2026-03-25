<?php

declare(strict_types=1);

namespace App\Services\Cars24;

use App\Enums\InsuranceProvidersEnum;
use App\Models\InsurancePartner;
use App\Models\InsurancePartnerMapping;
use App\Services\Logger\LoggerService;

class Cars24Service
{
    public function getLookups(array $requireLookups, string $leadSource): array
    {
        $isPartnerActive = InsurancePartner::active()->forCode($leadSource)->first();

        if (! $isPartnerActive) {
            LoggerService::info('Additional Vehicle and Driver Details are not enabled for the partner', [
                'partner' => $leadSource,
            ]);

            return [];
        }

        return InsurancePartnerMapping::active()->whereIn('key', $requireLookups)
            ->get()
            ->groupBy('key')
            ->mapWithKeys(fn ($item, $key) => [str_replace('-', '_', $key) => $item])
            ->toArray();
    }

    public function mapCars24LookupsToProviderCodes($vehicleDriverDetail, $paymentDetails)
    {
        if (! $vehicleDriverDetail || ! $paymentDetails?->insuranceProvider?->code) {
            return $vehicleDriverDetail;
        }

        $providerCodeColumn = match ($paymentDetails->insuranceProvider->code) {
            InsuranceProvidersEnum::AXA => 'axa_code',
            default => null,
        };

        if (! $providerCodeColumn) {
            return $vehicleDriverDetail;
        }

        $mappings = InsurancePartnerMapping::active()
            ->whereIn('code', array_filter([
                $vehicleDriverDetail->rta_transaction_type,
                $vehicleDriverDetail->rta_plate_category,
                $vehicleDriverDetail->vehicle_color,
                $vehicleDriverDetail->vehicle_plate_color,
                $vehicleDriverDetail->bank_name,
            ]))
            ->get()
            ->keyBy('code');

        $vehicleDriverDetail->rta_transaction_type = $mappings->get($vehicleDriverDetail->rta_transaction_type)?->{$providerCodeColumn} ?? $vehicleDriverDetail->rta_transaction_type;
        $vehicleDriverDetail->rta_plate_category = $mappings->get($vehicleDriverDetail->rta_plate_category)?->{$providerCodeColumn} ?? $vehicleDriverDetail->rta_plate_category;
        $vehicleDriverDetail->vehicle_color = $mappings->get($vehicleDriverDetail->vehicle_color)?->{$providerCodeColumn} ?? $vehicleDriverDetail->vehicle_color;
        $vehicleDriverDetail->vehicle_plate_color = $mappings->get($vehicleDriverDetail->vehicle_plate_color)?->{$providerCodeColumn} ?? $vehicleDriverDetail->vehicle_plate_color;
        $vehicleDriverDetail->bank_name = $mappings->get($vehicleDriverDetail->bank_name)?->{$providerCodeColumn} ?? $vehicleDriverDetail->bank_name;

        return $vehicleDriverDetail;
    }
}
