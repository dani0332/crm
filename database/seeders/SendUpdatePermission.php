<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class SendUpdatePermission extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::firstOrCreate([
            'name' => PermissionsEnum::SEND_UPDATE_EDIT_NOTES,
            'guard_name' => 'web',
        ]);
    }
}
