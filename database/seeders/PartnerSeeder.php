<?php

namespace Database\Seeders;

use App\Enums\EnvEnum;
use App\Models\Partner;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        
        Partner::updateOrCreate(
            [
                'name' => 'Cars 24',
                'code' => 'cars24',
                'email' => $partnerEmail,
                'is_active' => true,
            ]
        );
    }
}
