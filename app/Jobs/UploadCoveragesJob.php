<?php

namespace App\Jobs;

use App\Enums\ProcessStatusCode;
use App\Models\RateCoverageUpload;
use App\Services\RateCoverageUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class UploadCoveragesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 1200;
    public $backoff = 10;
    protected $uploadCoverages;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RateCoverageUpload $uploadCoverages)
    {
        $this->uploadCoverages = $uploadCoverages;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RateCoverageUploadService $uploadCoveragesService)
    {
        info('Coverage Job DisPatch');

        return $uploadCoveragesService->processUploadCoverages($this->uploadCoverages);
    }

    /**
     * @return array
     */
    public function middleware()
    {
        return [(new WithoutOverlapping($this->uploadCoverages->id))->dontRelease()];
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        info('CL: '.get_class().' FN: failed. Job Failed. Error: '.$exception->getMessage());
        $this->uploadCoverages->update(['status' => ProcessStatusCode::FAILED]);
    }
}
