<?php

namespace App\Services;

use App\Enums\FTCEmailLogEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\FtcEmailLog;
use App\Services\Logger\LoggerService;

class FtcEmailLogService
{
    public function createTrackEmail($payload)
    {
        LoggerService::info('FtcEmailLogService: createTrackEmail', ['payload' => $payload]);
        $trackEmail = FtcEmailLog::create($payload);

        return $trackEmail;
    }

    public function updateTrackEmail($payload, $id, $uuid = null)
    {
        LoggerService::info('FtcEmailLogService: updateTrackEmail', ['payload' => $payload, 'id' => $id, 'uuid' => $uuid]);
        $trackEmail = $uuid == null ? FtcEmailLog::find($id) : FtcEmailLog::where('uuid', $uuid)->first();
        if ($trackEmail == null) {
            LoggerService::info('FtcEmailLogService: updateTrackEmail: trackEmail not found', ['id' => $id, 'uuid' => $uuid]);
            return null;
        }
        $statuses = [QuoteStatusEnum::PolicyIssued, QuoteStatusEnum::PolicyBooked, QuoteStatusEnum::TransactionApproved];
        if ($payload['status'] == FTCEmailLogEnum::CLICKED && !in_array($trackEmail->quoteTrackable->quote_status_id, $statuses)) {
            LoggerService::info('FtcEmailLogService: updateTrackEmail: updating quote status to PaymentInitiated', ['quote_status_id' => $trackEmail->quoteTrackable->quote_status_id]);
            $trackEmail->quoteTrackable->update(['quote_status_id' => QuoteStatusEnum::PaymentInitiated]);
        }
        $trackEmail->update($payload);

        return $trackEmail;
    }
}
