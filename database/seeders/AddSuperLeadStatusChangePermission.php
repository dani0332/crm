<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enums\PermissionsEnum;
use App\Models\Permission;

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
