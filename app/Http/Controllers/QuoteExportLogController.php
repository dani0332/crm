<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuoteExportLogRequest;
use App\Services\QuoteExportLogService;

class QuoteExportLogController extends Controller
{
    private $quoteExportLogService;

    public function __construct(QuoteExportLogService $quoteExportLogService)
    {
        $this->quoteExportLogService = $quoteExportLogService;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(QuoteExportLogRequest $request)
    {
        $quoteLogData = $request->validated();
        $quoteLogData['user_id'] = auth()->user()->id;
        $quoteLogData['ip_address'] = $request->ip_address ?? $request->ip();

        $result = $this->quoteExportLogService->saveLog($quoteLogData);

        return response()->json(['success' => $result]);
    }
}
