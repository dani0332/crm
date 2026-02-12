<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Enums\PermissionsEnum;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => PermissionsEnum::VIEW_PCP,
                'guard_name' => 'web',
            ],
            [
                'name' => PermissionsEnum::EDIT_VEHICLE_TRANSACTION_DRIVER_DETAILS,
                'guard_name' => 'web',
            ],
            [
                'name' => PermissionsEnum::NONRULE_LEADALLOCATION,
                'guard_name' => 'web',
            ],
            [
                'name' => PermissionsEnum::BUY_LEADS_ADMIN,
                'guard_name' => 'web',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->addBuyLeadsAdminPermission();
    }

    private function addBuyLeadsAdminPermission(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::BUY_LEADS_ADMIN,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roles = Role::whereIn('name', [RolesEnum::Admin, RolesEnum::Engineering])->get();

        if ($roles) {
            foreach ($roles as $role) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                    info("Permission {$permission->name} assigned to role {$role->name}");
                } else {
                    info("Role {$role->name} already has permission {$permission->name}");
                }
            }
        }
    }
}
