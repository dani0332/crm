<?php

namespace Database\Seeders;

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
        $roles = ['_ADVISOR', '_MANAGER', '_RENEWAL_ADVISOR', '_RENEWAL_MANAGER', 'NEW_BUSINESS_MANAGER', '_NEW_BUSINESS_ADVISOR'];
        $lobs = [QuoteTypes::BIKE->value, QuoteTypes::CYCLE->value, QuoteTypes::YACHT->value, QuoteTypes::JETSKI->value];
        $permissions = ['-quotes-list', '-quotes-show', '-quotes-create', '-quotes-edit', '-quotes-delete'];

        foreach ($lobs as $lob) {
            foreach ($roles as $role) {
                Role::findOrCreate(strtoupper($lob).$role, 'web');
            }

            foreach ($permissions as $permission) {
                Permission::findOrCreate(strtolower($lob).$permission, 'web');
            }
        }
    }
}
