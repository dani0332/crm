<?php

namespace App\Jobs;

use App\Events\CustomerUploadCompleted;
use App\Imports\CustomersImport;
use App\Services\BerlinService;
use App\Services\SendEmailCustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessCustomerUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const SUBSCRIPTION_TYPE = 'CORPORATE';
    private const SUBSCRIPTION_QUEUE = 'corporate-myalfred-we';

    public int $tries = 2;
    public int $timeout = 600;
    public int $backoff = 60;
    public int $retryAfter = 660;

    public function __construct(
        private readonly string $filePath,
        private readonly string $myalfredExpiryDate,
        private readonly string $cdbId,
        private readonly bool $invitationEmail,
        private readonly int $userId,
    ) {
        $this->onQueue('customer-upload');
    }

    public function handle(SendEmailCustomerService $sendEmailCustomerService, BerlinService $berlinService): void
    {
        if (! Storage::disk('azureIMPrivate')->exists($this->filePath)) {
            Log::error('ProcessCustomerUploadJob: upload file missing, cannot import', [
                'path' => $this->filePath,
                'userId' => $this->userId,
            ]);
            event(new CustomerUploadCompleted($this->userId, 'failed', 0, $this->cdbId));

            return;
        }

        $import = new CustomersImport(
            $this->myalfredExpiryDate,
            $this->cdbId,
            $this->invitationEmail,
            $sendEmailCustomerService,
            $berlinService,
        );
        Excel::import($import, $this->filePath, 'azureIMPrivate');

        collect($import->customersToExtend)
            ->chunk(50)
            ->each(function ($chunk, int $chunkIndex) {
                $delay = $chunkIndex * 1; // Stagger the dispatch of jobs by 1 second per chunk to avoid overwhelming the queue
                $chunk->each(fn ($customer) => ExtendCustomerSubscriptionViaSQS::dispatch($customer, self::SUBSCRIPTION_TYPE, self::SUBSCRIPTION_QUEUE)
                    ->delay($delay));
            });

        Storage::disk('azureIMPrivate')->delete($this->filePath);

        try {
            event(new CustomerUploadCompleted($this->userId, 'success', $import->rowCount, $this->cdbId));
        } catch (Throwable $e) {
            Log::error('ProcessCustomerUploadJob: failed to broadcast completion event', [
                'userId' => $this->userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('ProcessCustomerUploadJob failed', [
            'userId' => $this->userId,
            'cdbId' => $this->cdbId,
            'error' => $e->getMessage(),
        ]);

        Storage::disk('azureIMPrivate')->delete($this->filePath);

        event(new CustomerUploadCompleted($this->userId, 'failed', 0, $this->cdbId));
    }
}
