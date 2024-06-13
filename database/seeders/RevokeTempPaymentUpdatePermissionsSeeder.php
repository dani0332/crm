<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RevokeTempPaymentUpdatePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionTempUpdate = Permission::where('name', PermissionsEnum::TEMP_UPDATE_TOTALPRICE)->first();
        if (! empty($permissionTempUpdate)) {
            // Revoke permission from all roles
            $permissionTempUpdate->roles()->detach();
        }
    }
}
