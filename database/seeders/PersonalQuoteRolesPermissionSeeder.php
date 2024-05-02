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
        $lobs = [QuoteTypes::BIKE->value, QuoteTypes::CYCLE->value, QuoteTypes::YACHT->value, QuoteTypes::JETSKI->value, QuoteTypes::LIFE->value, QuoteTypes::PET->value, QuoteTypes::CAR_REVIVAL->value];
        $permissions = ['-quotes-list', '-quotes-show', '-quotes-create', '-quotes-edit', '-quotes-delete'];

        foreach ($lobs as $lob) {
            foreach ($roles as $role) {
                Role::findOrCreate(strtoupper($lob).$role, 'web');
            }

            foreach ($permissions as $permission) {
                Permission::findOrCreate(strtolower($lob).$permission, 'web');
            }
        }

        Permission::findOrCreate(PermissionsEnum::TravelQuotesShow, 'web');
    }
}
