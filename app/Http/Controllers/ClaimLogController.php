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

class ClaimLogController extends Controller
{
    protected $cdnPath;
    protected ClaimsService $claimsService;
    protected ClaimStatusesService $claimsStatusesService;
    protected QuoteDocumentService $quoteDocumentService;
    protected CustomerService $customerService;

    public function __construct(
        ClaimsService $claimsService,
        ClaimStatusesService $claimsStatusesService,
    ) {
        $this->claimsService = $claimsService;
        $this->claimsStatusesService = $claimsStatusesService;
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_SHOW], ['only' => ['getClaimLeadHistory', 'getClaimSubStatusLogs', 'getComplaintStatusLogs', 'getNextFollowUpLogs']]);
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

}
