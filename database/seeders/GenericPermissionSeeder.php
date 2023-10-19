<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class GenericPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            ['name' => PermissionsEnum::PAUSE_AUTO_FOLLOWUPS],
            ['name' => PermissionsEnum::DATA_EXTRACTION],
            ['name' => PermissionsEnum::CAR_SOLD_LIST,            'role' => RolesEnum::MarketingOperations],
            ['name' => PermissionsEnum::CAR_UNCONTACTABLE_LIST,   'role' => RolesEnum::MarketingOperations],
        ];

        foreach ($permissions as $permission) {
            $permissionRecord = Permission::where('name', $permission['name'])->first();

            if (! $permissionRecord) {
                $permissionRecord = Permission::create([
                    'name' => $permission['name'],
                    'guard_name' => 'web',
                ]);
            }

            if (! empty($permission['role'])) {
                $role = Role::where('name', $permission['role'])->first();

                if (! $role) {
                    $role = Role::create([
                        'name' => $permission['role'],
                        'guard_name' => 'web',
                    ]);
                }

                if (! $role->hasPermissionTo($permissionRecord->id)) {
                    $role->givePermissionTo($permissionRecord->id);
                }
            }
        }

        $roles = [
            RolesEnum::CarAdvisor, RolesEnum::CarManager, RolesEnum::CarDeputyManager,
            RolesEnum::TravelAdvisor, RolesEnum::TravelManager,
            RolesEnum::HomeAdvisor, RolesEnum::HomeManager,
            RolesEnum::PetAdvisor, RolesEnum::PetManager,
            RolesEnum::CycleAdvisor, RolesEnum::CycleManager,
            RolesEnum::BikeAdvisor, RolesEnum::BikeManager,
            RolesEnum::CorpLineAdvisor, RolesEnum::CorplineManager,
        ];

        foreach ($roles as $item) {
            $role = Role::where('name', $item)->first();
            if (! $role->hasPermissionTo(PermissionsEnum::PaymentsCreate)) {
                $role->givePermissionTo(PermissionsEnum::PaymentsCreate);
            }
            if (! $role->hasPermissionTo(PermissionsEnum::PaymentsEdit)) {
                $role->givePermissionTo(PermissionsEnum::PaymentsEdit);
            }
        }
    }
}
