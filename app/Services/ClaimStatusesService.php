<?php

namespace App\Services;

use App\Enums\ClaimsEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Facades\CustomerPortalApiFacade;
use App\Facades\InstantWriterAIFacade;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\ClaimActivity;
use App\Models\ClaimRequest;
use App\Models\ClaimRequestDetail;
use App\Models\ClaimStatus;
use App\Models\DocumentType;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Models\User;
use App\Models\YearOfManufacture;
use App\Services\Logger\LoggerService;
use App\Traits\CentralTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ClaimStatusesService extends BaseService
{
    /**
     * Get claim sub-statuses from ClaimStatus
     */
    public function getClaimSubStatuses(): array
    {
        return ClaimStatus::where('status_type', ClaimsEnum::CLAIM_STATUSES_SUB_STATUS_KEY->value)->where('is_active', 1)->select('id', 'text', 'quote_type_id')->orderBy('sort_order')->get()->toArray();
    }

    /**
     * Get claim complaint statuses from ClaimStatus
     */
    public function getClaimComplaintStatuses(): array
    {
        return ClaimStatus::where('status_type', ClaimsEnum::CLAIM_STATUSES_COMPLAINT_STATUS_KEY->value)->where('is_active', 1)->select('id', 'text')->orderBy('sort_order')->get()->toArray();
    }

    /**
     * Get claim statuses from ClaimStatus
     */
    public function getClaimStatuses(): array
    {
        return ClaimStatus::where('status_type', ClaimsEnum::CLAIM_STATUSES_STATUS_KEY->value)->where('is_active', 1)->select('id', 'text', 'quote_type_id')->orderBy('sort_order')->get()->toArray();
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
            $claimInitiatedStatus = ClaimStatus::where('text', $claimRegisterStatusKey)->where('quote_type_id', $claimRequest->quote_type_id)->where('is_active', 1)->where('status_type', ClaimsEnum::CLAIM_STATUSES_SUB_STATUS_KEY->value)->first();

            // If no specific status found for the quote type, try to find a general one
            if (! $claimInitiatedStatus) {
                $claimInitiatedStatus = ClaimStatus::where('text', $claimRegisterStatusKey)->whereNull('quote_type_id')->where('is_active', 1)->where('status_type', ClaimsEnum::CLAIM_STATUSES_SUB_STATUS_KEY->value)->first();
            }

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

        return in_array($newClaimStatus?->text, $subStatusListForClaimClosed);
    }

    public function markClaimAsOpen(ClaimRequest $claimRequest): void
    {
        $claimStatusOpen = ClaimStatus::where('text', ClaimsEnum::CLAIM_STATUS_OPEN->value)->where('is_active', 1)->first();
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
        $claimStatusClosed = ClaimStatus::where('text', ClaimsEnum::CLAIM_STATUS_CLOSED->value)->where('is_active', 1)->first();
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
    public function updateClaimStatus($claimRequest, $request): ClaimRequest
    {
        try {
            // Prepare the status update data
            $statusUpdateData['claim_status_id'] = $request->claim_status_id;
            $notes = $request->notes;

            ClaimActivity::createForClaim($claimRequest->id, $claimRequest->uuid, $request->claim_status_id, $notes);

            // Update the claim request
            $claimRequest->update($statusUpdateData);

            LoggerService::info(' Claim status updated successfully - Claim UUID: '.$claimRequest->uuid, extra: [
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

            return $claimRequest->fresh(['claimStatus', 'claimSubStatus', 'manager']);

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

        $closedStatus = ClaimStatus::where('id', $statusId)->where('is_active', 1)->first();

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
            // Simple query - frontend will process old sub-status from chronological order
            $claimSubStatusLogs = DB::table('claim_activities as ca')
                ->join('claim_statuses as cs', 'ca.status_id', '=', 'cs.id')
                ->join('users as u', 'ca.created_by_id', '=', 'u.id')
                ->select(
                    'ca.created_at as ModifiedAt',
                    'u.name as ModifiedBy',
                    'cs.text as NewSubStatus',
                    'ca.comment as Notes',
                    'ca.created_at as created_at' // Include for frontend sorting
                )
                ->where('ca.claim_request_id', $claimId)
                ->where('cs.status_type', ClaimsEnum::CLAIM_STATUSES_SUB_STATUS_KEY->value) // Only get sub-statuses (not status_type statuses)
                ->whereNotNull('ca.status_id')
                ->orderBy('ca.created_at', 'asc') // Order chronologically for frontend processing
                ->get();

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
            $newComplaintStatus = ClaimStatus::where('id', $complaintStatusId)->where('is_active', 1)->first();

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
}
