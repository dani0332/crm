<?php

namespace Database\Seeders;

use App\Models\HealthNationalityGroup;
use Illuminate\Database\Seeder;

class HealthNationalityGroupSeeder extends Seeder
{
    public function run(): void
    {
        HealthNationalityGroup::upsert([
            [
                'group_code' => 'GCC',
                'group_name' => 'GCC Nationals',
                'is_active' => true,
                'effective_from' => '2026-03-02',
                'effective_to' => '2099-12-31',
                'created_at' => '2026-03-02',
                'updated_at' => '2026-03-02',
                'notes' => 'Gulf Cooperation Council member states',
            ],
            [
                'group_code' => 'ASIA',
                'group_name' => 'Asian',
                'is_active' => true,
                'effective_from' => '2026-03-02',
                'effective_to' => '2099-12-31',
                'created_at' => '2026-03-02',
                'updated_at' => '2026-03-02',
                'notes' => 'East + Southeast Asia',
            ],
            [
                'group_code' => 'SUBCONT',
                'group_name' => 'Subcontinent',
                'is_active' => true,
                'effective_from' => '2026-03-02',
                'effective_to' => '2099-12-31',
                'created_at' => '2026-03-02',
                'updated_at' => '2026-03-02',
                'notes' => 'Indian subcontinent countries',
            ],
            [
                'group_code' => 'EURO',
                'group_name' => 'European',
                'is_active' => true,
                'effective_from' => '2026-03-02',
                'effective_to' => '2099-12-31',
                'created_at' => '2026-03-02',
                'updated_at' => '2026-03-02',
                'notes' => 'Europe (geopolitical definition)',
            ],
            [
                'group_code' => 'NAM',
                'group_name' => 'North American',
                'is_active' => true,
                'effective_from' => '2026-03-02',
                'effective_to' => '2099-12-31',
                'created_at' => '2026-03-02',
                'updated_at' => '2026-03-02',
                'notes' => 'USA, Canada, Caribbean (as applicable)',
            ],
            [
                'group_code' => 'SAM',
                'group_name' => 'South American',
                'is_active' => true,
                'effective_from' => '2026-03-02',
                'effective_to' => '2099-12-31',
                'created_at' => '2026-03-02',
                'updated_at' => '2026-03-02',
                'notes' => 'South American continent',
            ],
            [
                'group_code' => 'AFR',
                'group_name' => 'African',
                'is_active' => true,
                'effective_from' => '2026-03-02',
                'effective_to' => '2099-12-31',
                'created_at' => '2026-03-02',
                'updated_at' => '2026-03-02',
                'notes' => 'African continent',
            ],
        ], ['group_code'], // unique key
            ['group_name', 'is_active', 'effective_from', 'effective_to', 'updated_at', 'notes']);
    }
}
