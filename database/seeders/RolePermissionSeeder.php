<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $this->addReceiveNotificationsPermission();
    }

    private function addReceiveNotificationsPermission()
    {
        $roles = Role::whereIn('name', [RolesEnum::CarAdvisor, RolesEnum::TravelAdvisor, RolesEnum::HealthAdvisor, RolesEnum::PetAdvisor, RolesEnum::BikeAdvisor, RolesEnum::HomeAdvisor, RolesEnum::LifeAdvisor, RolesEnum::CycleAdvisor, RolesEnum::YachtAdvisor, RolesEnum::JetskiAdvisor, RolesEnum::BusinessAdvisor, RolesEnum::CorpLineAdvisor])->get();
        $receiveNotificationsPermission = Permission::firstOrCreate([
            'name' => PermissionsEnum::RECEIVE_NOTIFICATIONS ?? 'receive-notifications',
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($roles as $role) {

            if (! $role->hasPermissionTo($receiveNotificationsPermission)) {
                $role->givePermissionTo($receiveNotificationsPermission);
                info("Permission {$receiveNotificationsPermission->name} assigned to role {$role->name}");
            } else {
                info("Role {$role->name} already has permission {$receiveNotificationsPermission->name}");
            }
        }
    }
}
