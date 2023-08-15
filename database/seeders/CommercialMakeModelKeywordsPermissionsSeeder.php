<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Enums\PermissionsEnum;
use Illuminate\Database\Seeder;

class CommercialMakeModelKeywordsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::findOrCreate(PermissionsEnum::COMMERCIAL_KEYWORDS, 'web');
        Permission::findOrCreate(PermissionsEnum::CAR_MAKE_MODEL_COMMERCIAL_ALLOCATION, 'web');
    }
}
