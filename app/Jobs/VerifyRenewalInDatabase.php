<?php

namespace App\Jobs;

use App\Models\RenewalsUploadLeads;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
class VerifyRenewalInDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $fileName;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $record = RenewalsUploadLeads::where('file_name', $this->fileName)->first();
        if($record)
        {
            // if record exists, update the number of rows uploaded
            $record->good = $record->good + 1;
            $record->save();
        }
        if(($record->good + $record->cannot_upload) == $record->total_records){
            // if all records are uploaded, update the status to completed
                $record->status = 'Completed';
                $record->save();
        }
    }
}
