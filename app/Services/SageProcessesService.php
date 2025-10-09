<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Enums\SageEnum;
use App\Models\EmbeddedTransaction;
use App\Models\PaymentSplits;
use App\Models\SageProcess;
use App\Services\Logger\LoggerService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Service class to handle failed sage processes and their related data
 *
 * This service fetches sage processes that are failed along with their
 * related quote/send_update entries that are marked as policy booking failed
 * and the failed entries from sage api logs.
 */
class SageProcessesService extends BaseService
{
    const CLASS_NAME = 'SageProcessesService';

    /**
     * Get failed sage processes with related quote/send_update and sage api logs
     *
     * @param  bool  $isExport  Whether this is for export
     * @return LengthAwarePaginator|Collection|array
     */
    public function getFailedSageProcesses(bool $isExport = false)
    {
        try {
            LoggerService::info(self::CLASS_NAME.' fn: '.__FUNCTION__.' - Start - Fetching failed sage processes');

            $failedLeads = SageProcess::with('model.sageApiLogs')
                ->whereNotIn('model_type', [PaymentSplits::class, EmbeddedTransaction::class])
                ->where('status', SageEnum::SAGE_PROCESS_FAILED_STATUS)
                ->whereHas('model', function ($query) {
                    $query->whereHas('sageApiLogs', function ($subQuery) {
                        $subQuery->where('status', SageEnum::STATUS_FAIL);
                    });
                    // $query->where('quote_status_id', QuoteStatusEnum::POLICY_BOOKING_FAILED);
                })
                ->simplePaginate(10);

            return $failedLeads;
        } catch (\Exception $e) {
            LoggerService::error(self::CLASS_NAME.' fn: '.__FUNCTION__.' - Error fetching failed sage processes: '.$e->getMessage(), extra: [
                'trace' => $e->getTraceAsString(),
            ]);
            dd($e);
            throw $e;
        }
    }

}
