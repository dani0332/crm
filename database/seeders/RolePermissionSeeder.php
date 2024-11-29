<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void {
        try {
            //permission for upload Health Rates and Coverages
            $uploadHealthRatesPermission = Permission::where('name', PermissionsEnum::UPLOAD_HEALTH_RATES)->first();
            if (! $uploadHealthRatesPermission) {
                Permission::create([
                    'name' => PermissionsEnum::UPLOAD_HEALTH_RATES,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $uploadHealthCoveragesPermission = Permission::where('name', PermissionsEnum::UPLOAD_HEALTH_COVERAGES)->first();
            if (! $uploadHealthCoveragesPermission) {
                Permission::create([
                    'name' => PermissionsEnum::UPLOAD_HEALTH_COVERAGES,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $th) {
            info('RolePermission Seeder issue Error:'.$th->getMessage().' Line:'.$th->getLine());
            throw $th;
        }
    }
}
