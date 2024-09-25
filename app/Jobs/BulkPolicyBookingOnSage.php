<?php

namespace App\Jobs;

use App\Enums\SageEnum;
use App\Models\SageProcess;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BulkPolicyBookingOnSage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $request;

    /**
     * Create a new job instance.
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $quoteType = $this->request->model_type;
        $quoteModelObject = $this->getModelObject($quoteType);
        $sageProcessIDs = $this->request->selectedSageProcesses;
        $quoteErrors = collect([]);
        foreach ($sageProcessIDs as $sageProcessID) {
            $sageProcess = SageProcess::find($sageProcessID);
            if ($sageProcess->status == SageEnum::SAGE_PROCESS_FAILED_STATUS) {
                $sageProcess->update(['status' => SageEnum::SAGE_PROCESS_PENDING_STATUS]);
            }
        }
    }
}
