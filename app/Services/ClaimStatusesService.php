<?php

namespace App\Services;

use App\Enums\CacheKeyEnum;
use App\Enums\ClaimsEnum;
use App\Enums\QuoteTypeId;
use App\Models\ClaimActivity;
use App\Models\ClaimRequest;
use App\Models\ClaimStatus;
use App\Services\Cache\CacheManager;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClaimStatusesService extends BaseService
{
    /**
     * Get claim sub-statuses from ClaimStatus
     */
    public function getClaimSubStatuses(): array
    {
        return CacheManager::remember(
            CacheKeyEnum::CLAIM_SUB_STATUSES_KEY,
            function () {
                return ClaimStatus::byStatusType(ClaimsEnum::CLAIM_STATUSES_SUB_STATUS_KEY->value)
                    ->active()
                    ->select('id', 'text', 'quote_type_id', 'claim_request_type_id', 'access_type_id')
                    ->orderBySortOrder()
                    ->get()
                    ->toArray();
            }
        );
    }

    /**
     * Get claim complaint statuses from ClaimStatus
     */
    public function getClaimComplaintStatuses(): array
    {
        return CacheManager::remember(
            CacheKeyEnum::CLAIM_COMPLAINT_STATUSES_KEY,
            function () {
                return ClaimStatus::byStatusType(ClaimsEnum::CLAIM_STATUSES_COMPLAINT_STATUS_KEY->value)
                    ->active()
                    ->select('id', 'text')
                    ->orderBySortOrder()
                    ->get()
                    ->toArray();
            }
        );
    }

    /**
     * Get claim statuses from ClaimStatus
     */
    public function getClaimStatuses(): array
    {
        return CacheManager::remember(
            CacheKeyEnum::CLAIM_STATUSES_KEY,
            function () {
                return ClaimStatus::byStatusType(ClaimsEnum::CLAIM_STATUSES_STATUS_KEY->value)
                    ->active()
                    ->select('id', 'text', 'quote_type_id')
                    ->orderBySortOrder()
                    ->get()
                    ->toArray();
            }
        );

    }

    public function updateClaimSubStatusToClaimRegistered(ClaimRequest $claimRequest): void
    {
        try {
            $isCarQuoteType = $claimRequest->quote_type_id == QuoteTypeId::Car;
            $claimRegisterStatusKey = ClaimsEnum::CLAIM_SUB_STATUS_CLAIM_REGISTERED->value;
            if ($isCarQuoteType) {
                $claimRegisterStatusKey = ClaimsEnum::CLAIM_SUB_STATUS_CLAIM_REGISTERED_AWAITING_INSPECTION->value;
            }
            // Find the "Claim initiated" status for the specific quote type
            $claimInitiatedStatus = ClaimStatus::byText($claimRegisterStatusKey)
                ->byQuoteType($claimRequest->quote_type_id)
                ->active()
                ->byStatusType(ClaimsEnum::CLAIM_STATUSES_SUB_STATUS_KEY->value)
                ->first();

            if ($claimInitiatedStatus) {
                // Update the claim sub status without triggering another observer event
                $claimRequest->claim_sub_status_id = $claimInitiatedStatus->id;
                $claimRequest->saveQuietly();

                LoggerService::info(' Claim sub status updated to "Claim registered" - Claim UUID: '.$claimRequest->uuid, extra: [
                    'claim_request_id' => $claimRequest->id,
                    'claim_uuid' => $claimRequest->uuid,
                    'claim_sub_status_id' => $claimInitiatedStatus->id,
                    'quote_type_id' => $claimRequest->quote_type_id,
                    'trigger' => 'claim_number_entered',
                    'updated_by' => Auth::id(),
                ]);
            } else {
                LoggerService::warning(' Could not find "Claim registered" status - Claim UUID: '.$claimRequest->uuid, extra: [
                    'claim_request_id' => $claimRequest->id,
                    'claim_uuid' => $claimRequest->uuid,
                    'quote_type_id' => $claimRequest->quote_type_id,
                ]);
            }
        } catch (\Exception $e) {
            LoggerService::error(' Error updating claim sub status to "Claim registered" - Claim UUID: '.$claimRequest->uuid, extra: [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function checkSubStatusForClaimClosure(ClaimRequest $claimRequest, $newClaimSubStatusId): bool
    {
        $newClaimStatus = ClaimStatus::find($newClaimSubStatusId);

        $isCarQuoteType = $claimRequest->quote_type_id == QuoteTypeId::Car;
        $isHealthQuoteType = $claimRequest->quote_type_id == QuoteTypeId::Health;
        $isLifeQuoteType = $claimRequest->quote_type_id == QuoteTypeId::Life;

        $subStatusListForClaimClosed = [
            ClaimsEnum::CLAIM_SUB_STATUS_CLAIM_PAID->value,
            ClaimsEnum::CLAIM_SUB_STATUS_CLAIM_WITHDRAWN->value,
            ClaimsEnum::CLAIM_SUB_STATUS_CLAIM_DENIED->value,
        ];

        if ($isCarQuoteType) {
            $subStatusListForClaimClosed = [
                ClaimsEnum::CLAIM_SUB_STATUS_REPAIR_COMPLETED_AND_CLAIM_SETTLED->value,
                ClaimsEnum::CLAIM_SUB_STATUS_TOTAL_LOSS_PAID_AND_CLAIM_SETTLED->value,
                ClaimsEnum::CLAIM_SUB_STATUS_CASH_LOSS_PAID_AND_CLAIM_SETTLED->value,
                ClaimsEnum::CLAIM_SUB_STATUS_CLAIM_WITHDRAWN->value,
                ClaimsEnum::CLAIM_SUB_STATUS_CLAIM_DENIED->value,
            ];
        }

        if ($isHealthQuoteType) {
            $subStatusListForClaimClosed = array_merge($subStatusListForClaimClosed, [
                ClaimsEnum::CLAIM_SUB_STATUS_REQUEST_APPROVED->value,
                ClaimsEnum::CLAIM_SUB_STATUS_ANSWERED_AND_CLOSED->value,
            ]);
        }

        if ($isLifeQuoteType) {
            $subStatusListForClaimClosed = [
                ClaimsEnum::CLAIM_SUB_STATUS_CLAIM_PAID->value,
                ClaimsEnum::CLAIM_SUB_STATUS_CLAIM_DENIED->value,
            ];
        }
        
        // convert the subStatusListForClaimClosed to lowercase
        $subStatusListForClaimClosed = array_map('strtolower', $subStatusListForClaimClosed);

        return in_array(strtolower($newClaimStatus?->text), $subStatusListForClaimClosed);
    }

    public function markClaimAsOpen(ClaimRequest $claimRequest): void
    {
        $claimStatusOpen = ClaimStatus::byText(ClaimsEnum::CLAIM_STATUS_OPEN->value)
            ->active()
            ->first();

        if ($claimStatusOpen) {
            $claimRequest->update(['claim_status_id' => $claimStatusOpen->id]);
            LoggerService::info(' Claim status updated to "Open" - Claim UUID: '.$claimRequest->uuid, extra: [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'claim_status_id' => $claimStatusOpen->id,
                'updated_by' => Auth::id(),
            ]);
        }
    }

    public function markClaimAsClosed(ClaimRequest $claimRequest): void
    {
        $claimStatusClosed = ClaimStatus::byText(ClaimsEnum::CLAIM_STATUS_CLOSED->value)
            ->active()
            ->first();

        if ($claimStatusClosed) {
            $claimRequest->updateQuietly(['claim_status_id' => $claimStatusClosed->id]);
            LoggerService::info(' Claim status updated to "Closed" - Claim UUID: '.$claimRequest->uuid, extra: [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'claim_status_id' => $claimStatusClosed->id,
                'updated_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Update claim status and sub status
     */
    public function updateClaimStatus($claimRequest, $request)
    {
        try {
            // Prepare the status update data
            $statusUpdateData['claim_status_id'] = $request->claim_status_id;
            $notes = $request->notes;

            ClaimActivity::createForClaim($claimRequest->id, $claimRequest->uuid, $request->claim_status_id, $notes);

            // Update the claim request
            $claimRequest->update($statusUpdateData);

            LoggerService::info('Claim status updated successfully - Claim UUID: '.$claimRequest->uuid, extra: [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'code' => $claimRequest->code,
                'updated_fields' => array_keys($statusUpdateData),
                'old_claim_status_id' => $claimRequest->getOriginal('claim_status_id'),
                'new_claim_status_id' => $claimRequest->claim_status_id,
                'old_claim_sub_status_id' => $claimRequest->getOriginal('claim_sub_status_id'),
                'new_claim_sub_status_id' => $claimRequest->claim_sub_status_id,
                'updated_by' => Auth::id(),
            ]);

            return true;

        } catch (\Exception $e) {
            LoggerService::error(' Error updating claim status - Claim UUID: '.$claimRequest->uuid, extra: [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'claim_request_id' => $claimRequest->uuid,
                'data' => $request,
                'updated_by' => Auth::id(),
            ]);

            throw $e;
        }
    }

    public function updateClaimSubStatus(ClaimRequest $claimRequest, $claimStatus): void
    {
        if ($claimStatus) {
            $claimRequest->claim_sub_status_id = $claimStatus->id;
            $claimRequest->saveQuietly();
            LoggerService::info(' Claim status updated - Claim UUID: '.$claimRequest->uuid, extra: [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'claim_sub_status_id' => $claimStatus->id,
                'updated_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Check if the given claim status ID represents a closed status
     */
    public function isClaimStatusClosed(?int $statusId): bool
    {
        if (! $statusId) {
            return false;
        }

        $closedStatus = ClaimStatus::active()
            ->find($statusId);

        return $closedStatus?->text === ClaimsEnum::CLAIM_STATUS_CLOSED->value;
    }

    /**
     * Get claim sub-status logs (sub-status changes by claims manager) - all data for client-side pagination
     * Frontend will process old sub-status from chronological data
     *
     * @return array
     */
    public function getClaimSubStatusLogs(int $claimId)
    {
        try {
            // Simple query using Eloquent ORM - frontend will process old sub-status from chronological order
            $claimSubStatusLogs = ClaimActivity::with([
                'claimStatus:id,text,status_type',
                'createdBy:id,name',
            ])
                ->forClaimRequest($claimId)
                ->whereNotNull('status_id')
                ->whereHas('claimStatus', function ($query) {
                    $query->where('status_type', ClaimsEnum::CLAIM_STATUSES_SUB_STATUS_KEY->value);
                })
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($activity) {
                    return [
                        'ModifiedAt' => $activity->created_at,
                        'ModifiedBy' => $activity->createdBy->name ?? null,
                        'NewSubStatus' => $activity->claimStatus->text ?? null,
                        'Notes' => $activity->comment,
                        'created_at' => $activity->created_at, // Include for frontend sorting
                    ];
                });

            return $claimSubStatusLogs;

        } catch (\Exception $e) {
            LoggerService::error(' Error fetching claim sub-status logs', extra: [
                'error' => $e->getMessage(),
                'claim_id' => $claimId,
                'user_id' => Auth::id(),
            ]);

            throw $e;
        }
    }

    /**
     * Update complaint status for a claim
     */
    public function updateComplaintStatus(ClaimRequest $claim, ?int $complaintStatusId, ?string $complaintDatetime = null, ?string $notes = null): ClaimRequest
    {
        try {
            // Update the claim with complaint status
            $claim->updateComplaintStatus($complaintStatusId, $complaintDatetime, $notes);

            // Check if complaint status has changed to open complaint status
            $newComplaintStatus = ClaimStatus::active()->find($complaintStatusId);

            $isNewStatusComplaintOpen = $newComplaintStatus?->text === ClaimsEnum::CLAIM_STATUS_OPEN_COMPLAINT->value;

            if ($isNewStatusComplaintOpen) {
                $this->markClaimAsOpen($claim);
            }

            LoggerService::info(' Complaint status updated successfully', extra: [
                'claim_id' => $claim->id,
                'complaint_status_id' => $complaintStatusId,
                'complaint_datetime' => $complaintDatetime,
                'is_new_status_complaint_open' => $isNewStatusComplaintOpen,
                'user_id' => Auth::id(),
            ]);

            return $claim->fresh();

        } catch (\Exception $e) {
            LoggerService::error(' Error updating complaint status', extra: [
                'error' => $e->getMessage(),
                'claim_id' => $claim->id,
                'complaint_status_id' => $complaintStatusId,
                'user_id' => Auth::id(),
            ]);

            throw $e;
        }
    }

    /**
     * Get complaint status logs for a claim from audit trail
     *
     * @return array
     */
    public function getComplaintStatusLogs(int $claimId)
    {
        $audits = DB::table('audits as a')
            ->select(
                'a.created_at as logged_at',
                DB::raw('(SELECT name from users where id = a.user_id) as logged_by'),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.old_values, '$.complaint_status_id')) AS old_complaint_status_id"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.complaint_status_id')) AS new_complaint_status_id"),
                DB::raw("(SELECT text FROM claim_statuses WHERE id = JSON_UNQUOTE(JSON_EXTRACT(a.old_values, '$.complaint_status_id'))) AS old_complaint_status"),
                DB::raw("(SELECT text FROM claim_statuses WHERE id = JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.complaint_status_id'))) AS new_complaint_status"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.old_values, '$.complaint_datetime')) AS old_complaint_datetime"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.complaint_datetime')) AS new_complaint_datetime"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.old_values, '$.complaint_notes')) AS old_complaint_notes"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.complaint_notes')) AS new_complaint_notes")
            )
            ->where(function ($query) {
                // Only get records where complaint fields were changed
                $query->whereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.complaint_status_id')"))
                    ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.complaint_datetime')"))
                    ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.complaint_notes')"));
            })
            ->where(function ($query) use ($claimId) {
                $query->where('a.auditable_type', 'App\Models\\ClaimRequest')
                    ->where('a.auditable_id', $claimId);
            })
            ->orderBy('a.created_at', 'DESC')
            ->get()
            ->filter(function ($item) {
                // Filter out records where no complaint fields changed
                return ! is_null($item->new_complaint_status_id) ||
                       ! is_null($item->new_complaint_datetime) ||
                       ! is_null($item->new_complaint_notes);
            })
            ->values()
            ->toArray();

        return $audits;
    }
}
