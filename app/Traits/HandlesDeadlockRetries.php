<?php

namespace App\Traits;

use App\Enums\PaymentProcessJobEnum;
use App\Models\CcPaymentProcess;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HandlesDeadlockRetries
{
    /**
     * Handle the given callback with retries for deadlock exceptions.
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function handleWithDeadlockRetries(callable $callback, int $maxRetries = 5, $splitPaymentId = 0, $quoteCode = '', $isFromJob = false)
    {
        $attempts = 0;

        while ($attempts < $maxRetries) {
            DB::beginTransaction();
            try {
                $result = $callback();

                DB::commit();

                return $result;
            } catch (Exception $exception) {
                DB::rollBack();

                if ($this->isDeadlockException($exception)) {
                    $attempts++;
                    if ($attempts >= $maxRetries) {
                        Log::error('Error After All Attempts: '.$exception->getMessage());

                        return ['status' => 'error', 'message' => $exception->getMessage()];
                    }
                    sleep(1); // Optional: wait a bit before retrying
                } else {

                    if ($splitPaymentId > 0) {
                        if ($isFromJob) {
                            CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::FAILED, 'message' => $exception->getMessage()]);
                        } else {
                            Log::error('Error in processSplitPaymentApprove '.$quoteCode.': '.$exception->getMessage());
                        }
                    }

                    Log::error('Error: '.$exception->getMessage());

                    return ['status' => 'error', 'message' => $exception->getMessage()];
                }
            }
        }
    }

    /**
     * Determine if the exception is a deadlock exception.
     */
    private function isDeadlockException(Exception $exception): bool
    {
        return in_array($exception->getCode(), ['40001', '1213']);
    }
}
