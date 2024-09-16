<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class AddSuperLeadStatusChangePermission extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionCreated = Permission::findOrCreate(PermissionsEnum::SUPER_LEAD_STATUS_CHANGE, 'web');
    }
}
