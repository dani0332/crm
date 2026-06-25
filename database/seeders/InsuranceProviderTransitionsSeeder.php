<?php

namespace Database\Seeders;

use App\Enums\InsuranceProvidersEnum;
use App\Models\InsuranceProvider;
use App\Models\InsuranceProviderTransition;
use Illuminate\Database\Seeder;

class InsuranceProviderTransitionsSeeder extends Seeder
{
    /**
     * Seed renewal_insurance_provider_transitions (e.g. RSA and TM → GIG AXA for Genesis-style leads).
     * Skips seeding for a source provider if it already has an active transition.
     */
    public function run(): void
    {
        $providers = InsuranceProvider::whereIn('code', [
            InsuranceProvidersEnum::RSA,
            InsuranceProvidersEnum::TM,
            InsuranceProvidersEnum::AXA,
        ])->get();

        $rsa = $providers->firstWhere('code', InsuranceProvidersEnum::RSA);
        $tm = $providers->firstWhere('code', InsuranceProvidersEnum::TM);
        $axa = $providers->firstWhere('code', InsuranceProvidersEnum::AXA);

        if (! $axa) {
            return;
        }

        if ($rsa) {
            $rsaHasActiveTransition = InsuranceProviderTransition::where('source_insurance_provider_id', $rsa->id)
                ->where('is_active', true)
                ->exists();

            if (! $rsaHasActiveTransition) {
                InsuranceProviderTransition::create([
                    'source_insurance_provider_id' => $rsa->id,
                    'target_insurance_provider_id' => $axa->id,
                    'is_active' => true,
                ]);
            }
        }

        if ($tm) {
            $tmHasActiveTransition = InsuranceProviderTransition::where('source_insurance_provider_id', $tm->id)
                ->where('is_active', true)
                ->exists();

            if (! $tmHasActiveTransition) {
                InsuranceProviderTransition::create([
                    'source_insurance_provider_id' => $tm->id,
                    'target_insurance_provider_id' => $axa->id,
                    'is_active' => true,
                ]);
            }
        }
    }
}
