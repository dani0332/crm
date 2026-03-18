<?php

namespace Database\Seeders;

use App\Enums\InsuranceProvidersEnum;
use App\Models\InsuranceProvider;
use App\Models\InsuranceProviderTransition;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Seeder;

class InsuranceProviderTransitionsSeeder extends Seeder
{
    /**
     * Seed renewal_insurance_provider_transitions (e.g. RSA and TM → GIG AXA for Genesis-style leads).
     * Skips seeding for a source provider if it already has an active transition.
     */
    public function run(): void
    {
        $rsa = InsuranceProvider::where('code', InsuranceProvidersEnum::RSA)->first();
        $tm = InsuranceProvider::where('code', InsuranceProvidersEnum::TM)->first();
        $axa = InsuranceProvider::where('code', InsuranceProvidersEnum::AXA)->first();

        if (! $axa) {
            LoggerService::warning('InsuranceProviderTransitionsSeeder: AXA (GIG AXA) provider not found. Skip seeding transitions.');

            return;
        }

        if ($rsa) {
            $rsaHasActiveTransition = InsuranceProviderTransition::where('source_insurance_provider_id', $rsa->id)
                ->whereHas('targetProvider', fn ($q) => $q->where('is_active', 1))
                ->exists();

            if (! $rsaHasActiveTransition) {
                InsuranceProviderTransition::create([
                    'source_insurance_provider_id' => $rsa->id,
                    'target_insurance_provider_id' => $axa->id,
                    'is_active' => true,
                ]);
                LoggerService::info('InsuranceProviderTransitionsSeeder: RSA → GIG AXA transition seeded.');
            } else {
                LoggerService::info('InsuranceProviderTransitionsSeeder: RSA already has active transition(s). Skip.');
            }
        } else {
            $this->command->warn('InsuranceProviderTransitionsSeeder: RSA provider not found. Skip RSA transition.');
        }

        if ($tm) {
            $tmHasActiveTransition = InsuranceProviderTransition::where('source_insurance_provider_id', $tm->id)
                ->whereHas('targetProvider', fn ($q) => $q->where('is_active', 1))
                ->exists();

            if (! $tmHasActiveTransition) {
                InsuranceProviderTransition::create([
                    'source_insurance_provider_id' => $tm->id,
                    'target_insurance_provider_id' => $axa->id,
                    'is_active' => true,
                ]);
                LoggerService::info('InsuranceProviderTransitionsSeeder: TM → GIG AXA transition seeded.');
            } else {
                LoggerService::info('InsuranceProviderTransitionsSeeder: TM already has active transition(s). Skip.');
            }
        } else {
            LoggerService::warning('InsuranceProviderTransitionsSeeder: TM provider not found. Skip TM transition.');
        }
    }
}
