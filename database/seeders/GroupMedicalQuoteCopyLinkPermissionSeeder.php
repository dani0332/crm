<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class GroupMedicalQuoteCopyLinkPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate(
            [
                'name' => PermissionsEnum::GMQuoteCopyLink,
                'guard_name' => 'web',
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                RolesEnum::GMAdvisor,
                RolesEnum::GMManager,
                RolesEnum::Admin,
                RolesEnum::PreQualificationAdvisor,
            ])
            ->get();

        foreach ($roles as $role) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
