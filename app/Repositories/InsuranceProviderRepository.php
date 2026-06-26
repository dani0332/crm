<?php

namespace App\Repositories;

use App\Models\HealthRatingEligibility;
use App\Models\InslyInsuranceProvider;
use App\Models\InsuranceProvider;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\DB;

class InsuranceProviderRepository extends BaseRepository
{
    public function model()
    {
        return InsuranceProvider::class;
    }

    public function fetchGetList($orderBy = 'sort_order', $order = 'asc')
    {
        return $this->withActive()->orderBy($orderBy, $order)->get();
    }

    public function fetchByQuoteTypeMapping(int $quoteTypeId, ?int $insuranceProviderId = null)
    {
        LoggerService::info('fn:fetchByQuoteTypeMapping - Start - InsuranceProviderRepository', [
            'quote_type_id'         => $quoteTypeId,
            'insurance_provider_id' => $insuranceProviderId,
        ]);

        // Base list: providers mapped for this quote type
        $providers = DB::table('insurance_provider_quote_type')
            ->join(
                'insurance_provider',
                'insurance_provider.id',
                '=',
                'insurance_provider_quote_type.insurance_provider_id'
            )
            ->where('insurance_provider_quote_type.quote_type_id', $quoteTypeId)
            ->where('insurance_provider.is_active', 1)
            ->where('insurance_provider.is_deleted', 0)
            ->orderBy('insurance_provider.text')
            ->get([
                'insurance_provider.id',
                'insurance_provider.code',
                'insurance_provider.text',
                'insurance_provider.text_lms',
                'insurance_provider_quote_type.insurance_provider_id',
                'insurance_provider_quote_type.quote_type_id',
            ]);

        // If a specific provider ID was requested but not present in the mapping result,
        // fetch it directly from insurance_provider and append it.
        if ($insuranceProviderId !== null && $providers->where('id', $insuranceProviderId)->isEmpty()) {
            $missing = DB::table('insurance_provider')
                ->where('id', $insuranceProviderId)
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->first([
                    'id',
                    'code',
                    'text',
                    'text_lms',
                    DB::raw('id as insurance_provider_id'),
                    DB::raw('NULL as quote_type_id'),
                ]);

            if ($missing) {
                $providers->push($missing);
            }
        }

        return $providers;
    }
    public function fetchNetworksByInsuranceProviders($request)
    {
        $networks = [];
        $insuranceProvidersIds = explode(',', $request['insuranceProviderId']);
        if (! empty($insuranceProvidersIds)) {
            $data = HealthRatingEligibility::whereIn('insurance_provider_id', $insuranceProvidersIds)
                ->select('text')
                ->get();
            $networks = $data->map(function ($item) {
                return [
                    'value' => $item->text,
                    'label' => $item->text,
                ];
            })->values();
        }

        return $networks;
    }

    public function fetchNetworksIdByInsuranceProvider($providerId, $network)
    {
        $networkId = HealthRatingEligibility::where('insurance_provider_id', $providerId)
            ->select('id')
            ->where('text', $network)
            ->first();

        return $networkId->id ?? 0;
    }

    public function fetchIsCommercialVehicles($record)
    {
        $commercialVehicleCount = DB::table('car_make')
            ->join('car_model', 'car_make.code', '=', 'car_model.car_make_code')
            ->where('car_make.id', $record->car_make_id ?? 0)
            ->where('car_model.id', $record->car_model_id ?? 0)
            ->where('car_make.is_commercial', 1)
            ->where('car_model.is_commercial', 1)
            ->count();
        if ($commercialVehicleCount > 0) {
            return true;
        }

        return false;
    }

    public function fetchGetById($id)
    {
        return $this->where('id', $id)->firstOrFail();
    }

    public function fetchGetInslyProviderId($insurerName)
    {
        return InslyInsuranceProvider::select('insurance_provider_id')
            ->where('insly_insurer_name', '=', $insurerName)
            ->first()->insurance_provider_id ?? null;
    }
}
