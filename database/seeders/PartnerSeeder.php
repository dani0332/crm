<?php

namespace Database\Seeders;

use App\Enums\EnvEnum;
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
            ['name' => 'Cars 24', 'code' => 'cars24'],
            ['email' => $partnerEmail, 'is_active' => true],
        );

        LeadSource::firstOrCreate(
            ['code' => 'cars24', 'is_active' => true, 'is_applicable_for_rules' => true],
            ['name' => 'cars24'],
        );
    }
}
