<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class ConfigSICHealthPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::firstOrCreate([
            'name' => PermissionsEnum::SIC_HEALTH_CONFIG ?? 'sic-health-config',
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
