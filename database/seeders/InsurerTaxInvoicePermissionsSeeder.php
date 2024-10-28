<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class InsurerTaxInvoicePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [PermissionsEnum::SEARCH_INSURER_TAX_INVOICE_NUMBER, PermissionsEnum::SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}
