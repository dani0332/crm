<?php

namespace App\Services\Bor;

use App\Enums\BorStatusEnum;
use App\Models\BorLog;
use App\Traits\GenericQueriesAllLobs;

class BorService
{
    use GenericQueriesAllLobs;

    protected $borEmailService;
    protected $borPdfService;

    public function __construct(BorEmailService $borEmailService, BorPdfService $borPdfService)
    {
        $this->borEmailService = $borEmailService;
        $this->borPdfService = $borPdfService;
    }

    /**
     * Get BOR logs for a lead
     *
     * @param array $data
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getBorLogs(array $data)
    {
        $quoteObject = $this->getQuoteObject($data['lob'], $data['leadId']);
        $personalQuote = checkPersonalQuotes($data['lob']) ?  $quoteObject : $quoteObject->personalQuote;
        $logs = BorLog::where('lead_id', $personalQuote->id)->orderBy('created_at', 'desc')->simplePaginate(15)->withQueryString();
        $total = BorLog::where('lead_id', $personalQuote->id)->count();
        return [$logs, $total];
    }

    /**
     * Create a new BOR log
     *
     * @param array $data
     * @return \App\Models\BorLog
     */
    public function createBorLog(array $data)
    {
        $quoteObject = $this->getQuoteObject($data['lob'], $data['lead_id']);
        $personalQuote = $quoteObject->personalQuote;

        $data['lead_id'] = $personalQuote->id;
        $data['status'] = BorStatusEnum::PENDING_BOR_REQUEST;

        $data['date_created'] = now();
        $data['email_sent'] = false;
        unset($data['lob']);
                
        $borLog = BorLog::create($data);

        // Send BOR request email
        $emailSent = $this->borEmailService->sendBorRequestEmail($borLog);

        // Update email sent status
        $borLog->update(['email_sent' => $emailSent]);
        return ['borLog' => $borLog, 'emailSent' => $emailSent];
    }
} 