<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

const GUARD_NAME = 'web';

class AddMotorHeadRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $carManagerRole = Role::findByName('CAR_MANAGER');

        if (! $carManagerRole) {
            Log::warning('CAR_MANAGER role not found. Motor Head role creation skipped.');

            return;
        }

        $motorHeadRole = Role::firstOrCreate([
            'name' => 'Motor_Head',
        ], [
            'guard_name' => GUARD_NAME,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $motorHeadRole->syncPermissions($carManagerRole->permissions);
        } catch (\Exception $e) {
            Log::error('Error assigning permissions to Motor Head role: '.$e->getMessage());
        }
    }
}
