<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Enums\SageEnum;
use App\Models\EmbeddedTransaction;
use App\Models\PaymentSplits;
use App\Models\SageProcess;
use App\Models\SendUpdateLog;
use App\Services\Logger\LoggerService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

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

            /* // Get all distinct model types and categorize them
            $modelTypes = SageProcess::whereNotIn('model_type', [PaymentSplits::class, EmbeddedTransaction::class])
                ->where('status', SageEnum::SAGE_PROCESS_FAILED_STATUS)
                ->distinct()
                ->pluck('model_type')
                ->filter();

            // Categorize model types based on whether they have quote_status_id column
            $modelsWithQuoteStatus = [];
            $modelsWithoutQuoteStatus = [];
            
            foreach ($modelTypes as $modelType) {
                if (class_exists($modelType)) {
                    $modelInstance = new $modelType();
                    $tableName = $modelInstance->getTable();
                    
                    if (Schema::hasColumn($tableName, 'quote_status_id')) {
                        $modelsWithQuoteStatus[] = $modelType;
                    } else {
                        $modelsWithoutQuoteStatus[] = $modelType;
                    }
                }
            } */

            $query = SageProcess::with([
                'model:id,code' => [
                    'sageApiLogs' => function ($query) {
                        $query->where('status', SageEnum::STATUS_FAIL);
                    },
                ],
                'insuranceProvider:id,text'
                ])
                ->whereNotIn('model_type', [PaymentSplits::class, EmbeddedTransaction::class])
                ->where('status', SageEnum::SAGE_PROCESS_FAILED_STATUS)
                ->whereHas('model', function ($query) {
                    $query->whereHas('sageApiLogs', function ($subQuery) {
                        $subQuery->where('status', SageEnum::STATUS_FAIL);
                    });
                });


            /* // Add quote_status_id filter conditionally based on model type
            if (!empty($modelsWithQuoteStatus) || !empty($modelsWithoutQuoteStatus)) {
                $query->where(function ($q) use ($modelsWithQuoteStatus, $modelsWithoutQuoteStatus) {
                    // For models with quote_status_id, check the status
                    if (!empty($modelsWithQuoteStatus)) {
                        $q->orWhere(function ($subQuery) use ($modelsWithQuoteStatus) {
                            $subQuery->whereIn('model_type', $modelsWithQuoteStatus)
                                ->whereHas('model', function ($modelQuery) {
                                    $modelQuery->where('quote_status_id', QuoteStatusEnum::POLICY_BOOKING_FAILED);
                                });
                        });
                    }
                    
                    // For models without quote_status_id, include them without status check
                    if (!empty($modelsWithoutQuoteStatus)) {
                        $q->orWhereIn('model_type', $modelsWithoutQuoteStatus);
                    }
                });
            } */

            $failedLeads = $query->simplePaginate(10);

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
