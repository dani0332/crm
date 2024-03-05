<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddPolicyIssuanceStatuses extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            'Portal Down',
            'Waiting for client confirmation',
            'Issue found',
            'Underwriter Issuance',
            'Portal Issuance',
            'Policy already issued by the underwriter',
            'Renewal, Direct to Underwriter',
            'Policy Issued',
            'Other',
        ];

        $index = 1;
        foreach ($statuses as $status) {
            $statusExists = DB::table('policy_issuance_status')->where(['text' => $status])->first();
            if (! $statusExists) {
                DB::table('policy_issuance_status')->insert([
                    'text' => $status,
                    'text_ar' => $status,
                    'is_active' => true,
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $index++;
            }
        }
    }
}
