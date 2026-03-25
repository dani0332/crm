<?php

namespace App\Services;

use App\Models\CommunicationEventLog;
use Illuminate\Support\Collection;

class CommunicationEventLogService
{
    public function getLogsForQuoteUuid(string $quoteUuid): Collection
    {
        return CommunicationEventLog::where('quote_uuid', $quoteUuid)
            ->orderByDesc('created_at')
            ->get();
    }
}
