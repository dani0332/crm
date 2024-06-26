<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class addPermissionsForInsurerNowPayment extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::findOrCreate(PermissionsEnum::INPL_USER, 'web');
        Permission::findOrCreate(PermissionsEnum::INPL_APPROVER, 'web');
    }
}
