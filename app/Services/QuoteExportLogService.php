<?php

namespace App\Services;

use App\Models\QuoteExportLog;
use Illuminate\Support\Facades\Log;

class QuoteExportLogService extends BaseService
{
    /**
     * Save the quote export log.
     *
     * @return QuoteExportLog
     */
    public function saveLog(array $data): bool
    {
        try {
            QuoteExportLog::create($data);

            return true;
        } catch (\Exception $e) {
            Log::error('Error quote export log: '.$e->getMessage());

            return false;
        }
    }
}
