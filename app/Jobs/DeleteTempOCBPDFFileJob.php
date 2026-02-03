<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class DeleteTempOCBPDFFileJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    private $tempFilePath;
    public function __construct($tempFilePath)
    {
        $this->tempFilePath = $tempFilePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if the file exists and delete it
        if (Storage::disk('azureIMPrivate')->exists($this->tempFilePath)) {
            Storage::disk('azureIMPrivate')->delete($this->tempFilePath);
            info("Temporary file deleted: {$this->tempFilePath}");
        } else {
            info("Temporary file not found: {$this->tempFilePath}");
        }
    }
}
