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

class UploadRatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 1200;
    public $backoff = 10;
    protected $uploadRate;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RateCoverageUpload $uploadRate)
    {
        $this->uploadRate = $uploadRate;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RateCoverageUploadService $uploadRateService)
    {
        info('Rate Job DisPatch');

        return $uploadRateService->processUploadRate($this->uploadRate);
    }

    /**
     * @return array
     */
    public function middleware()
    {
        return [(new WithoutOverlapping($this->uploadRate->id))->dontRelease()];
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        info('CL: '.get_class().' FN: failed. Job Failed. Error: '.$exception->getMessage());
        $this->uploadRate->update(['status' => ProcessStatusCode::FAILED]);
    }
}
