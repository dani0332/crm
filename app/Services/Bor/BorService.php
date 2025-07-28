<?php

namespace App\Services\Bor;

use App\Enums\BorStatusEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypes;
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
     * Get BOR logs for a lead with embedded document data
     *
     * @param array $data
     * @return array
     */
    public function getBorLogs(array $data)
    {
        $quoteObject = $this->getQuoteObject($data['lob'], $data['leadId']);
        $personalQuote = checkPersonalQuotes($data['lob']) ? $quoteObject : $quoteObject->personalQuote;
        
        // Get paginated BOR logs with relationships
        $logs = BorLog::where('lead_id', $personalQuote->id)
            ->with(['insuranceProvider', 'personalQuote'])
            ->orderBy('created_at', 'desc')
            ->simplePaginate(15)
            ->withQueryString();
        
        // Total count for backward compatibility
        $total = BorLog::where('lead_id', $personalQuote->id)->count();

        // Enhance each BOR log with document data
        $logs->getCollection()->transform(function ($borLog) {
            return $this->enrichBorLogWithDocuments($borLog);
        });

        return [$logs, $total];
    }

    /**
     * Enrich a single BOR log with document data
     *
     * @param BorLog $borLog
     * @return BorLog
     */
    private function enrichBorLogWithDocuments(BorLog $borLog): BorLog
    {
        try {
            $personalQuote = $borLog->personalQuote;
            $borRefId = $borLog->bor_reference;
            if (!$personalQuote) {
                // Add empty document collections if no personal quote found
                $borLog->uploaded_documents = collect([]);
                $borLog->signed_pdf = collect([]);
                return $borLog;
            }

            // Get the quote object to access documents
            $quoteName = QuoteTypes::getName($personalQuote->quote_type_id);
            $quoteObject = $this->getQuoteObject($quoteName->value, $personalQuote->quote_id);
            
            // Filter uploaded documents (BOR letters)
            $uploadedDocuments = $quoteObject->documents->filter(function ($doc) use ($borRefId) {
                $code = $doc->document_type_code;
                $allowedCodes = [
                    DocumentTypeCode::BAL,
                    DocumentTypeCode::BAL_BIKE,
                    DocumentTypeCode::BAL_TRVL,
                    DocumentTypeCode::BAL_HOME,
                    DocumentTypeCode::BAL_HLTH,
                    DocumentTypeCode::BAL_PET,
                    DocumentTypeCode::BAL_YACHT,
                    DocumentTypeCode::BAL_CYCLE,
                    DocumentTypeCode::BAL_LIFE,
                ];
                return in_array($code, $allowedCodes) && $doc->document_category == $borRefId;
            });

            // Filter signed PDF documents
            $signedPdf = $personalQuote->documents->filter(function ($doc) {
                $code = $doc->document_type_code;
                return $code == DocumentTypeCode::BOR_SIGN;
            });

            // Add document collections to the BOR log object
            $borLog->uploaded_documents = $uploadedDocuments->values()->toArray();
            $borLog->signed_pdf = $signedPdf->values()->toArray();
            
            // Add document metadata for easy access
            $borLog->has_uploaded_documents = $uploadedDocuments->isNotEmpty();
            $borLog->has_signed_pdf = $signedPdf->isNotEmpty();
            $borLog->total_documents = $uploadedDocuments->count() + $signedPdf->count();

        } catch (\Exception $e) {
            // Log error but don't fail the entire request
            \Illuminate\Support\Facades\Log::warning('Failed to load documents for BOR log', [
                'bor_log_id' => $borLog->id,
                'error' => $e->getMessage()
            ]);
            
            // Add empty collections to prevent frontend errors
            $borLog->uploaded_documents = collect([]);
            $borLog->signed_pdf = collect([]);
            $borLog->has_uploaded_documents = false;
            $borLog->has_signed_pdf = false;
            $borLog->total_documents = 0;
        }

        return $borLog;
    }

    /**
     * Create a new BOR log
     *
     * @param array $data
     * @return array
     */
    public function createBorLog(array $data)
    {
        $quoteObject = $this->getQuoteObject($data['lob'], $data['lead_id']);
        $personalQuote = $quoteObject->personalQuote;

        $data['lead_id'] = $personalQuote->id;
        $data['status'] = BorStatusEnum::SIGNATURE_REQUESTED;

        $data['date_created'] = now();
        $data['email_sent'] = false;
        unset($data['lob']);
                
        $borLog = BorLog::create($data);

        // Send BOR request email
        $emailSent = $this->borEmailService->sendBorRequestEmail($borLog);

        // Update email sent status
        $borLog->update(['email_sent' => $emailSent]);
        
        // Enrich the created BOR log with document data
        $enrichedBorLog = $this->enrichBorLogWithDocuments($borLog->fresh(['insuranceProvider', 'personalQuote']));
        
        return ['borLog' => $enrichedBorLog, 'emailSent' => $emailSent];
    }

    public function updateBorLog(array $data, $id)
    {
        $quoteObject = $this->getQuoteObject($data['lob'], $data['lead_id']);
        $personalQuote = $quoteObject->personalQuote;

        $data['lead_id'] = $personalQuote->id;
        unset($data['lob']);

        $borLog = BorLog::findOrFail($id);
        $borLog->update($data);

        // Enrich the created BOR log with document data
        $enrichedBorLog = $this->enrichBorLogWithDocuments($borLog->fresh(['insuranceProvider', 'personalQuote']));
        
        return ['borLog' => $enrichedBorLog, 'emailSent' => false];
    }

    /**
     * Determine the appropriate BOR document type code based on lead LOB
     */
    public function determineBorDocumentType($quoteType): string
    {
        if (!$quoteType) {
            return 'BAL'; // Default to general BAL
        }

        // Map LOB to document type code
        $lobToDocumentType = [
            'car' => DocumentTypeCode::BAL,
            'bike' => DocumentTypeCode::BAL_BIKE,
            'travel' => DocumentTypeCode::BAL_TRVL,
            'home' => DocumentTypeCode::BAL_HOME,
            'pet' => DocumentTypeCode::BAL_PET,
            'health' => DocumentTypeCode::BAL_HLTH,
            'life' => DocumentTypeCode::BAL_LIFE,
            'cycle' => DocumentTypeCode::BAL_CYCLE,
            'yacht' => DocumentTypeCode::BAL_YACHT,
            'business' => DocumentTypeCode::BAL_BS,
        ];

        return $lobToDocumentType[strtolower($quoteType)] ?? 'BAL';
    }
} 