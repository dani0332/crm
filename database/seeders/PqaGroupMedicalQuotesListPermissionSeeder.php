<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Seeder;

class PqaGroupMedicalQuotesListPermissionSeeder extends Seeder
{
    /**
     * Grant GMQuotesList permission to the PreQualificationAdvisor role so PQA
     * users can see the Group Medical Quotes nav item and access the list.
     * Idempotent: safe to run multiple times.
     */
    public function run(): void
    {
        Permission::firstOrCreate(
            ['name' => PermissionsEnum::GMQuotesList, 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $role = Role::firstOrCreate(
            ['name' => RolesEnum::PreQualificationAdvisor, 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        if (! $role->hasPermissionTo(PermissionsEnum::GMQuotesList)) {
            $role->givePermissionTo(PermissionsEnum::GMQuotesList);
            LoggerService::info('PQA: permission '.PermissionsEnum::GMQuotesList.' assigned to role '.RolesEnum::PreQualificationAdvisor);
        }
    }
}
