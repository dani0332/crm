<?php

namespace Database\Seeders;

use App\Models\PolicyIssuanceStatus;
use Illuminate\Database\Seeder;

class PolicyIssuanceStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['id' => 1, 'text' => 'Portal Down', 'text_ar' => 'Portal Down'],
            ['id' => 2, 'text' => 'Waiting for client confirmation', 'text_ar' => 'Waiting for client confirmation'],
            ['id' => 3, 'text' => 'Issue found', 'text_ar' => 'Issue found'],
            ['id' => 4, 'text' => 'Underwriter Issuance', 'text_ar' => 'Procedure for issuing the policy by sending an email to the underwriter'],
            ['id' => 5, 'text' => 'Portal Issuance', 'text_ar' => 'The procedure of policy issuance via the designated underwriter portal.'],
            ['id' => 6, 'text' => 'Policy already issued by the underwriter', 'text_ar' => 'The Policy has already been issued and requires recording in IMCRM and Send Policy '],
            ['id' => 7, 'text' => 'Renewal, Direct to Underwriter', 'text_ar' => 'The Policy has already been renewed and issued and requires recording in IMCRM and Send Policy '],
            ['id' => 8, 'text' => 'Other', 'text_ar' => 'Other'],
            ['id' => 9, 'text' => 'Policy Issued', 'text_ar' => 'Policy Issued'],
        ];

        foreach ($statuses as $status) {
            PolicyIssuanceStatus::firstOrCreate([
                'text' => $status['text'],
            ], $status);
        }

    }
}
