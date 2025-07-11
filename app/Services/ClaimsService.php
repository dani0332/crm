<?php

namespace App\Services;

use App\Enums\LookupsEnum;
use App\Models\Claim;
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

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get claims data with flexible filtering options
     *
     */
    public function getClaimsData(Request $request) {
        $query = Claim::query();

        return $query->paginate(2);
    }

    /**
     * Create a new claim
     */
    public function createClaim(array $data): Claim
    {
        try {
            DB::beginTransaction();

            $claim = Claim::create($data);

            // Log the creation
            LoggerService::info('Claims created successfully', [
                'claim_id' => $claim->id,
                'ref_id' => $claim->ref_id,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return $claim;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creating claim', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing claim
     */
    public function updateClaim(Claim $claim, array $data): Claim
    {
        try {
            DB::beginTransaction();

            $claim->update($data);

            // Log the update
            LoggerService::info('Claims updated successfully', [
                'claim_id' => $claim->id,
                'ref_id' => $claim->ref_id,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return $claim->fresh();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating claim', [
                'error' => $e->getMessage(),
                'claim_id' => $claim->id,
                'data' => $data,
            ]);
            throw $e;
        }
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
            'claimStatuses' => $this->getClaimStatuses(),
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
     * Get available claim statuses
     */
    public function getClaimStatuses(): array
    {
        return [
            ['value' => 'pending', 'text' => 'Pending'],
            ['value' => 'in_progress', 'text' => 'In Progress'],
            ['value' => 'resolved', 'text' => 'Resolved'],
            ['value' => 'closed', 'text' => 'Closed'],
            ['value' => 'cancelled', 'text' => 'Cancelled'],
        ];
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
     * Assign claim to a manager
     */
    public function assignClaim(Claim $claim, int $managerId, string $managerType = 'claims_manager'): Claim
    {
        try {
            DB::beginTransaction();

            $field = $managerType === 'assigned_claims_manager' ? 'assigned_claims_manager_id' : 'claims_manager_id';
            $dateField = $managerType === 'assigned_claims_manager' ? 'assigned_claims_manager_date' : 'claims_manager_assigned_date';

            $claim->update([
                $field => $managerId,
                $dateField => now(),
            ]);

            // Log the assignment
            LoggerService::info('Claims assigned successfully', [
                'claim_id' => $claim->id,
                'ref_id' => $claim->ref_id,
                'manager_id' => $managerId,
                'manager_type' => $managerType,
                'assigned_by' => Auth::id(),
            ]);

            DB::commit();

            return $claim->fresh();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error assigning claim', [
                'error' => $e->getMessage(),
                'claim_id' => $claim->id,
                'manager_id' => $managerId,
            ]);
            throw $e;
        }
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

            if (isset($additionalData['claim_denial_reason']) && !empty($additionalData['claim_denial_reason'])) {
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
    public function searchActivePolicies(string $email = null, string $policyNumber = null): array
    {
        // This would typically search in your policy/quotes tables
        // For now, returning empty array as placeholder
        return [];
    }

}
