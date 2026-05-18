<?php

namespace App\Http\Controllers;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PermissionsEnum;
use App\Exports\ClaimsExport;
use App\Http\Requests\ClaimAssignRequest;
use App\Http\Requests\ClaimBulkAssignRequest;
use App\Http\Requests\ClaimComplaintStatusUpdateRequest;
use App\Http\Requests\ClaimDetailsUpdateRequest;
use App\Http\Requests\ClaimExportValidationRequest;
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
use App\Services\ClaimDocumentService;
use App\Services\ClaimsService;
use App\Services\ClaimStatusesService;
use App\Services\CustomerService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClaimsController extends Controller
{
    protected $cdnPath;
    protected $claimEcomTrackingURL;
    protected ClaimsService $claimsService;
    protected ClaimStatusesService $claimsStatusesService;
    protected CustomerService $customerService;
    protected ClaimDocumentService $claimDocumentService;

    public function __construct(
        ClaimsService $claimsService,
        ClaimStatusesService $claimsStatusesService,
        CustomerService $customerService,
        ClaimDocumentService $claimDocumentService,
    ) {
        $this->claimsService = $claimsService;
        $this->claimsStatusesService = $claimsStatusesService;
        $this->customerService = $customerService;
        $this->cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
        $this->claimEcomTrackingURL = config('constants.CLAIM_ECOM_TRACKING_URL').'/';
        $this->claimDocumentService = $claimDocumentService;
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_LIST], ['only' => ['index']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_CREATE], ['only' => ['create', 'store', 'searchPolicies']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_EDIT], ['only' => ['edit', 'update', 'updateClaimDetails', 'updateComplaintStatus', 'updateNextFollowUp', 'makeAdditionalContactPrimary']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIMS_STATUS_UPDATE], ['only' => ['updateClaimStatus']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIMS_SUB_STATUS_UPDATE], ['only' => ['sendNotification', 'optimizeMessage']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_SHOW], ['only' => ['show']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIMS_EXPORT_DATA], ['only' => ['export']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIMS_MANUAL_ASSIGN], ['only' => ['assignClaim', 'bulkAssignClaims']]);
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
                'filters' => $this->claimsService->getFilters($request->safe()),
                'claimDropdownOptions' => $claimDropdownOptions,
            ]);
        } catch (Exception $e) {
            LoggerService::error(' Error loading claims index', extra: [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return Inertia::render('Claims/Index', [
                'claims' => $this->claimsService->getEmptyClaimsPaginator(),
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
                'message' => $policies->isEmpty() ? 'No available data' : 'Policies found successfully.',
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
            LoggerService::error(' Error creating claim', extra: [
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

            if (! $claimRequest) {
                return redirect()->route('claims.index')->with('error', 'Claim not found.');
            }

            // Get related data for the show page
            $dropdownData = $this->claimsService->getDropdownData();

            $claimDocumentTypes = $this->claimDocumentService->getClaimDocumentTypes($claimRequest->quote_type_id, $claimRequest->business_type_of_insurance_id);
            $requiredFieldsFilled = $this->claimsService->isRequiredFieldsFilled($claimRequest);
            $customerAdditionalContacts = $this->customerService->getAdditionalContacts($claimRequest->customer_id, $claimRequest->mobile_no);

            $documents = $claimRequest->documents;

            return Inertia::render('Claims/Show', [
                'claim' => $claimRequest,
                'documents' => $documents,
                'dropdowns' => $dropdownData,
                'additionalContacts' => $customerAdditionalContacts,
                'requiredFieldsFilled' => $requiredFieldsFilled,
                'claimDocumentTypes' => $claimDocumentTypes,
                'cdnPath' => $this->cdnPath,
                'claimEcomTrackingURL' => $this->claimEcomTrackingURL,
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

            if (! $claimRequest) {
                return redirect()->route('claims.index')->with('error', 'Claim not found.');
            }

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
     * Manually assign a claim to a claims manager (Claims Lead feature).
     */
    public function assignClaim(ClaimAssignRequest $request, ClaimRequest $claim): RedirectResponse
    {
        LoggerService::startQuoteLogging($claim, LoggerFeatureEnum::CLAIM_MANUAL_ASSIGN);
        try {
            $this->claimsService->assignClaim($claim, (int) $request->validated('manager_id'));

            return redirect()->back()->with('success', 'Claim assigned successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            LoggerService::error(' Error assigning claim - Claim UUID: '.$claim->uuid, extra: [
                'error' => $e->getMessage(),
                'claim_request_id' => $claim->uuid,
                'manager_id' => $request->validated('manager_id'),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk assign claims to a claims manager (Claims Lead feature, from list page).
     */
    public function bulkAssignClaims(ClaimBulkAssignRequest $request): RedirectResponse
    {
        $claimUuids = $request->validated('claim_uuids');
        $managerId = (int) $request->validated('manager_id');
        $assigned = 0;
        $errors = [];

        foreach ($claimUuids as $uuid) {
            try {
                $claim = $this->claimsService->getClaimByUUID($uuid);
                LoggerService::info(' Bulk assigning claim - UUID: '.$uuid, extra: [
                    'claim_uuid' => $uuid,
                    'manager_id' => $managerId,
                    'user_id' => Auth::id(),
                    'claim' => $claim,
                ]);

                if ($claim) {
                    $this->claimsService->assignClaim($claim, $managerId);
                    LoggerService::info(' Claim assigned successfully - UUID: '.$uuid, extra: [
                        'claim_uuid' => $uuid,
                        'manager_id' => $managerId,
                        'user_id' => Auth::id(),
                    ]);
                    $assigned++;
                }
            } catch (ValidationException $e) {
                $errors[] = $uuid.': '.implode(' ', $e->validator->errors()->all());
            } catch (Exception $e) {
                LoggerService::error(' Error bulk assigning claim - UUID: '.$uuid, extra: [
                    'error' => $e->getMessage(),
                    'user_id' => Auth::id(),
                ]);
                $errors[] = $uuid.': '.$e->getMessage();
            }
        }

        if ($assigned > 0) {
            $message = $assigned === count($claimUuids)
                ? 'All selected claims assigned successfully.'
                : "{$assigned} of ".count($claimUuids).' claims assigned successfully.';
            if (count($errors) > 0) {
                $message .= ' Errors: '.implode('; ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $message .= ' ...';
                }
            }

            return redirect()->back()->with('success', $message);
        }

        return redirect()->back()->with('error', count($errors) > 0 ? implode('; ', $errors) : 'Failed to assign claims.');
    }

    /**
     * Export claims data
     */
    public function export(ClaimExportValidationRequest $request)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::CLAIM_EXPORT);
        try {
            $requestParams = $request->safe();

            return app(ClaimsExport::class, [
                'claimsService' => $this->claimsService,
                'requestParams' => $requestParams,
            ])->download('Claims-List');

        } catch (Exception $e) {
            LoggerService::error(' Error exporting claims data', extra: [
                'error' => $e->getMessage(),
                'export_params' => $request->all(),
                'user_id' => Auth::id(),
            ]);

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
                return response()->json(['success' => false, 'message' => $optimizedMessageResponse->error], 500);
            }

            return response()->json([
                'success' => true,
                'optimized_message' => $optimizedMessageResponse->optimized_message,
            ], 200);
        } catch (Exception $e) {
            LoggerService::error(' Error optimizing message', extra: [
                'error' => $e->getMessage(),
                'request' => $request->safe(),
                'user_id' => Auth::id(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to optimize message.'], 500);
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
