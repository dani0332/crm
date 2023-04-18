<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddPermissionForExportData extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permission = PermissionsEnum::DATA_EXTRACTION;
        if (! DB::table('permissions')->where('name', $permission)->first()) {
            DB::table('permissions')->insert(
                [
                    'name' => $permission,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
