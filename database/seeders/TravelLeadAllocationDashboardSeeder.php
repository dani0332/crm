<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TravelLeadAllocationDashboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permission = DB::table('permissions')->where('name', 'travel-lead-allocation-dashboard')->first();

        // Add permission if it doesn't exist
        if (! $permission) {
            $id = DB::table('permissions')->insertGetId([
                'name' => 'travel-lead-allocation-dashboard',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $id = $permission->id;
        }

        // Add permission to selected roles
        $roles = DB::table('roles')->whereIn('name', [
            'ADMIN',
            // 'TRAVEL_ADVISOR',
            'TRAVEL_MANAGER',
            'LEAD_ALLOCATION',
            'RENEWALS_MANAGER',
        ])->get();

        foreach ($roles as $role) {
            DB::table('role_has_permissions')->updateOrInsert(
                [
                    'permission_id' => $id,
                    'role_id' => $role->id,
                ],
                [] // no updates, just insert if not exists
            );
        }
    }
}
