<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            //QuoteStatusTableSeeder::class,
            LookupSeeder::class,
            LostReasonsTableSeeder::class,
            AddGenericRolePermissionSeeder::class,
            addDubaiNowEmailGroup::class,
            DubaiLeadSource::class,
            ActivitySchedulesSeeder::class,
            GenericPermissionSeeder::class,
            ApplicationStorageSeeder::class,
            AddPaymentPermissions::class,
            AddCreateSendUpdatePermissionToAllRoles::class,
            SendUpdateDocumentTypesSeeder::class,
            AddSendUpdatesCategoriesInLookups::class,
            InslyRoles::class,
            InslyPermissions::class,
        ]);
    }
}
