<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EndorsementNumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::firstOrCreate([
            'name' => PermissionsEnum::TAP_BET_ACCESS,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
