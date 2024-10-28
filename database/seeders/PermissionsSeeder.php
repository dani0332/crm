<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->paidLeads();
        $this->buyLeads();
    }

    private function paidLeads()
    {
        Permission::findOrCreate(PermissionsEnum::ASSIGN_PAID_LEADS, 'web');
    }

    private function buyLeads()
    {
        Permission::findOrCreate(PermissionsEnum::BUY_LEADS, 'web');
    }
}
