<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmbeddedProductRoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::firstOrCreate([
            'name' => RolesEnum::EpAdmin,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::EmbeddedProductView,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!empty($role) && !empty($permission)) {
            $roles = [RolesEnum::Admin, RolesEnum::Engineering, RolesEnum::BetaUser, RolesEnum::EpAdmin];

            foreach ($roles as $item) {
                $res = Role::where('name', $item)->first();
                if (!empty($res)) {
                    $record = DB::table('role_has_permissions')->where('role_id', $res->id)->where('permission_id', $permission->id)->first();
                    if (empty($record)) {
                        DB::table('role_has_permissions')->insert(
                            [
                                'role_id' => $res->id,
                                'permission_id' => $permission->id,
                            ]
                        );
                    }
                }
            }
        }
    }
}
