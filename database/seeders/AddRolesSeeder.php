<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AddRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => RolesEnum::ComplianceSuperUser, 'guard_name' => 'web']
        ];

        foreach ($roles as $key => $role) {
            Role::firstOrCreate(['name' => $role['name']], [
                'guard_name' => $role['guard_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
