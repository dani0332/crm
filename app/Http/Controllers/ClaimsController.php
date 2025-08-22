<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\ClaimDetailsUpdateRequest;
use App\Http\Requests\ClaimDocumentRequest;
use App\Http\Requests\ClaimSendNotificationRequest;
use App\Http\Requests\ClaimStatusUpdateRequest;
use App\Http\Requests\ClaimStoreRequest;
use App\Http\Requests\ClaimUpdateRequest;
use App\Http\Requests\SearchPoliciesRequest;
use App\Models\ClaimRequest;
use App\Models\ClaimStatus;
use App\Models\QuoteDocument;
use App\Services\ClaimsService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ClaimsController extends Controller
{
    protected ClaimsService $claimsService;
    protected QuoteDocumentService $quoteDocumentService;

    public function __construct(
        ClaimsService $claimsService,
        QuoteDocumentService $quoteDocumentService,
    ) {
        $this->claimsService = $claimsService;
        $this->quoteDocumentService = $quoteDocumentService;
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_LIST], ['only' => ['index']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_CREATE], ['only' => ['create', 'store']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_EDIT], ['only' => ['edit', 'update', 'updateClaimDetails']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_SHOW], ['only' => ['show']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIMS_EXPORT_DATA], ['only' => ['export']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_DOCUMENT_UPLOAD], ['only' => ['storeDocument']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_DOCUMENT_DELETE], ['only' => ['destroyDocument']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_DOCUMENT_S3_URL], ['only' => ['getS3TempUrl']]);
    }

    /**
     * Display a listing of claims
     */
    public function index(Request $request): Response
    {
        try {
            $claims = $this->claimsService->getClaimsData($request);

            // Get dropdown data for filters
            $claimDropdownOptions = $this->claimsService->getDropdownData();

            return Inertia::render('Claims/Index', [
                'claims' => $claims,
                'filters' => $this->claimsService->getFilters($request),
                'claimDropdownOptions' => $claimDropdownOptions,
                'statistics' => [],
            ]);
        } catch (Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error loading claims index', extra: [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return Inertia::render('Claims/Index', [
                'claims' => collect([]),
                'filters' => [],
                'claimDropdownOptions' => [],
                'statistics' => [],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Show the form for creating a new claim
     */
    public function create()
    {
        try {
            // Get dropdown data for the form
            $claimDropdownOptions = $this->claimsService->getDropdownData();

            return Inertia::render('Claims/Create', [
                'dropdowns' => $claimDropdownOptions,
                'claim' => null,
            ]);
        } catch (Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error loading claims create form', extra: [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('claims.index')
                ->with('error', 'Failed to load create form.');
        }
    }

    /**
     * Search active policies (AJAX endpoint)
     */
    public function searchPolicies(SearchPoliciesRequest $request): JsonResponse
    {
        try {
            $policies = $this->claimsService->searchActivePolicies(
                $request->getEmail(),
                $request->getPolicyNumber(),
                $request->getQuoteTypeId(),
                $request->getPage()
            );

            return response()->json([
                'success' => true,
                'policies' => $policies,
                'message' => empty($policies) ? 'No available data' : 'Policies found successfully.',
            ]);
        } catch (Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error searching policies', extra: [
                'error' => $e->getMessage(),
                'email' => $request->getEmail(),
                'policy_number' => $request->getPolicyNumber(),
                'quote_type_id' => $request->getQuoteTypeId(),
                'page' => $request->getPage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to search policies.',
            ], 500);
        }
    }

    /**
     * Store a newly created claim
     */
    public function store(ClaimStoreRequest $request)
    {
        try {
            $claim = $this->claimsService->createClaim($request->validated());

            if (! empty($claim->errors)) {
                vAbort($claim->errors);
            }

            return redirect()->route('claims.show', $claim->claimUID)->with('success', "Claim {$claim->claimUID} has been created successfully.");
        } catch (Exception $e) {
            LoggerService::warning(self::class.'::'.__FUNCTION__.' - Error creating claim', extra: [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors($e->getMessage());
        }
    }

    /**
     * Display the specified claim request
     */
    public function show($uuid)
    {
        try {
            // Load claim request with all relationships
            $claimRequest = $this->claimsService->getClaimById($uuid);

            // Get related data for the show page
            $dropdownData = $this->claimsService->getDropdownData();
            $claimDocumentTypes = $this->claimsService->getClaimDocumentTypes($claimRequest->quote_type_id);
            $requiredFieldsFilled = $this->claimsService->isRequiredFieldsFilled($claimRequest);
            $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';

            return Inertia::render('Claims/Show', [
                'claim' => $claimRequest,
                'documents' => $claimRequest->documents,
                'dropdowns' => $dropdownData,
                'requiredFieldsFilled' => $requiredFieldsFilled,
                'claimDocumentTypes' => $claimDocumentTypes,
                'cdnPath' => $cdnPath,
                'storageUrl' => storageUrl(),
            ]);

        } catch (Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error loading claim request details - Claim UUID: '.$uuid, extra: [
                'error' => $e->getMessage(),
                'claim_request_id' => $uuid,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('claims.index')
                ->with('error', 'Failed to load claim details.');
        }
    }

    /**
     * Show the form for editing the specified claim request
     */
    public function edit($uuid)
    {
        try {
            // Load claim request with relationships
            $claimRequest = $this->claimsService->getClaimById($uuid);

            // Get dropdown data for the form
            $dropdownData = $this->claimsService->getDropdownData();

            return Inertia::render('Claims/Edit', [
                'claim' => $claimRequest,
                'dropdowns' => $dropdownData,
            ]);
        } catch (Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error loading claim request edit form - Claim UUID: '.$uuid, extra: [
                'error' => $e->getMessage(),
                'claim_request_uuid' => $uuid,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('claims.show', $uuid)->with('error', 'Failed to load edit form.');
        }
    }

    /**
     * Update the specified claim request
     */
    public function update(ClaimUpdateRequest $request, $uuid): RedirectResponse
    {
        try {
            $updatedClaimRequest = $this->claimsService->updateClaim($uuid, $request->validated());

            return redirect()->route('claims.show', $updatedClaimRequest->uuid)
                ->with('success', "Claim request {$updatedClaimRequest->code} has been updated successfully.");

        } catch (Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error updating claim request - Claim UUID: '.$uuid, extra: [
                'error' => $e->getMessage(),
                'claim_request_id' => $uuid,
                'data' => $request->validated(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors($e->getMessage());
        }
    }

    public function updateClaimDetails(ClaimDetailsUpdateRequest $request, $uuid): RedirectResponse
    {
        try {
            // Get only the validated data that should be updated
            $validatedData = $request->validatedForUpdate();

            $updatedClaimRequest = $this->claimsService->updateClaimDetails($uuid, $validatedData);

            return redirect()->route('claims.show', $updatedClaimRequest->uuid)
                ->with('success', "Claim request {$updatedClaimRequest->code} has been updated successfully.");

        } catch (Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error updating claim details - Claim UUID: '.$uuid, extra: [
                'error' => $e->getMessage(),
                'claim_request_id' => $uuid,
                'data' => $request->validatedForUpdate(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors($e->getMessage());
        }
    }

    public function updateClaimStatuses(ClaimStatusUpdateRequest $request, $uuid): RedirectResponse
    {
        try {
            $updatedClaimRequest = $this->claimsService->updateClaimStatus($uuid, $request->safe());

            return redirect()->back()->with('success', 'Claim status updated successfully.');

        } catch (Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error updating claim status - Claim UUID: '.$uuid, extra: [
                'error' => $e->getMessage(),
                'claim_request_id' => $uuid,
                'data' => $request->validated(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->with('error', 'Failed to update claim status. Please try again.');
        }
    }

    /**
     * Export claims data
     */
    public function export(Request $request)
    {
        //
    }

    /**
     * AI optimize message (AJAX endpoint)
     */
    public function optimizeMessage(Request $request, ClaimStatus $claimStatus): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            $optimizedMessage = $this->optimizeMessageWithAI($request->message);

            return response()->json([
                'status' => true,
                'optimized_message' => $optimizedMessage,
            ], 200);
        } catch (Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error optimizing message', extra: [
                'error' => $e->getMessage(),
                'message' => $request->message,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to optimize message.',
            ], 500);
        }
    }

    /**
     * Send notification to customer (AJAX endpoint)
     */
    public function sendNotification(ClaimSendNotificationRequest $request, ClaimRequest $claimRequest): JsonResponse
    {
        try {
            $this->claimsService->sendNotification($claimRequest, $request->safe());

            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully.',
            ]);
        } catch (Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error sending notification', extra: [
                'error' => $e->getMessage(),
                'claim_uuid' => $claimRequest->uuid,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification.',
            ], 500);
        }
    }

    /**
     * Store claim document(s) - Enhanced version following PersonalQuoteController pattern
     */
    public function storeDocument(ClaimDocumentRequest $request, ClaimRequest $claim): JsonResponse
    {
        try {
            $files = $request->file('files', []);
            $documentData = [
                'document_type_code' => $request->document_type_code,
                'folder_path' => $request->folder_path ?? 'claims',
            ];

            // Use the enhanced service method
            $result = $this->claimsService->uploadClaimDocuments($claim, $files, $documentData);

            LoggerService::info(self::class.'::'.__FUNCTION__.' - Document upload process completed', extra: [
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
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Unexpected error during document upload', extra: [
                'error' => $e->getMessage(),
                'claim_uuid' => $claim->uuid,
                'document_type' => $request->document_type_code ?? null,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during document upload.',
            ], 500);
        }
    }

    /**
     * Delete claim document - Enhanced version with business logic validation
     */
    public function destroyDocument(ClaimRequest $claim, QuoteDocument $document): JsonResponse
    {
        try {
            // Verify the document belongs to this claim
            if ($document->quote_documentable_id !== $claim->id ||
                $document->quote_documentable_type !== ClaimRequest::class) {

                LoggerService::warning(self::class.'::'.__FUNCTION__.' - Document ownership verification failed', extra: [
                    'claim_uuid' => $claim->uuid,
                    'document_id' => $document->id,
                    'document_claim_id' => $document->quote_documentable_id,
                    'document_type' => $document->quote_documentable_type,
                    'user_id' => Auth::id(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Document not found for this claim.',
                ], 404);
            }

            // Use service method with business logic validation
            $deleted = $this->claimsService->deleteClaimDocument($claim, $document->id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document could not be deleted. It may be required for claim processing or the claim is in a finalized state.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully.',
            ]);

        } catch (Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Unexpected error deleting document', extra: [
                'error' => $e->getMessage(),
                'claim_uuid' => $claim->uuid,
                'document_id' => $document->id ?? null,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while deleting the document.',
            ], 500);
        }
    }

    /**
     * Get S3 temporary URL for document access
     */
    public function getS3TempUrl(Request $request): JsonResponse
    {
        $request->validate([
            'docURL' => 'required|string',
        ]);

        try {
            // Use the same logic as quote documents for S3 temp URLs
            return $this->quoteDocumentService->getDocumentTempURL($request->docURL);

        } catch (Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error getting S3 temp URL', extra: [
                'error' => $e->getMessage(),
                'docURL' => $request->docURL,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to access document.',
            ], 500);
        }
    }

    /**
     * Placeholder for AI message optimization
     */
    private function optimizeMessageWithAI(string $message): string
    {
        // This is a placeholder implementation
        // In real implementation, this would integrate with an AI service

        $optimizedMessage = "Dear Customer,\n\n";
        $optimizedMessage .= 'Thank you for your patience regarding your claim. ';
        $optimizedMessage .= trim($message);
        $optimizedMessage .= "\n\nIf you have any questions, please don't hesitate to contact us.";
        $optimizedMessage .= "\n\nBest regards,\nClaims Team";

        return $optimizedMessage;
    }
}
