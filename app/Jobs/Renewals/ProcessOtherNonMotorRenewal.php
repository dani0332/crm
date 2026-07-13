<?php

declare(strict_types=1);

namespace App\Jobs\Renewals;

use App\Enums\ProcessStatusCode;
use App\Models\RenewalsUploadLeads;
use App\Services\Logger\LoggerService;
use App\Services\OtherNonMotorRenewalsUploadService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessOtherNonMotorRenewal implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;
    public int $backoff = 10;

    public function __construct(private int $renewalsUploadLeadId, private int $renewalQuoteProcessId)
    {
        $this->onQueue('renewals');
    }

    public function handle(OtherNonMotorRenewalsUploadService $service): void
    {
        $service->processSingle($this->renewalsUploadLeadId, $this->renewalQuoteProcessId);
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->renewalQuoteProcessId))->dontRelease()];
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        $renewalsUploadLead = RenewalsUploadLeads::find($this->renewalsUploadLeadId);
        $renewalsUploadLead?->update(['status' => ProcessStatusCode::FAILED]);
        LoggerService::info('CL: '.get_class().' FN: failed. Job Failed. Error: '.$exception->getMessage());
    }
}
