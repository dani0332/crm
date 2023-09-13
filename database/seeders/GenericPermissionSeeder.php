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
            Permission::findOrCreate($permission['name'], 'web');

            if (! empty($permission['role'])) {
                if (($role = Role::where('name', $permission['role'])->first()) && ! $role->hasPermissionTo($permission['name'])) {
                    $role->givePermissionTo($permission['name']);
                }
            }
        }

    }
}
