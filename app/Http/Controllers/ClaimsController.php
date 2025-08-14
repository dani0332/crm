<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\ClaimDetailsUpdateRequest;
use App\Http\Requests\ClaimStoreRequest;
use App\Http\Requests\ClaimUpdateRequest;
use App\Http\Requests\SearchPoliciesRequest;
use App\Http\Requests\ClaimStatusUpdateRequest;
use App\Models\Claim;
use App\Services\ClaimsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Inertia\Inertia;
use Inertia\Response;
use App\Services\Logger\LoggerService;

class ClaimsController extends Controller
{
    protected ClaimsService $claimsService;

    public function __construct(
        ClaimsService $claimsService,
    ) {
        $this->claimsService = $claimsService;
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_LIST], ['only' => ['index']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_CREATE], ['only' => ['create', 'store']]);
        $this->middleware(['permission:'.PermissionsEnum::CLAIM_EDIT], ['only' => ['edit', 'update', 'updateClaimDetails']]);
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
            LoggerService::error('Error loading claims index', extra: [
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
            LoggerService::error('Error loading claims create form', extra: [
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
            LoggerService::error('Error searching policies', extra: [
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

            if (! empty($claim->errors)) {
                vAbort($claim->errors);
            }
            return redirect()->route('claims.show', $claim->claimUID)->with('success', "Claim {$claim->claimUID} has been created successfully.");
        } catch (Exception $e) {
            LoggerService::warning('Error creating claim', extra:[
                'error' => $e->getMessage(),
                'data' => $request->validated(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors( $e->getMessage());
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
            //dd($claimRequest->toArray());
            // Get related data for the show page
            $dropdownData = $this->claimsService->getDropdownData();

            return Inertia::render('Claims/Show', [
                'claim' => $claimRequest,
                'dropdowns' => $dropdownData,
                'requiredFieldsFilled' => $this->claimsService->isRequiredFieldsFilled($claimRequest),
            ]);

        } catch (Exception $e) {
            LoggerService::error('Error loading claim request details', extra: [
                'error' => $e->getMessage(),
                'claim_request_id' => $uuid,
                'user_id' => auth()->id(),
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
            LoggerService::error('Error loading claim request edit form', extra: [
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
    public function update(ClaimUpdateRequest $request, $uuid): RedirectResponse
    {
        try {
            $updatedClaimRequest = $this->claimsService->updateClaim($uuid, $request->validated());

            return redirect()->route('claims.show', $updatedClaimRequest->uuid)
                ->with('success', "Claim request {$updatedClaimRequest->code} has been updated successfully.");

        } catch (Exception $e) {
            LoggerService::error('Error updating claim request', extra: [
                'error' => $e->getMessage(),
                'claim_request_id' => $uuid,
                'data' => $request->validated(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors( $e->getMessage());
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
            LoggerService::error('Error updating claim details', extra: [
                'error' => $e->getMessage(),
                'claim_request_id' => $uuid,
                'data' => $request->validatedForUpdate(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors($e->getMessage());
        }
    }

    public function updateClaimStatuses(ClaimStatusUpdateRequest $request, $uuid): RedirectResponse
    {
        try {
            $updatedClaimRequest = $this->claimsService->updateClaimStatus($uuid, $request->validated());
            
            return redirect()->back()->with('success', 'Claim status updated successfully.');
            
        } catch (Exception $e) {
            LoggerService::error('Error updating claim status', extra: [
                'error' => $e->getMessage(),
                'claim_request_id' => $uuid,
                'data' => $request->validated(),
                'user_id' => auth()->id(),
            ]);
            
            return redirect()->back()->with('error', 'Failed to update claim status. Please try again.');
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
            LoggerService::error('Error exporting claims', extra: [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to export claims data.');
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
            LoggerService::error('Error optimizing message', extra: [
                'error' => $e->getMessage(),
                'message' => $request->message,
                'user_id' => auth()->id(),
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
            LoggerService::error('Error sending notification', extra: [
                'error' => $e->getMessage(),
                'claim_id' => $claim->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification.',
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
