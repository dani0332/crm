<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\ClaimStoreRequest;
use App\Http\Requests\ClaimUpdateRequest;
use App\Http\Requests\SearchPoliciesRequest;
use App\Models\Claim;
use App\Models\ClaimRequest;
use App\Services\ClaimsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ClaimsController extends Controller
{
    protected ClaimsService $claimsService;

    public function __construct(
        ClaimsService $claimsService,
    ) {
        $this->claimsService = $claimsService;
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_LIST], ['only' => ['index']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_CREATE], ['only' => ['create', 'store']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_EDIT], ['only' => ['edit', 'update']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_SHOW], ['only' => ['show']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIMS_EXPORT_DATA], ['only' => ['export']]);
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
            Log::error('Error loading claims index', [
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
            Log::error('Error loading claims create form', [
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
            Log::error('Error searching policies', [
                'error' => $e->getMessage(),
                'email' => $request->getEmail(),
                'policy_number' => $request->getPolicyNumber(),
                'quote_type_id' => $request->getQuoteTypeId(),
                'page' => $request->getPage(),
                'user_id' => auth()->id(),
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

            return redirect()->route('claims.show', $claim->claimUID)->with('success', "Claim {$claim->claimUID} has been created successfully.");
        } catch (Exception $e) {
            Log::error('Error creating claim', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create claim. Please try again.');
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

            return Inertia::render('Claims/Show', [
                'claim' => $claimRequest,
                'dropdowns' => $dropdownData,
            ]);
        } catch (Exception $e) {
            Log::error('Error loading claim request details', [
                'error' => $e->getMessage(),
                'claim_request_id' => $claimRequest->id,
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
            Log::error('Error loading claim request edit form', [
                'error' => $e->getMessage(),
                'claim_request_uuid' => $uuid,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('claims.show', $uuid)->with('error', 'Failed to load edit form.');
        }
    }

    /**
     * Update the specified claim request
     */
    public function update(ClaimUpdateRequest $request, ClaimRequest $claimRequest): RedirectResponse
    {
        try {
            $updatedClaimRequest = $this->claimsService->updateClaim($claimRequest, $request->validated());

            return redirect()->route('claims.show', $updatedClaimRequest->id)
                ->with('success', "Claim request {$updatedClaimRequest->code} has been updated successfully.");
        } catch (Exception $e) {
            Log::error('Error updating claim request', [
                'error' => $e->getMessage(),
                'claim_request_id' => $claimRequest->id,
                'data' => $request->validated(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update claim. Please try again.');
        }
    }

    /**
     * Assign claim request to a manager (AJAX endpoint)
     */
    public function assign(Request $request, ClaimRequest $claimRequest): JsonResponse
    {
        // Check permission
        $this->authorize('update', $claimRequest);

        $request->validate([
            'manager_id' => 'required|integer|exists:users,id',
            'manager_type' => 'required|string|in:assigned_claims_manager,claims_manager',
        ]);

        try {
            $updatedClaimRequest = $this->claimsService->assignClaim(
                $claimRequest,
                $request->manager_id,
                $request->manager_type
            );

            return response()->json([
                'success' => true,
                'message' => 'Claim request has been assigned successfully.',
                'claim' => $updatedClaimRequest,
            ]);
        } catch (Exception $e) {
            Log::error('Error assigning claim request', [
                'error' => $e->getMessage(),
                'claim_request_id' => $claimRequest->id,
                'manager_id' => $request->manager_id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign claim.',
            ], 500);
        }
    }

    /**
     * Update claim status (AJAX endpoint)
     */
    public function updateStatus(Request $request, Claim $claim): JsonResponse
    {
        // Check permission
        $this->authorize('update', $claim);

        $request->validate([
            'status' => 'required|string|in:pending,in_progress,resolved,closed,cancelled',
        ]);

        try {
            $updatedClaim = $this->claimsService->updateClaimStatus($claim, $request->status);

            return response()->json([
                'success' => true,
                'message' => 'Claim status has been updated successfully.',
                'claim' => $updatedClaim,
            ]);
        } catch (Exception $e) {
            Log::error('Error updating claim status', [
                'error' => $e->getMessage(),
                'claim_id' => $claim->id,
                'status' => $request->status,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update claim status.',
            ], 500);
        }
    }

    /**
     * Get claims statistics (AJAX endpoint)
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->claimsService->getClaimsStatistics();

            return response()->json([
                'success' => true,
                'statistics' => $statistics,
            ]);
        } catch (Exception $e) {
            Log::error('Error getting claims statistics', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics.',
            ], 500);
        }
    }

    /**
     * Get claims for follow-up (AJAX endpoint)
     */
    public function followUp(): JsonResponse
    {
        try {
            $claims = $this->claimsService->getFollowUpClaims()->get();

            return response()->json([
                'success' => true,
                'claims' => $claims,
            ]);
        } catch (Exception $e) {
            Log::error('Error getting follow-up claims', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get follow-up claims.',
            ], 500);
        }
    }

    /**
     * Export claims data
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Check permission
        $this->authorize('viewAny', Claim::class);

        try {
            // Apply same filters as index
            $query = $this->claimsService->getGridData($this->genericModel, $request);
            $claims = $query->get();

            // Create CSV export
            $filename = 'claims_'.now()->format('Y-m-d_H-i-s').'.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () use ($claims) {
                $file = fopen('php://output', 'w');

                // CSV headers
                fputcsv($file, [
                    'Ref ID',
                    'First Name',
                    'Last Name',
                    'Email',
                    'Phone Number',
                    'Line of Business',
                    'Claim Type',
                    'Claim Sub Status',
                    'Policy Number',
                    'Insurer Claim Number',
                    'Claim Status',
                    'Assigned Claims Manager',
                    'Claims Manager',
                    'Created Date',
                    'Next Follow Up Date',
                    'Plate Number',
                    'Vehicle Make',
                    'Vehicle Model',
                    'Vehicle Year',
                    'Complaint Status',
                ]);

                // CSV data
                foreach ($claims as $claim) {
                    fputcsv($file, [
                        $claim->ref_id,
                        $claim->first_name,
                        $claim->last_name,
                        $claim->email,
                        $claim->phone_number,
                        $claim->line_of_business,
                        $claim->claim_type,
                        $claim->claim_sub_status,
                        $claim->policy_number,
                        $claim->insurer_claim_number,
                        $claim->claim_status,
                        $claim->assigned_claims_manager,
                        $claim->claims_manager,
                        $claim->created_at,
                        $claim->next_follow_up_date,
                        $claim->plate_number,
                        $claim->vehicle_make,
                        $claim->vehicle_model,
                        $claim->vehicle_year,
                        $claim->complaint_status,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (Exception $e) {
            Log::error('Error exporting claims', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to export claims data.');
        }
    }

    /**
     * FRD-specific endpoints
     */

    /**
     * Update complaint status (AJAX endpoint)
     */
    public function updateComplaintStatus(Request $request, Claim $claim): JsonResponse
    {
        // Check permission
        $this->authorize('update', $claim);

        $request->validate([
            'status' => 'required|string|in:N/A,Complaint Open,Complaint Closed',
            'notes' => 'nullable|string',
        ]);

        try {
            $updatedClaim = $this->claimsService->updateComplaintStatus(
                $claim,
                $request->status,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Complaint status has been updated successfully.',
                'claim' => $updatedClaim,
            ]);
        } catch (Exception $e) {
            Log::error('Error updating complaint status', [
                'error' => $e->getMessage(),
                'claim_id' => $claim->id,
                'status' => $request->status,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update complaint status.',
            ], 500);
        }
    }

    /**
     * Set next follow-up date (AJAX endpoint)
     */
    public function setNextFollowUp(Request $request, Claim $claim): JsonResponse
    {
        // Check permission
        $this->authorize('update', $claim);

        $request->validate([
            'date' => 'required|date|after:today|before:'.now()->addDays(15)->toDateString(),
            'notes' => 'nullable|string',
        ]);

        try {
            $updatedClaim = $this->claimsService->setNextFollowUp(
                $claim,
                $request->date,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Next follow-up date has been set successfully.',
                'claim' => $updatedClaim,
            ]);
        } catch (Exception $e) {
            Log::error('Error setting next follow-up', [
                'error' => $e->getMessage(),
                'claim_id' => $claim->id,
                'date' => $request->date,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to set next follow-up date.',
            ], 500);
        }
    }

    /**
     * Update claim sub-status with auto-updates (AJAX endpoint)
     */
    public function updateSubStatus(Request $request, Claim $claim): JsonResponse
    {
        // Check permission
        $this->authorize('update', $claim);

        $request->validate([
            'sub_status_id' => 'required|integer|exists:lookups,id',
            'approved_repair_amount' => 'nullable|numeric|min:0',
            'approved_total_loss_amount' => 'nullable|numeric|min:0',
            'approved_cash_loss_amount' => 'nullable|numeric|min:0',
            'claim_denial_reason' => 'nullable|string',
        ]);

        try {
            $updatedClaim = $this->claimsService->updateClaimSubStatus(
                $claim,
                $request->sub_status_id,
                $request->only([
                    'approved_repair_amount',
                    'approved_total_loss_amount',
                    'approved_cash_loss_amount',
                    'claim_denial_reason',
                ])
            );

            return response()->json([
                'success' => true,
                'message' => 'Claim sub-status has been updated successfully.',
                'claim' => $updatedClaim,
            ]);
        } catch (Exception $e) {
            Log::error('Error updating claim sub-status', [
                'error' => $e->getMessage(),
                'claim_id' => $claim->id,
                'sub_status_id' => $request->sub_status_id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update claim sub-status.',
            ], 500);
        }
    }

    /**
     * Update insurer claim number (AJAX endpoint)
     */
    public function updateInsurerClaimNumber(Request $request, Claim $claim): JsonResponse
    {
        // Check permission
        $this->authorize('update', $claim);

        $request->validate([
            'insurer_claim_number' => 'required|string|max:255',
        ]);

        try {
            $updatedClaim = $this->claimsService->updateInsurerClaimNumber(
                $claim,
                $request->insurer_claim_number
            );

            return response()->json([
                'success' => true,
                'message' => 'Insurer claim number has been updated successfully.',
                'claim' => $updatedClaim,
            ]);
        } catch (Exception $e) {
            Log::error('Error updating insurer claim number', [
                'error' => $e->getMessage(),
                'claim_id' => $claim->id,
                'insurer_claim_number' => $request->insurer_claim_number,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update insurer claim number.',
            ], 500);
        }
    }

    /**
     * AI optimize message (AJAX endpoint)
     */
    public function optimizeMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        try {
            // Placeholder for AI optimization logic
            // In real implementation, this would call an AI service
            $optimizedMessage = $this->optimizeMessageWithAI($request->message);

            return response()->json([
                'success' => true,
                'optimized_message' => $optimizedMessage,
            ]);
        } catch (Exception $e) {
            Log::error('Error optimizing message', [
                'error' => $e->getMessage(),
                'message' => $request->message,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize message.',
            ], 500);
        }
    }

    /**
     * Send notification to customer (AJAX endpoint)
     */
    public function sendNotification(Request $request, Claim $claim): JsonResponse
    {
        // Check permission
        $this->authorize('update', $claim);

        $request->validate([
            'message' => 'required|string',
            'send_email' => 'boolean',
            'send_whatsapp' => 'boolean',
        ]);

        try {
            // Placeholder for notification sending logic
            // In real implementation, this would send actual notifications

            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully.',
            ]);
        } catch (Exception $e) {
            Log::error('Error sending notification', [
                'error' => $e->getMessage(),
                'claim_id' => $claim->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification.',
            ], 500);
        }
    }

    /**
     * Get dropdown data for FRD fields (AJAX endpoint)
     */
    public function getDropdownData(): JsonResponse
    {
        try {
            $data = [
                'healthClaimServiceTypes' => $this->claimsService->getHealthClaimServiceTypes(),
                'healthServiceTypes' => $this->claimsService->getHealthServiceTypes(),
                'leadSourceOptions' => $this->claimsService->getLeadSourceOptions(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            Log::error('Error getting dropdown data', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get dropdown data.',
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
