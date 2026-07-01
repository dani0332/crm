<?php

namespace App\Services;

use App\Models\HealthThirdPartyAdministrator;
use Illuminate\Database\Eloquent\Collection;

class HealthThirdPartyAdministratorService
{
    public function getByInsuranceProviderId(int $insuranceProviderId): Collection
    {
        return HealthThirdPartyAdministrator::from('health_third_party_administrator as tpa')
            ->join('health_third_party_administrator_insurance_provider as htpa', 'htpa.health_third_party_administrator_id', '=', 'tpa.id')
            ->where('tpa.is_active', 1)
            ->where('htpa.insurance_provider_id', $insuranceProviderId)
            ->select('tpa.id', 'tpa.text')
            ->get();
    }
}
