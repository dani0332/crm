<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Enums\PermissionsEnum;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class HealthQuoteAccessPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::findOrCreate(PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS, 'web');
        Permission::findOrCreate(PermissionsEnum::HEALTH_QUOTES_ACCESS, 'web');
    }
}
