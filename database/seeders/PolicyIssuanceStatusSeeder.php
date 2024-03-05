<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PolicyIssuanceStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('policy_issuance_status')->insert([
            [
                'text' => 'Portal Down',
                'text_ar' => 'Portal Down',
            ],
            [
                'text' => 'Waiting for client confirmation',
                'text_ar' => 'Waiting for client confirmation',
            ],
            [
                'text' => 'Issue found',
                'text_ar' => 'Issue found',
            ],
            [
                'text' => 'Underwriter Issuance',
                'text_ar' => 'Procedure for issuing the policy by sending an email to the underwriter',
            ],
            [
                'text' => 'Portal Issuance',
                'text_ar' => 'The procedure of policy issuance via the designated underwriter portal.',
            ],
            [
                'text' => 'Policy already issued by the underwriter',
                'text_ar' => 'The Policy has already been issued and requires recording in IMCRM and Send Policy ',
            ],
            [
                'text' => 'Renewal, Direct to Underwriter',
                'text_ar' => 'The Policy has already been renewed and issued and requires recording in IMCRM and Send Policy ',
            ],
            [
                'text' => 'Other',
                'text_ar' => 'Other',
            ],

        ]);
    }
}
