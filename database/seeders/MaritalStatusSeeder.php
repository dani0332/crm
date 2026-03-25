<?php

namespace Database\Seeders;

use App\Models\MartialStatus;
use Illuminate\Database\Seeder;

class MaritalStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->disableMaritalStatus();
    }

    private function disableMaritalStatus(): void
    {
        $codes = ['Unmarried partner'];
        MartialStatus::whereIn('code', $codes)->update(['is_active' => 0]);
    }
}
