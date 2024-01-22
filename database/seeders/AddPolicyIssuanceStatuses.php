<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddPolicyIssuanceStatuses extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('policy_issuance_status');
    }
}
