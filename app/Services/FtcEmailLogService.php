<?php

namespace App\Services;

use App\Enums\FTCEmailLogEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\FtcEmailLog;
use App\Services\Logger\LoggerService;

class FtcEmailLogService
{
    /**
     * Create or retrieve an email tracking log
     *
     * @param  array  $payload
     * @return array{message: string, data: FtcEmailLog}
     */
    public function createTrackEmail($payload): array
    {
        LoggerService::info('FtcEmailLogService: createTrackEmail', ['payload' => $payload]);
        $checkEmailFtcLog = FtcEmailLog::where('uuid', $payload['uuid'])->first();
        if ($checkEmailFtcLog) {
            return ['message' => 'Email log already created with this uuid', 'data' => $checkEmailFtcLog];
        }
        $trackEmail = FtcEmailLog::create($payload);

        return ['message' => 'Email log created successfully', 'data' => $trackEmail];
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
        if ($payload['status'] == FTCEmailLogEnum::CLICKED && $trackEmail->quoteTrackable && ! in_array($trackEmail->quoteTrackable->quote_status_id, $statuses)) {
            LoggerService::info('FtcEmailLogService: updateTrackEmail: updating quote status to PaymentInitiated', ['quote_status_id' => $trackEmail->quoteTrackable->quote_status_id]);
            $trackEmail->quoteTrackable->update(['quote_status_id' => QuoteStatusEnum::PaymentInitiated]);
        }
        $trackEmail->update($payload);

        return $trackEmail;
    }
}
