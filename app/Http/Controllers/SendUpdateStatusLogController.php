<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SendUpdateStatusLogIndexRequest;
use App\Services\SendUpdateStatusLogService;
use Illuminate\Http\JsonResponse;

class SendUpdateStatusLogController extends Controller
{
    public function index(SendUpdateStatusLogIndexRequest $request, SendUpdateStatusLogService $sendUpdateStatusLogService): JsonResponse
    {
        $validatedData = $request->validated();

        $logs = $sendUpdateStatusLogService->getSendUpdateStatusLogs(
            (int) $validatedData['sendUpdateLogId'],
        );

        return response()->json($logs);
    }
}
