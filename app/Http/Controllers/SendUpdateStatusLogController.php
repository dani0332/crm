<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SendUpdateStatusLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SendUpdateStatusLogController extends Controller
{
    public function index(Request $request, SendUpdateStatusLogService $sendUpdateStatusLogService): JsonResponse
    {
        $validatedData = $request->validate([
            'sendUpdateLogId' => ['required', 'integer', 'min:1'],
        ]);

        $logs = $sendUpdateStatusLogService->getSendUpdateStatusLogs(
            (int) $validatedData['sendUpdateLogId'],
        );

        return response()->json($logs);
    }
}
