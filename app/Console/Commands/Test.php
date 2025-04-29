<?php

namespace App\Console\Commands;

use App\Models\Audit\AllocationAudit;
use Illuminate\Console\Command;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // AllocationAudit::where('quote_type_id', 0)->delete();

        // fetch all allocation audits and remove auditable_id and auditable_type attributes from the collection

        $allocationAudits = AllocationAudit::all();
        $allocationAudits = $allocationAudits->map(function ($audit) {
            // Remove the attributes from the model
            unset($audit->auditable_id);
            unset($audit->auditable_type);
            return $audit;
        });

        $allocationAudits->each(function ($audit) {
            $audit->save();
        });
    }
}
