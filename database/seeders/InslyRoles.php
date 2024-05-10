<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\Role;
use Illuminate\Database\Seeder;

class InslyRoles extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate([
            'name' => RolesEnum::OperationExecutive,
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => RolesEnum::ServiceExecutive,
            'guard_name' => 'web',
        ]);
    }
}
