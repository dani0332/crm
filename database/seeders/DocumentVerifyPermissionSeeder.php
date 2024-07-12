<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DocumentVerifyPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $roleId = Role::where('name', RolesEnum::TravelHapex ?? 'HAPEX')->first()->id;
        if (! empty($roleId)) {
            $docVeirfyPermission = Permission::where('name', PermissionsEnum::DOCUMENT_VERIFY)->first();
            if (empty($docVeirfyPermission->id)) {
                $docVeirfyPermissionId = DB::table('permissions')->insertGetId([
                    'name' => PermissionsEnum::DOCUMENT_VERIFY ?? 'document-verify',
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('role_has_permissions')->insert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $docVeirfyPermissionId,
                    ]);
            } else {
                info(PermissionsEnum::DOCUMENT_VERIFY.' Permission already exists');
            }
        } else {
            info(RolesEnum::TravelHapex.' Role not found');
        }

    }
}
