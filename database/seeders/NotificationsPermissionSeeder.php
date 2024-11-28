<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $callbackRoles = Role::whereIn('name', [RolesEnum::CarAdvisor, RolesEnum::TravelAdvisor, RolesEnum::HealthAdvisor])->get();
        $callbackPermission = Permission::firstOrCreate([
            'name' => PermissionsEnum::CALLBACK_NOTIFICATIONS ?? 'callback-notifications',
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($callbackRoles as $role) {
            $record = DB::table('role_has_permissions')
                ->where('role_id', $role->id)
                ->where('permission_id', $callbackPermission->id)
                ->first();

            if (empty($record)) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $role->id,
                    'permission_id' => $callbackPermission->id,
                ]);
            }
        }

        $paymentRoles = Role::whereIn('name', [RolesEnum::CarAdvisor, RolesEnum::TravelAdvisor, RolesEnum::HealthAdvisor, RolesEnum::PetAdvisor, RolesEnum::BikeAdvisor, RolesEnum::HomeAdvisor, RolesEnum::LifeAdvisor, RolesEnum::CycleAdvisor, RolesEnum::YachtAdvisor, RolesEnum::JetskiAdvisor, RolesEnum::BusinessAdvisor, RolesEnum::CorpLineAdvisor])->get();
        $paymentPermission = Permission::firstOrCreate([
            'name' => PermissionsEnum::PAYMENT_NOTIFICATIONS ?? 'payment-notifications',
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($paymentRoles as $role) {
            $record = DB::table('role_has_permissions')
                ->where('role_id', $role->id)
                ->where('permission_id', $paymentPermission->id)
                ->first();

            if (empty($record)) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $role->id,
                    'permission_id' => $paymentPermission->id,
                ]);
            }
        }
    }
}
