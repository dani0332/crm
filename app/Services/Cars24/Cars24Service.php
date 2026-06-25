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
            return ['vehicleDriverDetail' => $vehicleDriverDetail, 'mappingValues' => []];
        }

        $providerCodeColumn = match ($paymentDetails->insuranceProvider->code) {
            InsuranceProvidersEnum::AXA => 'axa_code',
            default => null,
        };

        if (! $providerCodeColumn) {
            return ['vehicleDriverDetail' => $vehicleDriverDetail, 'mappingValues' => []];
        }

        $clonedVehicleDriverDetail = clone $vehicleDriverDetail;
        $lookupMappings = $this->buildLookupMappings($vehicleDriverDetail);
        $mappings = $this->fetchMappings($lookupMappings);
        $mappingValues = [];

        foreach ($lookupMappings as $config) {
            if ($config['code'] !== null && $config['code'] !== '') {
                $mapping = $mappings->get($config['key'].'-'.$config['code']);
                if ($mapping) {
                    $clonedVehicleDriverDetail->{$config['field']} = $mapping->{$providerCodeColumn};
                    $mappingValues[$config['key'].'-'.$config['code']] = $mapping->value;
                }
            }
        }

        return ['vehicleDriverDetail' => $clonedVehicleDriverDetail, 'mappingValues' => $mappingValues];
    }

    private function buildLookupMappings($vehicleDriverDetail): array
    {
        return [
            ['key' => 'rta-transaction-type', 'code' => $vehicleDriverDetail->rta_transaction_type, 'field' => 'rta_transaction_type'],
            ['key' => 'rta-plate-category', 'code' => $vehicleDriverDetail->rta_plate_category, 'field' => 'rta_plate_category'],
            ['key' => 'vehicle-color', 'code' => $vehicleDriverDetail->vehicle_color, 'field' => 'vehicle_color'],
            ['key' => 'vehicle-color', 'code' => $vehicleDriverDetail->vehicle_plate_color, 'field' => 'vehicle_plate_color'],
            ['key' => 'bank-name', 'code' => $vehicleDriverDetail->bank_name, 'field' => 'bank_name'],
        ];
    }

    private function fetchMappings(array $lookupMappings)
    {
        $hasValidCodes = collect($lookupMappings)
            ->contains(fn ($config) => $config['code'] !== null && $config['code'] !== '');

        if (! $hasValidCodes) {
            return collect();
        }

        return InsurancePartnerMapping::active()
            ->where(function ($query) use ($lookupMappings) {
                foreach ($lookupMappings as $config) {
                    if ($config['code'] !== null && $config['code'] !== '') {
                        $query->orWhere(function ($q) use ($config) {
                            $q->where('key', $config['key'])->where('code', $config['code']);
                        });
                    }
                }
            })
            ->get()
            ->mapWithKeys(fn ($mapping) => [$mapping->key.'-'.$mapping->code => $mapping]);
    }
}
