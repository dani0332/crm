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

    public int $tries = 2;
    public int $timeout = 600;
    public int $backoff = 60;

    public function __construct(
        private readonly string $filePath,
        private readonly string $myalfredExpiryDate,
        private readonly string $cdbId,
        private readonly bool $invitationEmail,
        private readonly int $userId,
    ) {}

    public function handle(SendEmailCustomerService $sendEmailCustomerService, BerlinService $berlinService): void
    {
        $import = new CustomersImport(
            $this->myalfredExpiryDate,
            $this->cdbId,
            $this->invitationEmail,
            $sendEmailCustomerService,
            $berlinService,
        );

        Excel::import($import, Storage::path($this->filePath));

        Storage::delete($this->filePath);

        event(new CustomerUploadCompleted($this->userId, 'success', $import->rowCount, $this->cdbId));
    }

    public function failed(Throwable $e): void
    {
        Log::error('ProcessCustomerUploadJob failed', [
            'userId' => $this->userId,
            'cdbId' => $this->cdbId,
            'error' => $e->getMessage(),
        ]);

        Storage::delete($this->filePath);

        event(new CustomerUploadCompleted($this->userId, 'failed', 0, $this->cdbId));
    }
}
