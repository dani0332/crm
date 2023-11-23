<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AddRoleForComplianceSuperUser extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => RolesEnum::ComplianceSuperUser], [
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
