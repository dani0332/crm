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
            ['name' => PermissionsEnum::DATA_EXTRACTION],
            ['name' => PermissionsEnum::CAR_SOLD_LIST,            'role' => RolesEnum::MarketingOperations],
            ['name' => PermissionsEnum::CAR_UNCONTACTABLE_LIST,   'role' => RolesEnum::MarketingOperations],
        ];

        foreach ($permissions as $permission) {

            $permissionRecord = Permission::where('name', $permission['name'])->first();

            if(!$permissionRecord) {
                $permissionRecord = Permission::create([
                    'name' => $permission['name'],
                    'guard_name' => 'web'
                ]);
            }

            if (! empty($permission['role'])) {

                $role = Role::where('name',$permission['role'])->first();

                if(!$role)
                {
                    $role = Role::create([
                        'name' => $permission['role'],
                        'guard_name' => 'web'
                    ]);
                }

                if ( ! $role->hasPermissionTo($permissionRecord->id)) {
                    $role->givePermissionTo($permissionRecord->id);
                }
            }

        }

    }
}
