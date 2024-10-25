<?php

namespace App\Http\Controllers;

use App\Services\QuoteExportLogService;
use Illuminate\Http\Request;

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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'quote_type_id' => 'required|exists:quote_type,id',
            'url' => 'required',
        ]);

        $data['user_id'] = auth()->user()->id;
        $data['ip_address'] = $request->ip();

        $result = $this->quoteExportLogService->saveLog($data);

        return response()->json(['success' => $result]);
    }
}
