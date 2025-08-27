<?php

declare(strict_types=1);

namespace App\Services\ClaimAllocation;

use App\Enums\AssignmentTypeEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Enums\UserStatusEnum;
use App\Models\Claim;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;
use App\Services\SendEmailCustomerService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ClaimAllocationService implements ClaimAllocationInterface
{
    public function __construct(
        private readonly SendEmailCustomerService $sendEmailCustomerService,
        private readonly LoggerService $loggerService
    ) {}

    /**
     * Execute claim allocation for a given claim UUID
     *
     * @param string $claimUuid
     * @param int|null $teamId
     * @param bool $overrideAdvisorId
     * @param bool $isReAssignment
     * @return array
     */
    public function execute(string $claimUuid, ?int $teamId = null, bool $overrideAdvisorId = false, bool $isReAssignment = false): array
    {
       

        try {
            $this->loggerService->info('ClaimAllocationService - execute: Claim Allocation Started', [
                'claim_uuid' => $claimUuid,
                'team_id' => $teamId,
                'override_advisor_id' => $overrideAdvisorId,
                'is_reassignment' => $isReAssignment,
            ]);

            // Fetch the claim
            $claim = $this->fetchClaim($claimUuid);
            
            if (!$claim) {
                $this->loggerService->info('ClaimAllocationService - execute: Claim not found', [
                    'claim_uuid' => $claimUuid,
                ]);
                
                return $this->createResponse(0, 'Claim not found or not under fetch criteria', Response::HTTP_NOT_FOUND);
            }

            // Check if claim is already assigned
            if ($claim->assigned_to_id && !$overrideAdvisorId) {
                $this->loggerService->info('ClaimAllocationService - execute: Claim already assigned', [
                    'claim_uuid' => $claimUuid,
                    'assigned_to_id' => $claim->assigned_to_id,
                ]);
                
                return $this->createResponse($claim->assigned_to_id, 'Claim already assigned to advisor', Response::HTTP_OK);
            }

            // Check if allocation is in progress
            if ($this->isAllocationInProgress($claim)) {
                $this->loggerService->info('ClaimAllocationService - execute: Allocation already in progress', [
                    'claim_uuid' => $claimUuid,
                ]);
                
                return $this->createResponse(0, 'Claim allocation is already in progress', Response::HTTP_OK);
            }

            // Mark allocation as in progress
            $this->markAllocationInProgress($claim);

            // Find available advisor
            $advisor = $this->fetchAvailableAdvisor($claim, $teamId, $isReAssignment);

            if (!$advisor) {
                $this->loggerService->info('ClaimAllocationService - execute: No advisor found', [
                    'claim_uuid' => $claimUuid,
                ]);
                
                $this->claimAllocationFailed($claimUuid);
                $this->sendNonAdvisorEmail($claim);
                
                return $this->createResponse(0, 'No available advisor found for claim allocation', Response::HTTP_NOT_FOUND);
            }

            // Assign claim to advisor
            $this->assignClaim($claim, $advisor);
            
            $this->loggerService->info('ClaimAllocationService - execute: Claim assigned successfully', [
                'claim_uuid' => $claimUuid,
                'advisor_id' => $advisor->id,
                'advisor_name' => $advisor->name,
            ]);

            return $this->createResponse($advisor->id, 'Claim assigned successfully!', Response::HTTP_OK);

        } catch (Exception $e) {
            $this->loggerService->error('ClaimAllocationService - execute: Exception occurred', [
                'claim_uuid' => $claimUuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->claimAllocationFailed($claimUuid);
            
            return $this->createResponse(0, 'Claim allocation failed: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Fetch claim by UUID
     *
     * @param string $claimUuid
     * @return Claim|null
     */
    private function fetchClaim(string $claimUuid): ?Claim
    {
        return Claim::where('uuid', $claimUuid)
            ->whereNotIn('claims_status_id', [
                // Add status IDs that should not be allocated
                // This will depend on your claims status enum
            ])
            ->first();
    }

    /**
     * Check if allocation is already in progress
     *
     * @param Claim $claim
     * @return bool
     */
    private function isAllocationInProgress(Claim $claim): bool
    {
        // Check if there's an allocation in progress flag
        // You might need to add this field to your claims table
        return false; // Placeholder - implement based on your needs
    }

    /**
     * Mark allocation as in progress
     *
     * @param Claim $claim
     * @return void
     */
    private function markAllocationInProgress(Claim $claim): void
    {
        // Mark allocation as in progress
        // You might need to add this field to your claims table
        // $claim->update(['allocation_in_progress' => true, 'allocation_started_at' => now()]);
    }

    /**
     * Fetch available advisor for claim allocation
     *
     * @param Claim $claim
     * @param int|null $teamId
     * @param bool $isReAssignment
     * @return User|null
     */
    private function fetchAvailableAdvisor(Claim $claim, ?int $teamId = null, bool $isReAssignment = false): ?User
    {
        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (!$isReAssignment) {
            $statusOrder[] = UserStatusEnum::UNAVAILABLE;
        }

        // Get advisor roles based on claim type
        $advisorRoles = $this->getAdvisorRolesForClaim($claim);

        foreach ($statusOrder as $status) {
            $this->loggerService->info('ClaimAllocationService - fetchAvailableAdvisor: Trying to get advisor with status', [
                'status' => $status,
                'claim_uuid' => $claim->uuid,
            ]);

            $eligibleUser = $this->getAdvisorBaseQuery($status, $advisorRoles, $teamId)
                ->where('users.is_active', true)
                ->first();

            if ($eligibleUser) {
                $this->loggerService->info('ClaimAllocationService - fetchAvailableAdvisor: Eligible advisor found', [
                    'status' => $status,
                    'user_id' => $eligibleUser->user_id,
                    'claim_uuid' => $claim->uuid,
                ]);

                return User::find($eligibleUser->user_id);
            }
        }

        $this->loggerService->info('ClaimAllocationService - fetchAvailableAdvisor: No eligible advisor found', [
            'claim_uuid' => $claim->uuid,
        ]);

        return null;
    }

    /**
     * Get advisor roles based on claim type
     *
     * @param Claim $claim
     * @return array
     */
    private function getAdvisorRolesForClaim(Claim $claim): array
    {
        // Determine advisor roles based on claim type
        // This will depend on your business logic and role structure
        $claimType = $claim->typeofinsurance?->name ?? '';
        
        return match (strtolower($claimType)) {
            'car', 'motor' => ['CarClaimAdvisor', 'GeneralClaimAdvisor'],
            'health' => ['HealthClaimAdvisor', 'GeneralClaimAdvisor'],
            'travel' => ['TravelClaimAdvisor', 'GeneralClaimAdvisor'],
            'home' => ['HomeClaimAdvisor', 'GeneralClaimAdvisor'],
            default => ['GeneralClaimAdvisor'],
        };
    }

    /**
     * Build base query for finding eligible advisors
     *
     * @param int $status
     * @param array $roles
     * @param int|null $teamId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getAdvisorBaseQuery(int $status, array $roles, ?int $teamId = null)
    {
        $query = DB::table('users')
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->leftJoin('user_team as ut', 'ut.user_id', '=', 'users.id')
            ->leftJoin('teams as t', 't.id', '=', 'ut.team_id')
            ->select('users.id as user_id', 'users.name', 'users.email')
            ->where('users.status', $status)
            ->whereIn('r.name', $roles)
            ->where('users.is_active', true);

        if ($teamId) {
            $query->where('t.id', $teamId);
        }

        return $query;
    }

    /**
     * Assign claim to advisor
     *
     * @param Claim $claim
     * @param User $advisor
     * @return void
     */
    private function assignClaim(Claim $claim, User $advisor): void
    {
        DB::transaction(function () use ($claim, $advisor) {
            // Update claim assignment
            $claim->update([
                'assigned_to_id' => $advisor->id,
                'assigned_at' => now(),
                'assignment_type' => AssignmentTypeEnum::SYSTEM_ASSIGNED,
            ]);

            // Log the assignment
            $this->loggerService->info('ClaimAllocationService - assignClaim: Claim assigned', [
                'claim_uuid' => $claim->uuid,
                'advisor_id' => $advisor->id,
                'assignment_type' => AssignmentTypeEnum::SYSTEM_ASSIGNED,
            ]);

            // Update advisor allocation count if needed
            $this->updateAdvisorAllocationCount($advisor->id);
        });
    }

    /**
     * Update advisor allocation count
     *
     * @param int $advisorId
     * @return void
     */
    private function updateAdvisorAllocationCount(int $advisorId): void
    {
        // Update the advisor's claim allocation count
        // This might be in a separate table similar to lead_allocation
        // You can implement this based on your needs
    }

    /**
     * Handle failed claim allocation
     *
     * @param string $claimUuid
     * @return void
     */
    private function claimAllocationFailed(string $claimUuid): void
    {
        $this->loggerService->error('ClaimAllocationService - claimAllocationFailed: Claim allocation failed', [
            'claim_uuid' => $claimUuid,
        ]);

        // You might want to update the claim status or create a failed allocation record
    }

    /**
     * Send email notification when no advisor is available
     *
     * @param Claim $claim
     * @return void
     */
    private function sendNonAdvisorEmail(Claim $claim): void
    {
        try {
            // Send notification email to customer service or management
            // This will depend on your email service implementation
            $this->loggerService->info('ClaimAllocationService - sendNonAdvisorEmail: Sending notification email', [
                'claim_uuid' => $claim->uuid,
            ]);
        } catch (Exception $e) {
            $this->loggerService->error('ClaimAllocationService - sendNonAdvisorEmail: Failed to send email', [
                'claim_uuid' => $claim->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create response array
     *
     * @param int $advisorId
     * @param string $message
     * @param int $status
     * @return array
     */
    private function createResponse(int $advisorId, string $message, int $status): array
    {
        return [
            'advisorId' => $advisorId,
            'message' => $message,
            'status' => $status,
        ];
    }

    /**
     * Get claim allocation statistics
     *
     * @param int|null $teamId
     * @return array
     */
    public function getClaimAllocationStats(?int $teamId = null): array
    {
        try {
            $query = Claim::select([
                'claims.id',
                'claims.uuid',
                'claims.assigned_to_id',
                'claims.assigned_at',
                'claims.claims_status_id',
                'u.name as advisor_name',
                'u.email as advisor_email',
                't.name as team_name',
                'cs.name as status_name',
            ])
            ->leftJoin('users as u', 'claims.assigned_to_id', '=', 'u.id')
            ->leftJoin('user_team as ut', 'ut.user_id', '=', 'u.id')
            ->leftJoin('teams as t', 't.id', '=', 'ut.team_id')
            ->leftJoin('claims_status as cs', 'claims.claims_status_id', '=', 'cs.id')
            ->whereNotNull('claims.assigned_to_id');

            if ($teamId) {
                $query->where('t.id', $teamId);
            }

            $claims = $query->orderBy('claims.assigned_at', 'desc')->get();

            return [
                'total_allocated' => $claims->count(),
                'claims' => $claims,
            ];

        } catch (Exception $e) {
            $this->loggerService->error('ClaimAllocationService - getClaimAllocationStats: Failed to get stats', [
                'error' => $e->getMessage(),
                'team_id' => $teamId,
            ]);

            return [
                'total_allocated' => 0,
                'claims' => [],
            ];
        }
    }

    /**
     * Reassign claim to different advisor
     *
     * @param string $claimUuid
     * @param int $newAdvisorId
     * @return array
     */
    public function reassignClaim(string $claimUuid, int $newAdvisorId): array
    {
        try {
            $claim = Claim::where('uuid', $claimUuid)->first();
            
            if (!$claim) {
                return $this->createResponse(0, 'Claim not found', Response::HTTP_NOT_FOUND);
            }

            $newAdvisor = User::find($newAdvisorId);
            if (!$newAdvisor) {
                return $this->createResponse(0, 'New advisor not found', Response::HTTP_NOT_FOUND);
            }

            $oldAdvisorId = $claim->assigned_to_id;
            
            DB::transaction(function () use ($claim, $newAdvisor, $oldAdvisorId) {
                $claim->update([
                    'assigned_to_id' => $newAdvisor->id,
                    'assigned_at' => now(),
                    'assignment_type' => AssignmentTypeEnum::MANUAL_REASSIGNED,
                    'reassigned_from_id' => $oldAdvisorId,
                ]);

                $this->loggerService->info('ClaimAllocationService - reassignClaim: Claim reassigned', [
                    'claim_uuid' => $claim->uuid,
                    'old_advisor_id' => $oldAdvisorId,
                    'new_advisor_id' => $newAdvisor->id,
                ]);
            });

            return $this->createResponse($newAdvisor->id, 'Claim reassigned successfully!', Response::HTTP_OK);

        } catch (Exception $e) {
            $this->loggerService->error('ClaimAllocationService - reassignClaim: Failed to reassign claim', [
                'claim_uuid' => $claimUuid,
                'new_advisor_id' => $newAdvisorId,
                'error' => $e->getMessage(),
            ]);

            return $this->createResponse(0, 'Failed to reassign claim: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
