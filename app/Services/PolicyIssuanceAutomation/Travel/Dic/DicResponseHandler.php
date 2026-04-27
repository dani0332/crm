<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

class DicResponseHandler
{
    /**
     * @param  mixed  $error
     * @param  mixed  $data
     */
    public function buildStepResponse(string $step, bool $status = false, ?string $message = null, $error = null, $data = null): array
    {
        return [
            'status' => $status,
            'completed_step' => $step,
            'message' => $message,
            'error' => $error,
            'data' => $data,
        ];
    }
}
