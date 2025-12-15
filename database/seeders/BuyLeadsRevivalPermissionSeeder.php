<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BuyLeadsRevivalPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::BUY_LEADS_REVIVAL,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roles = Role::whereIn('name', [RolesEnum::Admin, RolesEnum::Engineering])->get();

        foreach ($roles as $role) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
