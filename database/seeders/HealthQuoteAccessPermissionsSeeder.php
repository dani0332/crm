<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Models\Permission;
use Illuminate\Database\Seeder;

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
