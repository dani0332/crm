<?php

namespace App\Services;

use App\Models\CommunicationEventLog;
use Illuminate\Support\Collection;

class CommunicationEventLogService
{
    public function getLogsForQuoteUuid(string $quoteUuid): Collection
    {
        return CommunicationEventLog::query()
            ->where('quoteUuid', $quoteUuid)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
