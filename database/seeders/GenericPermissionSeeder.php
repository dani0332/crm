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
        $healthManagerAccess = Permission::where('name', PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS)->first();
        if (! $healthManagerAccess) {
            Permission::create([
                'name' => PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS,
                'guard_name' => 'web',
            ]);
        }

        $healthQuoteAccess = Permission::where('name', PermissionsEnum::HEALTH_QUOTES_ACCESS)->first();
        if (! $healthQuoteAccess) {
            Permission::create([
                'name' => PermissionsEnum::HEALTH_QUOTES_ACCESS,
                'guard_name' => 'web',
            ]);
        }

        // Add Compliance Permission to Admin
        $role = Role::where('name', RolesEnum::Admin)->first();

        if (! $role->hasPermissionTo(PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS)) {
            $role->givePermissionTo(PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS);
        }

        if (! $role->hasPermissionTo(PermissionsEnum::HEALTH_QUOTES_ACCESS)) {
            $role->givePermissionTo(PermissionsEnum::HEALTH_QUOTES_ACCESS);
        }

        $this->embeddedProductSeeds();
    }

    private function embeddedProductSeeds()
    {
        // update name of existing permission
        Permission::where(['name' => 'embedded-product-view'])->update(['name' => PermissionsEnum::EmbeddedProductAdvisor]);

        $permissionList = [
            PermissionsEnum::EmbeddedProductAdvisor => [
                RolesEnum::CarAdvisor,
                RolesEnum::Admin,
                RolesEnum::Engineering,
                RolesEnum::BetaUser,
                RolesEnum::EpAdmin
            ],
            PermissionsEnum::EmbeddedProductAdmin => [
                RolesEnum::Admin,
                RolesEnum::Engineering,
                RolesEnum::BetaUser,
                RolesEnum::EpAdmin
            ],
        ];

        foreach ($permissionList as $permission => $roles) {
            $dataset = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            foreach ($roles as $roleName) {
                if (($role = Role::where('name', $roleName)->first()) && !$role->hasPermissionTo($dataset->id)) {
                    $role->givePermissionTo($dataset->id);
                }
            }
        }
    }
}
