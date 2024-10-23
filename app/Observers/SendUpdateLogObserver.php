<?php

namespace App\Observers;

use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SendUpdateLog;
use App\Services\SendUpdateLogService;

class SendUpdateLogObserver
{
    public function updated(SendUpdateLog $sendUpdateLog): void
    {
        $dirty = $sendUpdateLog->getDirty();
        if (
            $sendUpdateLog->isDirty('status') &&
            $sendUpdateLog->status === SendUpdateLogStatusEnum::UPDATE_ISSUED
        ) {
            $response = app(SendUpdateLogService::class)->generateBrokerInvoiceNumberForSU($sendUpdateLog);
            throw_if(! $response['status'], new \Exception($response['message']));
        }
    }
}
