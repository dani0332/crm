<?php

namespace Database\Seeders;


use App\Enums\RolesEnum;
use App\Enums\PermissionsEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DocumentVerifyPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $roleId = Role::where('name', RolesEnum::TravelHapex ?? 'HAPEX')->first()->id;
        if(!empty($roleId)){
            $docVeirfyPermission = Permission::where('name', PermissionsEnum::DOCUMENT_VERIFY)->first();
            if(empty($docVeirfyPermission->id)){
                DB::table('permissions')->insert([
                    'name' =>  PermissionsEnum::DOCUMENT_VERIFY ?? 'document-verify',
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('role_has_permissions')->insert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $docVeirfyPermission->id,
                    ]);
            }
            else {
                info(PermissionsEnum::DOCUMENT_VERIFY .' Permission not found');
            }
        }
        else {
            info(RolesEnum::TravelHapex .' Role not found');
        }


    }
}
