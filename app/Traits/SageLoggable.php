<?php

namespace App\Traits;

use App\Models\SageApiLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait SageLoggable
{
    protected function logSageApiCall($payload, $response = [], $section = null, $step = null, $totalSteps = null, $status = 'success')
    {
        try {
            $userId = Auth::id();
            // Ensure mandatory fields are populated
            SageApiLog::updateOrCreate(
                [
                    'section_id' => optional($section)->id,
                    'section_type' => optional($section)->getMorphClass(),
                    'step' => $step,
                ],
                [
                    'user_id' => $userId,
                    'total_steps' => $totalSteps,
                    'sage_end_point' => $payload['endPoint'],
                    'sage_payload' => json_encode($payload['payload']),
                    'response' => json_encode($response),
                    'status' => $status,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to log Sage API call: '.$e->getMessage());
        }
    }
}
