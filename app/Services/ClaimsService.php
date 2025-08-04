<?php

namespace App\Services;

use App\Enums\ClaimsEnum;
use App\Enums\LookupsEnum;
use App\Models\Claim;
use App\Models\ClaimRequest;
use App\Models\ClaimRequestDetail;
use App\Models\Lookup;
use App\Models\QuoteType;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Traits\CentralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClaimsService extends BaseService
{
    use CentralTrait;

    protected $searchPrefix = 'claims.';
    protected $query;

    public function __construct()
    {
        parent::__construct();

        $this->query = ClaimRequest::select([
            'id',
            'uuid',
            'code',
            'incident',
            'first_name',
            'last_name',
            'email',
            'mobile_no',
            'customer_id',
            'source',
            'manager_id',
            'manager_assigned_date',
            'quote_uuid',
            'quote_type_id',
            'personal_quote_id',
            'insurance_provider_id',
            'policy_number',
            'claim_status_id',
            'claim_sub_status_id',
            'claim_type_id',
            'claim_request_type_id',
            'whatsapp_consent',
            'created_at',
        ])
            ->with([
                'quoteType:id,text',
                'claimType:id,text',
                'manager:id,name',
                'claimStatus:id,text',
                'claimSubStatus:id,text',
                'insuranceProvider:id,name',
                'claimRequestType:id,text',
                'claimRequestDetails',
            ]);
    }

    /**
     * Get claims data with flexible filtering options
     */
    public function getClaimsData(Request $request)
    {
        // Apply filters
        $filters = $this->getFilters($request);
        $query = $this->applyFilters($this->query, $filters);

        return $query->simplePaginate(25)->withQueryString();
    }

    /**
     * Get claim request data with flexible filtering options
     */
    public function getClaimById($claimRequestId)
    {
        return $this->query->find($claimRequestId);
    }

    /**
     * Create a new claim request
     */
    public function createClaim(array $data): ClaimRequest
    {
        try {
            DB::beginTransaction();

            // Separate claim request data from detail data
            $claimRequestData = collect($data)->only([
                'uuid', 'code', 'incident', 'incident_story', 'first_name', 'last_name', 'email', 'mobile_no',
                'customer_id', 'source', 'manager_id', 'manager_assigned_date', 'quote_uuid',
                'quote_type_id', 'personal_quote_id', 'insurance_provider_id', 'policy_number',
                'claim_status_id', 'claim_sub_status_id', 'claim_type_id', 'claim_request_type_id',
                'whatsapp_consent', 'selected_policy_id', 'policy_not_listed', 'insurer_claim_number'
            ])->filter()->toArray();

            // Store incident_story as incident field
            if (isset($claimRequestData['incident_story'])) {
                $claimRequestData['incident'] = $claimRequestData['incident_story'];
                unset($claimRequestData['incident_story']);
            }

            $claimRequest = ClaimRequest::create($claimRequestData);

            // Create claim request detail if vehicle info or service type is provided
            $detailData = collect($data)->only([
                'car_make', 'car_model', 'service_type_id', 'request_referrence_number', 'user_ip'
            ])->filter()->toArray();

            if (!empty($detailData)) {
                $detailData['claim_request_id'] = $claimRequest->id;
                ClaimRequestDetail::create($detailData);
            }

            // Log the creation
            LoggerService::info('Claim request created successfully', [
                'claim_request_id' => $claimRequest->id,
                'code' => $claimRequest->code,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return $claimRequest->load(['claimRequestDetails', 'manager', 'claimStatus']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creating claim request', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing claim request
     */
    public function updateClaim(ClaimRequest $claimRequest, array $data): ClaimRequest
    {
        try {
            DB::beginTransaction();

            // Separate claim request data from detail data
            $claimRequestData = collect($data)->only([
                'uuid', 'code', 'incident', 'incident_story', 'first_name', 'last_name', 'email', 'mobile_no',
                'customer_id', 'source', 'manager_id', 'manager_assigned_date', 'quote_uuid',
                'quote_type_id', 'personal_quote_id', 'insurance_provider_id', 'policy_number',
                'claim_status_id', 'claim_sub_status_id', 'claim_type_id', 'claim_request_type_id',
                'whatsapp_consent', 'selected_policy_id', 'policy_not_listed', 'insurer_claim_number'
            ])->filter()->toArray();

            // Store incident_story as incident field
            if (isset($claimRequestData['incident_story'])) {
                $claimRequestData['incident'] = $claimRequestData['incident_story'];
                unset($claimRequestData['incident_story']);
            }

            $claimRequest->update($claimRequestData);

            // Handle claim request detail updates
            $detailData = collect($data)->only([
                'car_make', 'car_model', 'service_type_id', 'request_referrence_number', 'user_ip'
            ])->filter()->toArray();

            if (!empty($detailData)) {
                $detail = $claimRequest->claimRequestDetails()->first();
                if ($detail) {
                    $detail->update($detailData);
                } else {
                    $detailData['claim_request_id'] = $claimRequest->id;
                    ClaimRequestDetail::create($detailData);
                }
            }

            // Log the update
            LoggerService::info('Claim request updated successfully', [
                'claim_request_id' => $claimRequest->id,
                'code' => $claimRequest->code,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return $claimRequest->fresh(['claimRequestDetails', 'manager', 'claimStatus']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating claim request', [
                'error' => $e->getMessage(),
                'claim_request_id' => $claimRequest->id,
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Assign claim request to a manager
     */
    public function assignClaim(ClaimRequest $claimRequest, int $managerId, string $managerType): ClaimRequest
    {
        try {
            DB::beginTransaction();

            // For the new structure, we only have manager_id (unified approach)
            $claimRequest->assignManager($managerId);

            LoggerService::info('Claim request assigned successfully', [
                'claim_request_id' => $claimRequest->id,
                'manager_id' => $managerId,
                'assigned_by' => Auth::id(),
            ]);

            DB::commit();

            return $claimRequest->fresh(['manager', 'claimStatus']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error assigning claim request', [
                'error' => $e->getMessage(),
                'claim_request_id' => $claimRequest->id,
                'manager_id' => $managerId,
            ]);
            throw $e;
        }
    }

    /**
     * Get claims for follow-up
     */
    public function getFollowUpClaims()
    {
        // For the new structure, we don't have follow-up dates yet
        // This would need to be implemented based on business requirements
        return ClaimRequest::where('created_at', '<=', now()->subDays(7));
    }

    /**
     * Get dropdown data for forms
     */
    public function getDropdownData(): array
    {
        return [
            'lineOfBusiness' => $this->getLineOfBusinessOptions(),
            'claimTypes' => $this->getClaimTypes(),
            'claimSubStatuses' => $this->getClaimSubStatuses(),
            'claimsManagers' => $this->getClaimsManagers(),
            'claimStatuses' => ClaimsEnum::getStatuses(),
            'complaintStatuses' => $this->getComplaintStatuses(),
        ];
    }

    /**
     * Get line of business options
     */
    public function getLineOfBusinessOptions(): array
    {
        return QuoteType::select('id', 'text')
            ->where('is_active', 1)
            ->orderBy('text')
            ->get()
            ->toArray();
    }

    /**
     * Get claim types from lookup
     */
    public function getClaimTypes(): array
    {
        return Lookup::where('key', LookupsEnum::CLAIM_TYPES)
            ->where('is_active', 1)
            ->select('id', 'text', 'code')
            ->orderBy('text')
            ->get()
            ->toArray();
    }

    /**
     * Get claim sub-statuses from lookup
     */
    public function getClaimSubStatuses(): array
    {
        return Lookup::where('key', LookupsEnum::CLAIM_SUB_STATUSES->value)
            ->where('is_active', 1)
            ->select('id', 'text', 'code', 'quote_type_id')
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Get sub-status ID by name
     */
    private function getSubStatusByName(string $name): ?int
    {
        $subStatus = Lookup::where('key', LookupsEnum::CLAIM_SUB_STATUSES->value)
            ->where('text', $name)
            ->where('is_active', 1)
            ->first();

        return $subStatus ? $subStatus->id : null;
    }

    /**
     * Get claims managers (users with appropriate roles)
     */
    public function getClaimsManagers(): array
    {
        return User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['Claims Manager', 'Admin', 'SuperAdmin']);
        })
            ->select('id', 'name', 'email')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Get complaint statuses
     */
    public function getComplaintStatuses(): array
    {
        return [
            ['value' => 'none', 'text' => 'No Complaint'],
            ['value' => 'pending', 'text' => 'Complaint Pending'],
            ['value' => 'resolved', 'text' => 'Complaint Resolved'],
            ['value' => 'escalated', 'text' => 'Complaint Escalated'],
        ];
    }



    /**
     * Get claims statistics
     */
    public function getClaimsStatistics(): array
    {
        return [
            'total_claims' => Claim::count(),
            'pending_claims' => Claim::byStatus('pending')->count(),
            'in_progress_claims' => Claim::byStatus('in_progress')->count(),
            'resolved_claims' => Claim::byStatus('resolved')->count(),
            'overdue_claims' => 0,
            'unassigned_claims' => Claim::unassigned()->count(),
        ];
    }

    /**
     * Update claim status
     */
    public function updateClaimStatus(Claim $claim, string $status): Claim
    {
        try {
            DB::beginTransaction();

            $claim->update(['claim_status' => $status]);

            // Log the status change
            LoggerService::info('Claims status updated', [
                'claim_id' => $claim->id,
                'ref_id' => $claim->ref_id,
                'old_status' => $claim->getOriginal('claim_status'),
                'new_status' => $status,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return $claim->fresh();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating claim status', [
                'error' => $e->getMessage(),
                'claim_id' => $claim->id,
                'status' => $status,
            ]);
            throw $e;
        }
    }

    /**
     * Get lead source options
     */
    public function getLeadSourceOptions(): array
    {
        return [
            ['value' => 'Website', 'text' => 'Website'],
            ['value' => 'IMCRM', 'text' => 'IMCRM'],
            ['value' => 'Phone', 'text' => 'Phone'],
            ['value' => 'Email', 'text' => 'Email'],
            ['value' => 'Walk-in', 'text' => 'Walk-in'],
        ];
    }

    /**
     * Update claim sub-status and auto-update related fields
     */
    public function updateClaimSubStatus(Claim $claim, int $subStatusId, ?array $additionalData = null): Claim
    {
        try {
            DB::beginTransaction();

            $claim->updateClaimSubStatus($subStatusId, $additionalData['reason'] ?? null);

            // Handle auto-status updates based on amount fields
            if (isset($additionalData['approved_repair_amount']) && $additionalData['approved_repair_amount'] > 0) {
                $claim->approved_repair_amount = $additionalData['approved_repair_amount'];
                $claim->updateClaimSubStatus($this->getSubStatusByName('Repair approved & work in progress'));
            }

            if (isset($additionalData['approved_total_loss_amount']) && $additionalData['approved_total_loss_amount'] > 0) {
                $claim->approved_total_loss_amount = $additionalData['approved_total_loss_amount'];
                $claim->updateClaimSubStatus($this->getSubStatusByName('Total Loss Offer Letter shared'));
            }

            if (isset($additionalData['approved_cash_loss_amount']) && $additionalData['approved_cash_loss_amount'] > 0) {
                $claim->approved_cash_loss_amount = $additionalData['approved_cash_loss_amount'];
                $claim->updateClaimSubStatus($this->getSubStatusByName('Cash loss approved'));
            }

            if (isset($additionalData['claim_denial_reason']) && ! empty($additionalData['claim_denial_reason'])) {
                $claim->claim_denial_reason = $additionalData['claim_denial_reason'];
                $claim->updateClaimSubStatus($this->getSubStatusByName('Claim denied'));
            }

            $claim->save();

            DB::commit();

            return $claim->fresh();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Update complaint status
     */
    public function updateComplaintStatus(Claim $claim, string $status, ?string $notes = null): Claim
    {
        try {
            DB::beginTransaction();

            $claim->updateComplaintStatus($status, $notes);

            DB::commit();

            return $claim->fresh();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Search active policies by email or policy number
     */
    public function searchActivePolicies(?string $email = null, ?string $policyNumber = null): array
    {
        $policies = [];

        try {
            // Search in PersonalQuote (Car, Health, etc.)
            $personalQuotes = \App\Models\PersonalQuote::query()
                ->when($email, function ($query, $email) {
                    $query->where('email', $email);
                })
                ->when($policyNumber, function ($query, $policyNumber) {
                    $query->where('policy_number', 'LIKE', "%{$policyNumber}%");
                })
                ->whereNotNull('policy_number')
                ->where('policy_number', '!=', '')
                ->whereIn('quote_status_id', [3, 4, 5, 6]) // Active policy statuses
                ->with(['quoteType', 'insuranceProvider'])
                ->limit(10)
                ->get();

            foreach ($personalQuotes as $quote) {
                $policies[] = [
                    'ref_id' => $quote->code,
                    'policy_number' => $quote->policy_number,
                    'customer_name' => $quote->first_name . ' ' . $quote->last_name,
                    'currently_insured_with' => $quote->insuranceProvider->name ?? 'N/A',
                    'product' => $quote->quoteType->text ?? 'Personal Insurance',
                    'policy_expiry_date' => $quote->policy_expiry_date ? date('d-m-Y', strtotime($quote->policy_expiry_date)) : 'N/A',
                    'email' => $quote->email,
                    'mobile_no' => $quote->mobile_no,
                    'quote_type' => 'personal',
                    'quote_id' => $quote->id,
                ];
            }

            // Search in BusinessQuote
            $businessQuotes = \App\Models\BusinessQuote::query()
                ->when($email, function ($query, $email) {
                    $query->where('email', $email);
                })
                ->when($policyNumber, function ($query, $policyNumber) {
                    $query->where('policy_number', 'LIKE', "%{$policyNumber}%");
                })
                ->whereNotNull('policy_number')
                ->where('policy_number', '!=', '')
                ->whereIn('quote_status_id', [3, 4, 5, 6]) // Active policy statuses
                ->with(['insuranceProvider'])
                ->limit(10)
                ->get();

            foreach ($businessQuotes as $quote) {
                $policies[] = [
                    'ref_id' => $quote->code,
                    'policy_number' => $quote->policy_number,
                    'customer_name' => $quote->first_name . ' ' . $quote->last_name,
                    'currently_insured_with' => $quote->insuranceProvider->name ?? 'N/A',
                    'product' => 'Business Insurance',
                    'policy_expiry_date' => $quote->policy_expiry_date ? date('d-m-Y', strtotime($quote->policy_expiry_date)) : 'N/A',
                    'email' => $quote->email,
                    'mobile_no' => $quote->mobile_no,
                    'quote_type' => 'business',
                    'quote_id' => $quote->id,
                ];
            }

            // Log the search
            LoggerService::info('Policy search performed', [
                'email' => $email,
                'policy_number' => $policyNumber,
                'results_count' => count($policies),
                'user_id' => Auth::id(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error searching active policies', [
                'error' => $e->getMessage(),
                'email' => $email,
                'policy_number' => $policyNumber,
            ]);
        }

        return $policies;
    }

    public function applyFilters($query, $filters)
    {
        if (! empty($filters['ref_id'])) {
            $query->where('ref_id', $filters['ref_id']);
        }

        if (! empty($filters['first_name'])) {
            $query->where('first_name', 'like', '%'.$filters['first_name'].'%');
        }

        if (! empty($filters['last_name'])) {
            $query->where('last_name', 'like', '%'.$filters['last_name'].'%');
        }

        if (! empty($filters['email'])) {
            $query->where('email_address', $filters['email']);
        }

        if (! empty($filters['phone_number'])) {
            $query->where('phone_number', $filters['phone_number']);
        }

        if (! empty($filters['claim_status_id'])) {
            $query->where('claims_status_id', $filters['claim_status_id']);
        }

        if (! empty($filters['claim_sub_status_id'])) {
            $query->where('claim_sub_status_id', $filters['claim_sub_status_id']);
        }

        if (! empty($filters['assigned_claims_manager_id'])) {
            $query->where('assigned_claims_manager_id', $filters['assigned_claims_manager_id']);
        }

        if (! empty($filters['line_of_business_id'])) {
            $query->where('line_of_business_id', $filters['line_of_business_id']);
        }

        if (! empty($filters['policy_number'])) {
            $query->where('policy_number', 'like', '%'.$filters['policy_number'].'%');
        }

        if (! empty($filters['plate_number'])) {
            $query->where('plate_number', 'like', '%'.$filters['plate_number'].'%');
        }

        if (! empty($filters['vehicle_make'])) {
            $query->where('vehicle_make', 'like', '%'.$filters['vehicle_make'].'%');
        }

        if (! empty($filters['vehicle_model'])) {
            $query->where('vehicle_model', 'like', '%'.$filters['vehicle_model'].'%');
        }

        if (! empty($filters['vehicle_year'])) {
            $query->where('vehicle_year', $filters['vehicle_year']);
        }

        // Date filtering - handle start date, end date, or both
        if (! empty($filters['created_date_start']) && ! empty($filters['created_date_end'])) {
            $query->whereBetween('created_at', [$filters['created_date_start'], $filters['created_date_end']]);
        }

        return $query;
    }

    public function getFilters(Request $request)
    {
        return $request->only([
            'ref_id',
            'first_name',
            'last_name',
            'email_address',
            'phone_number',
            'created_date_start',
            'created_date_end',
            'claim_status_id',
            'claim_sub_status_id',
            'assigned_claims_manager_id',
            'claims_manager_id',
            'claims_manager_assigned_date',
            'line_of_business_id',
            'plate_number',
            'vehicle_make',
            'vehicle_model',
            'vehicle_year',
            'policy_number',
            'assigned_leads',
            'unassigned_leads',
            'next_follow_up_date',
            'complaint_status',
            'claim_type_id',
            'assigned_to_id',
            'created_at',
        ]);
    }

}
