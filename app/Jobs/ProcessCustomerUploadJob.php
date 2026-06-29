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
    private const SUBSCRIPTION_DISPATCH_DELAY_SECONDS = 1;

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
        $context = ['userId' => $this->userId, 'cdbId' => $this->cdbId, 'filePath' => $this->filePath, 'attempt' => $this->attempts()];

        Log::info('ProcessCustomerUploadJob: started', $context);

        if (! Storage::disk('azureIMPrivate')->exists($this->filePath)) {
            Log::error('ProcessCustomerUploadJob: upload file missing, cannot import', $context);
            event(new CustomerUploadCompleted($this->userId, 'failed', 0, $this->cdbId));

            return;
        }

        Log::info('ProcessCustomerUploadJob: file found, starting import', $context);

        $import = new CustomersImport(
            $this->myalfredExpiryDate,
            $this->cdbId,
            $this->invitationEmail,
            $sendEmailCustomerService,
            $berlinService,
        );
        Excel::import($import, $this->filePath, 'azureIMPrivate');

        Log::info('ProcessCustomerUploadJob: import complete', [...$context, 'rowCount' => $import->rowCount, 'customersToExtend' => \count($import->customersToExtend)]);

        collect($import->customersToExtend)
            ->chunk(50)
            ->each(function ($chunk, int $chunkIndex) {
                $delay = $chunkIndex * self::SUBSCRIPTION_DISPATCH_DELAY_SECONDS;
                $chunk->each(fn ($customer) => ExtendCustomerSubscriptionViaSQS::dispatch($customer, self::SUBSCRIPTION_TYPE, self::SUBSCRIPTION_QUEUE)
                    ->delay($delay));
            });

        Log::info('ProcessCustomerUploadJob: SQS extension jobs dispatched', $context);

        Storage::disk('azureIMPrivate')->delete($this->filePath);

        Log::info('ProcessCustomerUploadJob: upload file deleted', $context);

        try {
            event(new CustomerUploadCompleted($this->userId, 'success', $import->rowCount, $this->cdbId));
            Log::info('ProcessCustomerUploadJob: completion event fired', $context);
        } catch (Throwable $e) {
            Log::error('ProcessCustomerUploadJob: failed to broadcast completion event', [...$context, 'error' => $e->getMessage()]);
        }
    }

    public function failed(Throwable $e): void
    {
        $context = ['userId' => $this->userId, 'cdbId' => $this->cdbId, 'filePath' => $this->filePath, 'error' => $e->getMessage()];

        Log::error('ProcessCustomerUploadJob failed', $context);

        Storage::disk('azureIMPrivate')->delete($this->filePath);

        try {
            event(new CustomerUploadCompleted($this->userId, 'failed', 0, $this->cdbId));
        } catch (Throwable $broadcastException) {
            Log::error('ProcessCustomerUploadJob: failed to broadcast failed event', [...$context, 'broadcastError' => $broadcastException->getMessage()]);
        }
    }
}
