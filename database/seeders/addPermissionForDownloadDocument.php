<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class addPermissionForDownloadDocument extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $downloadDocumentPermission = Permission::where('name', 'download-all-document')->first();
        if ($downloadDocumentPermission == null) {
            DB::table('permissions')->insert([
                'name' => 'download-all-document',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        }
    }
}
