<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class addSICWorkflow extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startWorkFlowName = ApplicationStorage::where('key_name', 'SIC_WORKFLOW_NAME')->count();
        if ($startWorkFlowName == 0) {
            ApplicationStorage::insert([
                'key_name' => 'SIC_WORKFLOW_NAME',
                'value' => 'sic_workflow_enable',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }

        $endWorkflowName = ApplicationStorage::where('key_name', 'SIC_END_WORKFLOW_NAME')->count();
        if ($endWorkflowName == 0) {
            ApplicationStorage::insert([
                'key_name' => 'SIC_END_WORKFLOW_NAME',
                'value' => 'sic_workflow_disable',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }

        $sicNoAdvisorEmailTemplateID = ApplicationStorage::where('key_name', 'SIC_NO_ADVISOR_TEMPLATE_ID')->count();
        if ($sicNoAdvisorEmailTemplateID == 0) {
            ApplicationStorage::insert([
                'key_name' => 'SIC_NO_ADVISOR_TEMPLATE_ID',
                'value' => 605,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
    }
}
