<?php

namespace App\Http\Controllers;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PermissionsEnum;
use App\Http\Requests\ClaimDocumentRequest;
use App\Http\Requests\ClaimGetS3TempUrlRequest;
use App\Models\ClaimRequest;
use App\Models\QuoteDocument;
use App\Services\ClaimDocumentService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ClaimDocumentsController extends Controller
{
    protected ClaimDocumentService $claimDocumentService;
    protected QuoteDocumentService $quoteDocumentService;

    public function __construct(
        ClaimDocumentService $claimDocumentService,
        QuoteDocumentService $quoteDocumentService,
    ) {
        $this->claimDocumentService = $claimDocumentService;
        $this->quoteDocumentService = $quoteDocumentService;

        $this->middleware(['permission:'.PermissionsEnum::CLAIM_DOCUMENT_UPLOAD], ['only' => ['storeDocument']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_DOCUMENT_DELETE], ['only' => ['destroyDocument']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_DOCUMENT_S3_URL], ['only' => ['getS3TempUrl']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_DOWNLOAD_ALL_DOCUMENTS], ['only' => ['downloadAllDocuments']]);
    }

    /**
     * Store claim document(s) - Enhanced version following PersonalQuoteController pattern
     */
    public function storeDocument(ClaimDocumentRequest $request, ClaimRequest $claim): JsonResponse
    {
        LoggerService::startQuoteLogging($claim, LoggerFeatureEnum::CLAIM_DOCUMENT_UPLOAD);
        try {
            $files = $request->file('files', []);
            $documentData = ['document_type_code' => $request->document_type_code, 'folder_path' => $request->folder_path ?? 'claims'];

            // Use the enhanced service method
            $result = $this->claimDocumentService->uploadClaimDocuments($claim, $files, $documentData);

            LoggerService::info(' Document upload process completed', extra: [
                'claim_uuid' => $claim->uuid,
                'success_count' => $result['success_count'],
                'error_count' => $result['error_count'],
                'document_type' => $request->document_type_code,
                'user_id' => Auth::id(),
            ]);

            // Handle mixed results (some success, some failures)
            if ($result['error_count'] > 0 && $result['success_count'] > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "{$result['success_count']} document(s) uploaded successfully, {$result['error_count']} failed.",
                    'documents' => $result['uploaded_documents'],
                    'errors' => $result['errors'],
                    'partial_success' => true,
                ], 207); // 207 Multi-Status
            }

            // All failed
            if ($result['error_count'] > 0 && $result['success_count'] === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'All document uploads failed.',
                    'errors' => $result['errors'],
                ], 400);
            }

            // All succeeded
            return response()->json([
                'success' => true,
                'message' => count($files) === 1
                    ? 'Document uploaded successfully.'
                    : "{$result['success_count']} documents uploaded successfully.",
                'documents' => $result['uploaded_documents'],
            ]);

        } catch (Exception $e) {
            LoggerService::error(' Unexpected error during document upload', extra: [
                'error' => $e->getMessage(),
                'claim_uuid' => $claim->uuid,
                'document_type' => $request->document_type_code ?? null,
                'user_id' => Auth::id(),
            ]);

            return response()->json(['success' => false, 'message' => 'An unexpected error occurred during document upload.'], 500);
        }
    }

    /**
     * Delete claim document - Enhanced version with business logic validation
     */
    public function destroyDocument(ClaimRequest $claim, QuoteDocument $document): JsonResponse
    {
        LoggerService::startQuoteLogging($claim, LoggerFeatureEnum::CLAIM_DOCUMENT_DELETE);
        try {
            // Use service method with business logic validation
            $deleted = $this->claimDocumentService->deleteClaimDocument($claim, $document->id);

            if (! $deleted) {
                return response()->json(['success' => false, 'message' => 'Document could not be deleted. It may be required for claim processing or the claim is in a finalized state.'], 422);
            }

            return response()->json(['success' => true, 'message' => 'Document deleted successfully.']);

        } catch (Exception $e) {
            LoggerService::error(' Unexpected error deleting document', extra: [
                'error' => $e->getMessage(),
                'claim_uuid' => $claim->uuid,
                'document_id' => $document->id ?? null,
                'user_id' => Auth::id(),
            ]);

            return response()->json(['success' => false, 'message' => 'An unexpected error occurred while deleting the document.'], 500);
        }
    }

    /**
     * Get S3 temporary URL for document access
     */
    public function getS3TempUrl(ClaimGetS3TempUrlRequest $request): JsonResponse
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::CLAIM_DOCUMENT_S3_URL);

        try {
            // Use the same logic as quote documents for S3 temp URLs
            $tempUrl = $this->quoteDocumentService->getDocumentUrl($request->safe()->docURL);

            if ($tempUrl) {
                return response()->json(['url' => $tempUrl], 200);
            } else {
                return response()->json(['url' => null], 404);
            }

        } catch (Exception $e) {
            LoggerService::error(' Error getting S3 temp URL', extra: [
                'error' => $e->getMessage(),
                'docURL' => $request->safe()->docURL,
                'user_id' => Auth::id(),
            ]);

            return response()->json(['success' => false, 'error' => 'Failed to access document.'], 500);
        }
    }

    /**
     * Download all claim documents as a ZIP file
     */
    public function downloadAllDocuments(ClaimRequest $claim)
    {
        LoggerService::startQuoteLogging($claim, LoggerFeatureEnum::CLAIM_DOCUMENT_DOWNLOAD_ALL);
        try {
            // Use service to create ZIP
            $result = $this->claimDocumentService->createDocumentsZip($claim);

            if (! $result['success']) {
                return response()->json([
                    'message' => 'Failed to create document archive.',
                    'details' => $result['errors'] ?? [],
                ], 500);
            }

            return response()->download($result['file_path'])->deleteFileAfterSend(true);

        } catch (Exception $e) {
            LoggerService::error(' Unexpected error', extra: [
                'error' => $e->getMessage(),
                'claim_uuid' => $claim->uuid,
                'user_id' => Auth::id(),
            ]);

            return response()->json(['success' => false, 'error' => 'An unexpected error occurred while downloading documents.'], 500);
        }
    }
}
