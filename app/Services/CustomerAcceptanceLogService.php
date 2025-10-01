<?php

namespace App\Services;

use App\Enums\QuoteTypes;
use App\Models\BorLog;
use App\Models\CustomerAcceptanceLog;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;

class CustomerAcceptanceLogService
{
    use GenericQueriesAllLobs;

    /**
     * Get BOR logs for a lead with embedded document data
     *
     * @return array
     */
    public function getCustomerAcceptanceLogs(array $data)
    {
        $quoteObject = $this->getQuoteObject($data['lob'], $data['leadId']);
        $isPersonalQuote = checkPersonalQuotes(ucfirst($data['lob']));
        ! $isPersonalQuote && $quoteObject->load('personalQuote');
        $personalQuote = $isPersonalQuote ? $quoteObject : $quoteObject->personalQuote;

        if (! $personalQuote) {
            throw new \Exception('Personal quote not found for BOR logs');
        }

        // Get paginated BOR logs with relationships
        $logs = CustomerAcceptanceLog::where('quote_uuid', $personalQuote->uuid)
            ->orderBy('created_at', 'desc')
            ->simplePaginate(15)
            ->withQueryString();

        // Total count for backward compatibility
        $total = CustomerAcceptanceLog::where('quote_uuid', $personalQuote->uuid)->count();

        $customerAcceptanceLogsUrl = config('constants.DECLARATION_BASE_URL');  

        return [$logs, $total, $customerAcceptanceLogsUrl];
    }
}
