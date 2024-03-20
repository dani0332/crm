<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Enums\PermissionsEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AddCapAdvisorPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $adviosrCapPermission = Permission::where('name', PermissionsEnum::ADVISOR_CAPACITY_MANAGEMENT)->first();
        if ($adviosrCapPermission == null) {
            DB::table('permissions')->insert([
                'name' => PermissionsEnum::ADVISOR_CAPACITY_MANAGEMENT,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

    }
}
