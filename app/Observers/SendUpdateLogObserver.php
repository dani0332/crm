<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SendUpdateLog;
use App\Services\SendUpdateLogService;
use App\Services\SendUpdateStatusLogService;

class SendUpdateLogObserver
{
    public function created(SendUpdateLog $sendUpdateLog): void
    {
        if (empty($sendUpdateLog->status)) {
            return;
        }

        app(SendUpdateStatusLogService::class)->createSendUpdateStatusLog(
            $sendUpdateLog,
            '',
            $sendUpdateLog->status,
        );
    }

    public function updated(SendUpdateLog $sendUpdateLog): void
    {
        if ($sendUpdateLog->wasChanged('status')) {
            $previous = $sendUpdateLog->getOriginal('status') ?? '';

            app(SendUpdateStatusLogService::class)->createSendUpdateStatusLog(
                $sendUpdateLog,
                $previous,
                $sendUpdateLog->status,
            );

            if ($sendUpdateLog->status === SendUpdateLogStatusEnum::UPDATE_ISSUED) {
                info('SendUpdateLogObserver -> fn: updated for Send Update - code: '.$sendUpdateLog->code);
                $response = app(SendUpdateLogService::class)->generateBrokerInvoiceNumberForSU($sendUpdateLog);
                throw_if(! $response['status'], new \Exception($response['message']));
            }
        }
    }
}
