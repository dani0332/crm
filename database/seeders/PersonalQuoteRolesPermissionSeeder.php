<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PersonalQuoteRolesPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = ['_ADVISOR', '_MANAGER'];
        $lobs = [
            QuoteTypes::BIKE->value,
            QuoteTypes::CYCLE->value,
            QuoteTypes::YACHT->value,
            QuoteTypes::JETSKI->value,
            QuoteTypes::LIFE->value,
            QuoteTypes::PET->value
        ];

        $permissions = ['-quotes-list', '-quotes-show', '-quotes-create', '-quotes-edit'];

        foreach ($lobs as $lob) {
            $allowedPermissions = [];

            foreach ($permissions as $permission) {
                $insertedPermission = Permission::findOrCreate(strtolower($lob).$permission, 'web');
                $allowedPermissions[] = $insertedPermission->id;
            }

            foreach ($roles as $role) {
                $role = Role::findOrCreate(strtoupper($lob).$role, 'web');
                $role->givePermissionTo($allowedPermissions);
            }
        }

        Permission::findOrCreate(PermissionsEnum::TravelQuotesShow, 'web');
    }
}
