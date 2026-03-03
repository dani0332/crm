<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\HealthGroupNationality;
use App\Models\HealthNationalityGroup;

class HealthGroupNationalitySeeder extends Seeder
{
    public function run(): void
    {
        // GCC values
        $groups = HealthNationalityGroup::firstWhere('group_code', 'GCC');

        HealthGroupNationality::insert([
            [
                'health_nationality_group_id' => $groups->id,
                'canonical_nationality_code' => 'CN0015',
                'is_active' => true,
                'effective_from' => '2026-03-02',
                'effective_to' => '2099-12-31',
            ],
            [
                'health_nationality_group_id' => $groups->id,
                'canonical_nationality_code' => 'CN0062',
                'is_active' => true,
                'effective_from' => '2026-03-02',
                'effective_to' => '2099-12-31',
            ],
            [
                'health_nationality_group_id' => $groups->id,
                'canonical_nationality_code' => 'CN0104',
                'is_active' => true,
                'effective_from' => '2026-03-02',
                'effective_to' => '2099-12-31',
            ],
            [
                'health_nationality_group_id' => $groups->id,
                'canonical_nationality_code' => 'CN0137',
                'is_active' => true,
                'effective_from' => '2026-03-02',
                'effective_to' => '2099-12-31',
            ],
            [
                'health_nationality_group_id' => $groups->id,
                'canonical_nationality_code' => 'CN0147',
                'is_active' => true,
                'effective_from' => '2026-03-02',
                'effective_to' => '2099-12-31',
            ],
            [
                'health_nationality_group_id' => $groups->id,
                'canonical_nationality_code' => 'CN0155',
                'is_active' => true,
                'effective_from' => '2026-03-02',
                'effective_to' => '2099-12-31',
            ],
        ]);
    }
}
