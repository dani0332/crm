<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Logger\LoggerService;
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
                'name' => PermissionsEnum::DELETE_ADDITIONAL_CONTACT,
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
        $this->addTransAppSearchPermission();
        $this->addLifeRevivalPermissions();
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

    private function addTransAppSearchPermission(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::TRANSAPP_SEARCH,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->where(function ($query) {
                $query->whereIn('name', [RolesEnum::Admin, RolesEnum::Engineering])
                    ->orWhereHas('permissions', function ($permissionQuery) {
                        $permissionQuery->whereIn('name', [PermissionsEnum::TransAppCreate, PermissionsEnum::TransAppEdit, PermissionsEnum::TransAppDelete]);
                    });
            })
            ->get();

        if ($roles->isNotEmpty()) {
            foreach ($roles as $role) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                    LoggerService::info("Permission {$permission->name} assigned to role {$role->name}");
                } else {
                    LoggerService::info("Role {$role->name} already has permission {$permission->name}");
                }
            }
        }
    }

    private function addLifeRevivalPermissions(): void
    {
        $permissions = [
            [
                'name' => PermissionsEnum::LIFE_REVIVAL_QUOTES_LIST,
                'guard_name' => 'web',
            ],
            [
                'name' => PermissionsEnum::LIFE_REVIVAL_QUOTES_SHOW,
                'guard_name' => 'web',
            ],
            [
                'name' => PermissionsEnum::LIFE_REVIVAL_QUOTES_EDIT,
                'guard_name' => 'web',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                [
                    'name' => $permissionData['name'],
                    'guard_name' => $permissionData['guard_name'],
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
