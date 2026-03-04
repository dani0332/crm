<?php

namespace Database\Seeders;

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
        Partner::updateOrCreate(
            [
                'name' => 'Cars 24',
                'code' => 'cars24',
                'is_active' => true,
            ]
        );
    }
}
