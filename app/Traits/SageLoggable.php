<?php

namespace App\Traits;

use App\Models\SageApiLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait SageLoggable
{
    public function logSageApiCall($payload, $response = [], $section = null, $step = null, $totalSteps = null, $status = 'success', $loggedInUserId = null)
    {
        try {
            $userId = $section->userId ?? ($loggedInUserId ?? Auth::id());
            // Ensure mandatory fields are populated
            SageApiLog::updateOrCreate(
                [
                    'section_id' => optional($section)->id,
                    'section_type' => optional($section)->getMorphClass(),
                    'step' => $step,
                    'sage_request_type' => $payload['sage_request_type'] ?? '',
                    'entry_type' => $payload['entry_type'] ?? '',
                ],
                [
                    'user_id' => $userId,
                    'total_steps' => $totalSteps,
                    'sage_end_point' => $payload['endPoint'],
                    'sage_payload' => json_encode($payload['payload'] ?? []),
                    'response' => json_encode($response),
                    'status' => $status,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to log Sage API call: '.$e->getMessage());
        }
    }
}
