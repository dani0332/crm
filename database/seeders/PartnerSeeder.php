<?php

namespace Database\Seeders;

use App\Enums\EnvEnum;
use App\Enums\LeadSourceEnum;
use App\Models\InsurancePartner;
use App\Models\LeadSource;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $partnerEmail = 'sureshbabu.rajendran@myalfred.com';
        if (config('constants.APP_ENV') == EnvEnum::PRODUCTION) {
            $partnerEmail = '';
        }

        InsurancePartner::firstOrCreate(
            ['name' => 'Cars 24', 'code' => LeadSourceEnum::CARS24],
            ['email' => $partnerEmail, 'is_active' => false],
        );

        LeadSource::firstOrCreate(
            ['code' => LeadSourceEnum::CARS24],
            ['name' => LeadSourceEnum::CARS24, 'is_active' => false, 'is_applicable_for_rules' => true],
        );
    }
}
