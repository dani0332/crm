<?php

namespace App\Http\Controllers;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PermissionsEnum;
use App\Exports\ClaimsExport;
use App\Http\Requests\ClaimComplaintStatusUpdateRequest;
use App\Http\Requests\ClaimDetailsUpdateRequest;
use App\Http\Requests\ClaimDocumentRequest;
use App\Http\Requests\ClaimExportValidationRequest;
use App\Http\Requests\ClaimGetS3TempUrlRequest;
use App\Http\Requests\ClaimMakeAdditionalContactPrimaryRequest;
use App\Http\Requests\ClaimNextFollowUpUpdateRequest;
use App\Http\Requests\ClaimOptimizeMessageRequest;
use App\Http\Requests\ClaimSearchRequest;
use App\Http\Requests\ClaimSendNotificationRequest;
use App\Http\Requests\ClaimStatusUpdateRequest;
use App\Http\Requests\ClaimStoreRequest;
use App\Http\Requests\ClaimUpdateRequest;
use App\Http\Requests\SearchPoliciesRequest;
use App\Models\ClaimRequest;
use App\Models\QuoteDocument;
use App\Services\ClaimDocumentService;
use App\Services\ClaimsService;
use App\Services\ClaimStatusesService;
use App\Services\CustomerService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ClaimsController extends Controller
{
    protected $cdnPath;
    protected ClaimsService $claimsService;
    protected ClaimDocumentService $claimDocumentService;
    protected ClaimStatusesService $claimsStatusesService;
    protected QuoteDocumentService $quoteDocumentService;
    protected CustomerService $customerService;

    public function __construct(
        ClaimsService $claimsService,
        ClaimStatusesService $claimsStatusesService,
        ClaimDocumentService $claimDocumentService,
        QuoteDocumentService $quoteDocumentService,
        CustomerService $customerService,
    ) {
        $this->claimsService = $claimsService;
        $this->claimsStatusesService = $claimsStatusesService;
        $this->claimDocumentService = $claimDocumentService;
        $this->quoteDocumentService = $quoteDocumentService;
        $this->customerService = $customerService;
        $this->cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';

        $this->middleware(['permission:'.PermissionsEnum::CLAIM_LIST], ['only' => ['index']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_CREATE], ['only' => ['create', 'store']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_EDIT], ['only' => ['edit', 'update', 'updateClaimDetails']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_SHOW], ['only' => ['show']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIMS_EXPORT_DATA], ['only' => ['export']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_DOCUMENT_UPLOAD], ['only' => ['storeDocument']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_DOCUMENT_DELETE], ['only' => ['destroyDocument']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_DOCUMENT_S3_URL], ['only' => ['getS3TempUrl']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_DOWNLOAD_ALL_DOCUMENTS], ['only' => ['downloadAllDocuments']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_SHOW], ['only' => ['getClaimLeadHistory', 'getClaimSubStatusLogs']]);
    }

    /**
     * Display a listing of claims
     */
    public function index(ClaimSearchRequest $request): Response
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::CLAIM_LIST);
        try {
            $claims = $this->claimsService->getClaimsData($request->safe());

            $claimDropdownOptions = $this->claimsService->getDropdownData($request->safe());

            return Inertia::render('Claims/Index', [
                'claims' => $claims,
                'filters' => $this->claimsService->getFilters($request),
                'claimDropdownOptions' => $claimDropdownOptions,
            ]);
        } catch (Exception $e) {
            LoggerService::error(' Error loading claims index', extra: [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return Inertia::render('Claims/Index', [
                'claims' => collect([]),
                'filters' => [],
                'claimDropdownOptions' => [],
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
            return Inertia::render('Claims/Create', [
                'dropdowns' => $this->claimsService->getDropdownData(null),
            ]);
        } catch (Exception $e) {
            LoggerService::error(' Error loading claims create form', extra: [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('claims.index')->with('error', 'Failed to load create form.');
        }
    }

    /**
     * Search active policies (AJAX endpoint)
     */
    public function searchPolicies(SearchPoliciesRequest $request): JsonResponse
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::CLAIM_SEARCH_POLICIES);
        try {
            $policies = $this->claimsService->searchActivePolicies($request->safe());

            return response()->json([
                'success' => true,
                'policies' => $policies,
                'message' => empty($policies) ? 'No available data' : 'Policies found successfully.',
            ]);
        } catch (Exception $e) {
            LoggerService::error(' Error searching policies', extra: [
                'error' => $e->getMessage(),
                'data' => $request->safe(),
                'user_id' => Auth::id(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to search policies.'], 500);
        }
    }

    /**
     * Store a newly created claim
     */
    public function store(ClaimStoreRequest $request)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::CLAIM_CREATION);
        try {
            $claim = $this->claimsService->createClaim($request->safe());

            if (! $claim['success']) {
                vAbort($claim['errors']);
            }

            return redirect()->route('claims.show', $claim['claimUID'])->with('success', "Claim {$claim['claimUID']} has been created successfully.");
        } catch (Exception $e) {
            LoggerService::warning(' Error creating claim', extra: [
                'error' => $e->getMessage(),
                'data' => $request->safe(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->withInput()->withErrors($e->getMessage());
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
            $customerAdditionalContacts = $this->customerService->getAdditionalContacts($claimRequest->customer_id, $claimRequest->mobile_no);

            $documents = $claimRequest->documents->load('createdBy:id,name');

            return Inertia::render('Claims/Show', [
                'claim' => $claimRequest,
                'documents' => $documents,
                'dropdowns' => $dropdownData,
                'additionalContacts' => $customerAdditionalContacts,
                'requiredFieldsFilled' => $requiredFieldsFilled,
                'claimDocumentTypes' => $claimDocumentTypes,
                'cdnPath' => $this->cdnPath,
                'storageUrl' => storageUrl(),
            ]);

        } catch (Exception $e) {
            LoggerService::error(' Error loading claim request details - Claim UUID: '.$uuid, extra: [
                'error' => $e->getMessage(),
                'claim_request_id' => $uuid,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('claims.index')->with('error', $e->getMessage());
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
            LoggerService::error(' Error loading claim request edit form - Claim UUID: '.$uuid, extra: [
                'error' => $e->getMessage(),
                'claim_request_uuid' => $uuid,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('claims.show', $uuid)->with('error', $e->getMessage());
        }
    }

    /**
     * Update the specified claim request
     */
    public function update(ClaimUpdateRequest $request, $uuid): RedirectResponse
    {
        LoggerService::startQuoteLogging($uuid, LoggerFeatureEnum::CLAIM_UPDATE);
        try {
            $updatedClaimRequest = $this->claimsService->updateClaim($uuid, $request->safe());

            return redirect()->route('claims.show', $updatedClaimRequest->uuid)->with('success', "Claim request {$updatedClaimRequest->code} has been updated successfully.");

        } catch (Exception $e) {
            LoggerService::error(' Error updating claim request - Claim UUID: '.$uuid, extra: [
                'error' => $e->getMessage(),
                'claim_request_id' => $uuid,
                'data' => $request->safe(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->withInput()->withErrors($e->getMessage());
        }
    }

    public function updateClaimDetails(ClaimDetailsUpdateRequest $request, ClaimRequest $claim): RedirectResponse
    {
        LoggerService::startQuoteLogging($claim, LoggerFeatureEnum::CLAIM_DETAILS_UPDATE);
        try {
            $updatedClaimRequest = $this->claimsService->updateClaimDetails($claim, $request->safe());

            return redirect()->back()->with('success', "Claim request {$updatedClaimRequest->code} has been updated successfully.");

        } catch (Exception $e) {
            LoggerService::error(' Error updating claim details - Claim UUID: '.$claim->uuid, extra: [
                'error' => $e->getMessage(),
                'claim_request_id' => $claim->uuid,
                'data' => $request->safe(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->withInput()->withErrors($e->getMessage());
        }
    }

    public function updateClaimStatus(ClaimStatusUpdateRequest $request, ClaimRequest $claim): RedirectResponse
    {
        LoggerService::startQuoteLogging($claim, LoggerFeatureEnum::CLAIM_STATUS_UPDATE);
        try {
            $this->claimsStatusesService->updateClaimStatus($claim, $request->safe());

            return redirect()->back()->with('success', 'Claim status updated successfully.');

        } catch (Exception $e) {
            LoggerService::error(' Error updating claim status - Claim UUID: '.$claim->uuid, extra: [
                'error' => $e->getMessage(),
                'claim_request_id' => $claim->uuid,
                'data' => $request->safe(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->with('error', 'Failed to update claim status. Please try again.');
        }
    }

    /**
     * Export claims data
     */
    public function export(ClaimExportValidationRequest $request)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::CLAIM_EXPORT);
        try {
            $requestParams = $request->safe();

            // Check export type for email vs download
            /*if ($request->input('exportType') === 'email') {
                $requestParams->recipientEmail = auth()->user()->email;

                return app(ClaimsExport::class, [
                    'claimsService' => app(ClaimsService::class),
                    'requestParams' => $requestParams,
                ])->emailCSV('Claims-List', $requestParams);
            }*/

            return app(ClaimsExport::class, [
                'claimsService' => app(ClaimsService::class),
                'requestParams' => $requestParams,
            ])->download('Claims-List');

        } catch (Exception $e) {
            LoggerService::error(' Error exporting claims data', extra: [
                'error' => $e->getMessage(),
                'export_params' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            if ($request->input('exportType') === 'email') {
                return response()->json(['success' => false, 'message' => 'Failed to initiate claims export. Please try again.'], 500);
            }

            return redirect()->back()->with('error', 'Failed to export claims data. Please try again.');
        }
    }

    /**
     * AI optimize message (AJAX endpoint)
     */
    public function optimizeMessage(ClaimOptimizeMessageRequest $request): JsonResponse
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::CLAIM_OPTIMIZE_MESSAGE);
        try {
            $optimizedMessageResponse = $this->claimsService->optimizeMessageWithAI($request->safe());

            if (! $optimizedMessageResponse->success) {
                return response()->json(['status' => false, 'message' => $optimizedMessageResponse->error], 500);
            }

            return response()->json([
                'status' => true,
                'optimized_message' => $optimizedMessageResponse->optimized_message,
            ], 200);
        } catch (Exception $e) {
            LoggerService::error(' Error optimizing message', extra: [
                'error' => $e->getMessage(),
                'request' => $request->safe(),
                'user_id' => Auth::id(),
            ]);

            return response()->json(['status' => false, 'message' => 'Failed to optimize message.'], 500);
        }
    }

    /**
     * Send notification to customer (AJAX endpoint)
     */
    public function sendNotification(ClaimSendNotificationRequest $request, ClaimRequest $claim): JsonResponse
    {
        LoggerService::startQuoteLogging($claim, LoggerFeatureEnum::CLAIM_SEND_NOTIFICATION);
        try {
            $this->claimsService->sendNotification($claim, $request->safe());

            return response()->json(['success' => true, 'message' => 'Notification sent successfully.']);
        } catch (Exception $e) {
            LoggerService::error(' Error sending notification', extra: [
                'error' => $e->getMessage(),
                'claim_uuid' => $claim->uuid,
                'user_id' => Auth::id(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to send notification.'], 500);
        }
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
            return $this->quoteDocumentService->getDocumentTempURL($request->safe()->docURL);

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

    /**
     * Get claim lead history (AJAX endpoint)
     */
    public function getClaimLeadHistory(ClaimRequest $claim): JsonResponse
    {
        try {
            $history = $this->claimsService->getClaimLeadHistory($claim->id);

            return response()->json($history);

        } catch (Exception $e) {
            LoggerService::error(' Error fetching claim lead history', extra: [
                'error' => $e->getMessage(),
                'claim_uuid' => $claim->uuid,
                'claim_id' => $claim->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to load claim lead history.'], 500);
        }
    }

    /**
     * Get claim sub-status logs (AJAX endpoint)
     */
    public function getClaimSubStatusLogs(ClaimRequest $claim): JsonResponse
    {
        try {
            $logs = $this->claimsStatusesService->getClaimSubStatusLogs($claim->id);

            return response()->json($logs);

        } catch (Exception $e) {
            LoggerService::error(' Error fetching claim sub-status logs', extra: [
                'error' => $e->getMessage(),
                'claim_uuid' => $claim->uuid,
                'claim_id' => $claim->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to load claim sub-status logs.'], 500);
        }
    }

    /**
     * Update complaint status for a claim
     */
    public function updateComplaintStatus(ClaimComplaintStatusUpdateRequest $request, ClaimRequest $claim)
    {
        LoggerService::startQuoteLogging($claim, LoggerFeatureEnum::CLAIM_COMPLAINT_STATUS_UPDATE);
        try {
            $validated = $request->safe();

            // Update complaint status using service
            $this->claimsStatusesService->updateComplaintStatus(
                $claim,
                $validated->complaint_status_id,
                $validated->complaint_datetime,
                $validated->notes
            );

            LoggerService::info(' Complaint status updated successfully', extra: [
                'claim_uuid' => $claim->uuid,
                'complaint_status_id' => $validated->complaint_status_id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('claims.show', $claim->uuid)->with('success', 'Complaint status updated successfully.');

        } catch (Exception $e) {
            LoggerService::error(' Error updating complaint status', extra: [
                'error' => $e->getMessage(),
                'claim_uuid' => $claim->uuid,
                'request_data' => $request->safe(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('claims.show', $claim->uuid)->with('error', 'Failed to update complaint status.');
        }
    }

    /**
     * Update next follow-up for a claim
     */
    public function updateNextFollowUp(ClaimNextFollowUpUpdateRequest $request, ClaimRequest $claim)
    {
        LoggerService::startQuoteLogging($claim, LoggerFeatureEnum::CLAIM_NEXT_FOLLOW_UP_UPDATE);
        try {
            // Update next follow-up using service
            $this->claimsService->updateNextFollowUp($claim, $request->safe());

            LoggerService::info(' Next follow-up updated successfully', extra: [
                'claim_uuid' => $claim->uuid,
                'data' => $request->safe(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('claims.show', $claim->uuid)->with('success', 'Next follow-up updated successfully.');

        } catch (Exception $e) {
            LoggerService::error(' Error updating next follow-up', extra: [
                'error' => $e->getMessage(),
                'claim_uuid' => $claim->uuid,
                'data' => $request->safe(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('claims.show', $claim->uuid)->with('error', 'Failed to update next follow-up.');
        }
    }

    /**
     * Get complaint status logs for a claim
     */
    public function getComplaintStatusLogs(ClaimRequest $claim): JsonResponse
    {
        try {
            $complaintStatusLogs = $this->claimsStatusesService->getComplaintStatusLogs($claim->id);

            LoggerService::info(' Complaint status logs retrieved successfully', extra: [
                'claim_uuid' => $claim->uuid,
                'total_records' => count($complaintStatusLogs),
                'user_id' => Auth::id(),
            ]);

            return response()->json($complaintStatusLogs);

        } catch (Exception $e) {
            LoggerService::error(' Error retrieving complaint status logs', extra: [
                'error' => $e->getMessage(),
                'claim_uuid' => $claim->uuid,
                'user_id' => Auth::id(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to load complaint status logs.'], 500);
        }
    }

    /**
     * Get next follow-up logs for a claim
     */
    public function getNextFollowUpLogs(ClaimRequest $claim): JsonResponse
    {
        try {
            $nextFollowUpLogs = $this->claimsService->getNextFollowUpLogs($claim->id);

            LoggerService::info(' Next follow-up logs retrieved successfully', extra: [
                'claim_uuid' => $claim->uuid,
                'total_records' => count($nextFollowUpLogs),
                'user_id' => Auth::id(),
            ]);

            return response()->json($nextFollowUpLogs);

        } catch (Exception $e) {
            LoggerService::error(' Error retrieving next follow-up logs', extra: [
                'error' => $e->getMessage(),
                'claim_uuid' => $claim->uuid,
                'user_id' => Auth::id(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to load next follow-up logs.'], 500);
        }
    }

    /**
     * Make additional contact primary for claim request
     */
    public function makeAdditionalContactPrimary(ClaimMakeAdditionalContactPrimaryRequest $request, ClaimRequest $claim)
    {
        LoggerService::startQuoteLogging($claim, LoggerFeatureEnum::CLAIM_MAKE_ADDITIONAL_CONTACT_PRIMARY);
        try {
            $validated = $request->safe();

            // Use the CustomerService to make the contact primary
            $this->customerService->makeAdditionalContactPrimary($claim, $validated->key, $validated->value);

            LoggerService::info(' Additional contact made primary for claim', extra: [
                'claim_uuid' => $claim->uuid,
                'key' => $validated->key,
                'value' => $validated->value,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('claims.show', $claim->uuid)->with('success', 'Primary contact updated successfully.');

        } catch (Exception $e) {
            LoggerService::error(' Error making additional contact primary', extra: [
                'error' => $e->getMessage(),
                'claim_uuid' => $claim->uuid,
                'request_data' => $request->safe(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('claims.show', $claim->uuid)->with('error', 'Failed to update primary contact.');
        }
    }

}
