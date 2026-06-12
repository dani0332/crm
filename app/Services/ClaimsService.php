<?php

namespace App\Services;

use App\Enums\CacheKeyEnum;
use App\Enums\ClaimsEnum;
use App\Enums\InsuranceProviderContactDepartmentEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Facades\CustomerPortalApiFacade;
use App\Facades\InstantWriterAIFacade;
use App\Facades\Ken;
use App\Jobs\SendClaimSubStatusUpdateNotificationEmailJob;
use App\Models\BusinessTypeOfInsurance;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\ClaimActivity;
use App\Models\ClaimRequest;
use App\Models\ClaimRequestDetail;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Models\User;
use App\Models\YearOfManufacture;
use App\Services\Cache\CacheManager;
use App\Services\Logger\LoggerService;
use App\Traits\CentralTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClaimsService extends BaseService
{
    use CentralTrait;

    protected ClaimStatusesService $claimsStatusesService;
    protected LookupService $lookupService;
    protected UserService $userService;
    protected $searchPrefix = 'claims.';
    protected $perPage = 15;

    public function __construct(
        ClaimStatusesService $claimsStatusesService,
        LookupService $lookupService,
        UserService $userService,
    ) {
        parent::__construct();
        $this->claimsStatusesService = $claimsStatusesService;
        $this->lookupService = $lookupService;
        $this->userService = $userService;
    }

    /**
     * Build and return a fresh detailed claim query with all relationships
     */
    protected function buildDetailedClaimListQuery()
    {
        return ClaimRequest::select([
            'id',
            'uuid',
            'code',
            'incident',
            'incident_date',
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
            'business_type_of_insurance_id',
            'personal_quote_id',
            'insurance_provider_id',
            'policy_number',
            'claim_number',
            'claim_status_id',
            'claim_sub_status_id',
            'claim_type_id',
            'claim_request_type_id',
            'complaint_status_id',
            'complaint_datetime',
            'complaint_notes',
            'next_followup_datetime',
            'next_followup_notes',
            'whatsapp_consent',
            'approved_repair_amount',
            'approved_total_loss_amount',
            'approved_cash_loss_amount',
            'claim_decline_reason',
            'created_at',
        ])
            ->with([
                'quoteType:id,code,text',
                'insuranceProvider:id,code,text',
                'claimStatus:id,text',
                'claimSubStatus:id,text',
                'complaintStatus:id,text',
                'claimType:id,code,text',
                'claimRequestType:id,code,text',
                'claimRequestDetails' => function ($query) {
                    $query->with('serviceType:id,code,text');
                },
                'manager:id,name',
            ])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Build and return a fresh detailed claim query with all relationships
     */
    protected function buildDetailedClaimQuery()
    {
        return ClaimRequest::select([
            'id',
            'uuid',
            'code',
            'incident',
            'incident_date',
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
            'business_type_of_insurance_id',
            'personal_quote_id',
            'insurance_provider_id',
            'policy_number',
            'claim_number',
            'claim_status_id',
            'claim_sub_status_id',
            'claim_type_id',
            'claim_request_type_id',
            'complaint_status_id',
            'complaint_datetime',
            'complaint_notes',
            'next_followup_datetime',
            'next_followup_notes',
            'whatsapp_consent',
            'approved_repair_amount',
            'approved_total_loss_amount',
            'approved_cash_loss_amount',
            'claim_decline_reason',
            'created_at',
            'updated_at',
        ])
            ->with([
                'customerBankAccounts',
                'quoteType:id,code,text',
                'claimType:id,code,text',
                'manager:id,name',
                'claimStatus:id,text',
                'claimSubStatus:id,text',
                'complaintStatus:id,text',
                'insuranceProvider:id,code,text',
                'claimRequestType:id,code,text',
                'claimRequestDetails' => function ($query) {
                    $query->with([
                        'serviceType:id,code,text',
                        'tpaOption:id,code,text',
                    ]);
                },
                'personalQuote' => function ($query) {
                    $query->with('advisor:id,name', 'insuranceProvider:id,code,text');
                },
            ]);
    }

    /**
     * Get claims data with flexible filtering options
     */
    public function getClaimsData($request)
    {
        // Use a fresh list query each call so filters never accumulate (same pattern as buildDetailedClaimQuery)
        $filters = $this->getFilters($request);
        $query = $this->applyFilters($this->buildDetailedClaimListQuery(), $filters);

        return $query->simplePaginate($this->perPage)->withQueryString();
    }

    /**
     * Return an empty paginated result with the same structure as getClaimsData().
     * Used when the index fails so the frontend always receives a paginator shape (data, links, current_page, etc.).
     */
    public function getEmptyClaimsPaginator()
    {
        return ClaimRequest::query()
            ->whereRaw('1 = 0')
            ->simplePaginate($this->perPage)
            ->withQueryString();
    }

    /**
     * Get claims data for export with all necessary relationships.
     * Eager-loads every relationship used by ClaimsExport::map() to prevent N+1
     * when streaming chunked CSV (quoteType, claimType, claimStatus, claimSubStatus,
     * complaintStatus, manager, insuranceProvider, claimRequestType, claimRequestDetails.serviceType).
     */
    public function getClaimsDataForExport($requestParams = [])
    {
        $query = $this->buildDetailedClaimQuery();

        // Apply filters if provided using the same filtering logic as regular claims listing
        if (! empty($requestParams)) {
            // Use the same filter structure as getClaimsData
            $query = $this->applyFilters($query, $requestParams);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function applyFilters($query, $filters)
    {
        // is loggedin user is claim manager
        $user = auth()->user();
        $isClaimManager = $user?->hasRole(RolesEnum::ClaimsManager);
        if ($isClaimManager) {
            $query->where('manager_id', $user->id);
        }

        if (! empty($filters['code'])) {
            $query->where('code', $filters['code']);
        }

        if (! empty($filters['first_name'])) {
            $query->where('first_name', 'like', '%'.$filters['first_name'].'%');
        }

        if (! empty($filters['last_name'])) {
            $query->where('last_name', 'like', '%'.$filters['last_name'].'%');
        }

        if (! empty($filters['email'])) {
            $query->where('email', $filters['email']);
        }

        if (! empty($filters['mobile_no'])) {
            $query->where('mobile_no', $filters['mobile_no']);
        }

        if (! empty($filters['claim_status_id'])) {
            $query->where('claim_status_id', $filters['claim_status_id']);
        }

        if (! empty($filters['claim_sub_status_id'])) {
            $query->where('claim_sub_status_id', $filters['claim_sub_status_id']);
        }

        if (! empty($filters['complaint_status_id'])) {
            $query->where('complaint_status_id', $filters['complaint_status_id']);
        }

        if (! empty($filters['claim_request_type_id'])) {
            $query->where('claim_request_type_id', $filters['claim_request_type_id']);
        }

        if (! empty($filters['manager_id'])) {
            $query->where('manager_id', $filters['manager_id']);
        }

        if (! empty($filters['manager_assigned_date'])) {
            $query->whereDate('manager_assigned_date', $filters['manager_assigned_date']);
        }

        if (! empty($filters['next_followup_datetime'])) {
            $query->whereDate('next_followup_datetime', $filters['next_followup_datetime']);
        }

        if (! empty($filters['quote_type_id'])) {
            $query->where('quote_type_id', $filters['quote_type_id']);
        }

        if (! empty($filters['business_type_of_insurance_id'])) {
            $query->where('business_type_of_insurance_id', $filters['business_type_of_insurance_id']);
        }

        if (! empty($filters['policy_number'])) {
            $query->where('policy_number', $filters['policy_number']);
        }

        // Assignment status filtering
        if (! empty($filters['assigned_status'])) {
            if ($filters['assigned_status'] === 'assigned') {
                $query->whereNotNull('manager_id');
            } elseif ($filters['assigned_status'] === 'un-assigned') {
                $query->whereNull('manager_id');
            }
        }

        // Date filtering - handle start date, end date, or both
        if (! empty($filters['created_at_start']) && ! empty($filters['created_at_end'])) {
            $query->whereBetween('created_at', [$filters['created_at_start'], $filters['created_at_end']]);
        }

        // Filter by car details stored in claim_request_details table
        if (! empty($filters['car_make'])) {
            $query->whereHas('claimRequestDetails', function ($subQuery) use ($filters) {
                $subQuery->where('car_make', $filters['car_make']);
            });
        }

        if (! empty($filters['car_model'])) {
            $query->whereHas('claimRequestDetails', function ($subQuery) use ($filters) {
                $subQuery->where('car_model', $filters['car_model']);
            });
        }

        if (! empty($filters['model_year'])) {
            $query->whereHas('claimRequestDetails', function ($subQuery) use ($filters) {
                $subQuery->where('model_year', $filters['model_year']);
            });
        }

        if (! empty($filters['plate_number'])) {
            $query->whereHas('claimRequestDetails', function ($subQuery) use ($filters) {
                $subQuery->where('plate_number', $filters['plate_number']);
            });
        }

        if (! empty($filters['service_type_id'])) {
            $query->whereHas('claimRequestDetails', function ($subQuery) use ($filters) {
                $subQuery->where('service_type_id', $filters['service_type_id']);
            });
        }

        if (! empty($filters['insurance_provider_id'])) {
            $query->where('insurance_provider_id', $filters['insurance_provider_id']);
        }

        if (! empty($filters['claim_type_id'])) {
            $query->where('claim_type_id', $filters['claim_type_id']);
        }

        return $query;
    }

    public function getFilters($request)
    {
        return $request->only([
            'code',
            'first_name',
            'last_name',
            'email',
            'mobile_no',
            'created_at_start',
            'created_at_end',
            'claim_status_id',
            'claim_sub_status_id',
            'claim_type_id',
            'manager_id',
            'manager_assigned_date',
            'quote_type_id',
            'business_type_of_insurance_id',
            'policy_number',
            'insurance_provider_id',
            'complaint_status_id',
            'next_followup_datetime',
            'assigned_status',
            'plate_number',
            'car_make',
            'car_model',
            'model_year',
            'claim_request_type_id',
            'service_type_id',
        ]);
    }

    /**
     * Get claim request data with flexible filtering options
     */
    public function getClaimById($uuid)
    {
        return $this->buildDetailedClaimQuery()
            ->with(['documents.createdBy:id,name'])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Manually assign a claim to a claims manager (Claims Lead feature).
     * Validates that the target user has CLAIM_MANAGER role.
     */
    public function assignClaim(ClaimRequest $claim, int $managerId): ClaimRequest
    {
        $manager = User::find($managerId);

        if (! $manager || ! $manager->hasRole(RolesEnum::ClaimsManager)) {
            throw ValidationException::withMessages([
                'manager_id' => ['The selected user must be a claims manager.'],
            ]);
        }

        $claim->assignManager($managerId);

        return $claim->fresh(['manager:id,name']);
    }

    /**
     * Search active policies by email or policy number
     */
    public function searchActivePolicies($request)
    {
        $email = $request->email;
        $policyNumber = $request->policy_number;
        $quoteTypeId = $request->quote_type_id;

        $policies = PersonalQuote::with([
            'insuranceProvider:id,text',
            'quoteType:id,text',
            'carQuote' => function ($query) {
                $query->select(['id', 'uuid', 'car_make_id', 'car_model_id', 'year_of_manufacture'])
                    ->with([
                        'carMake:id,text',
                        'carModel:id,text',
                        'carQuoteRequestDetail:id,car_quote_request_id,plate_number',
                    ]);
            },
        ])
            ->select([
                'id',
                'uuid',
                'code',
                'policy_number',
                'first_name',
                'last_name',
                'policy_expiry_date',
                'policy_start_date',
                'email',
                'mobile_no',
                'quote_type_id',
                'customer_id',
                'insurance_provider_id',
                'quote_status_id',
            ])
            ->whereNotNull('policy_number')
            ->when($quoteTypeId, function ($query) use ($quoteTypeId) {
                $query->where('quote_type_id', $quoteTypeId);
            })
            ->when(! empty($request->business_type_of_insurance_id), function ($query) use ($request) {
                $query->where('business_type_of_insurance_id', $request->business_type_of_insurance_id);
            })
            ->whereIn('quote_status_id', [QuoteStatusEnum::PolicyBooked])
            ->when($email || $policyNumber, function ($query) use ($email, $policyNumber) {
                $query->where(function ($subQuery) use ($email, $policyNumber) {
                    $subQuery->where('email', $email);
                    if ($policyNumber) {
                        $subQuery->orWhere('policy_number', $policyNumber);
                    }
                });
            })
            ->orderBy('policy_expiry_date', 'desc')
            ->simplePaginate($this->perPage);

        // Transform data to match expected output format
        $policies->getCollection()->transform(function ($policy) {
            return (object) [
                'id' => $policy->id,
                'uuid' => $policy->uuid,
                'ref_id' => $policy->code,
                'policy_number' => $policy->policy_number,
                'customer_name' => trim($policy->first_name.' '.$policy->last_name),
                'policy_expiry_date' => $policy->policy_expiry_date,
                'policy_start_date' => $policy->policy_start_date,
                'email' => $policy->email,
                'mobile_no' => $policy->mobile_no,
                'quote_type_id' => $policy->quote_type_id,
                'quote_id' => $policy->id,
                'customer_id' => $policy->customer_id,
                'insurance_provider_id' => $policy->insurance_provider_id,
                'quote_status_id' => $policy->quote_status_id,
                'currently_insured_with' => $policy->insuranceProvider?->text,
                'product' => $policy->quoteType?->text,
                // Car-specific fields - only populated when quote_type_id is Car
                'car_make' => $policy->quote_type_id === QuoteTypeId::Car ? $policy->carQuote?->carMake?->text : null,
                'car_model' => $policy->quote_type_id === QuoteTypeId::Car ? $policy->carQuote?->carModel?->text : null,
                'model_year' => $policy->quote_type_id === QuoteTypeId::Car ? $policy->carQuote?->year_of_manufacture : null,
                'plate_number' => $policy->quote_type_id === QuoteTypeId::Car ? $policy->carQuote?->carQuoteRequestDetail?->plate_number : null,
            ];
        });

        return $policies;

    }

    /**
     * Create a new claim request
     */
    public function createClaim($request)
    {
        try {
            // Prepare data for API call
            $apiData = [
                'firstName' => $request->first_name ?? null,
                'lastName' => $request->last_name ?? null,
                'email' => $request->email ?? null,
                'mobileNo' => $request->mobile_no ?? null,
                'incident' => $request->incident_story ?? null,
                'incidentDate' => $request->incident_date ?? null,
                'customerId' => $request->customer_id ?? null,
                'policyNumber' => $request->policy_number ?? null,
                'insuranceProviderId' => $request->insurance_provider_id ?? null,
                'quoteTypeId' => $request->quote_type_id ?? null,
                'businessTypeOfInsuranceId' => $request->business_type_of_insurance_id ?? null,
                'source' => $request->source ?? config('constants.SOURCE_NAME', 'IMCRM'),
                'quoteUID' => $request->selected_quote_uuid ?? null,
                'claimTypeId' => $request->claim_type_id ?? null,
                'carMake' => $request->car_make ?? null,
                'carModel' => $request->car_model ?? null,
                'modelYear' => $request->model_year ?? null,
                'plateNumber' => $request->plate_number ?? null,
                'claimRequestTypeId' => $request->claim_request_type_id ?? null,
                'serviceTypeId' => $request->service_type_id ?? null,
                'requestReferenceNumber' => $request->request_reference_number ?? null,
            ];

            // Remove null values from $apiData before sending request
            $apiData = array_filter($apiData, function ($value) {
                return ! is_null($value);
            });
            // Make API call to create claim
            $response = CustomerPortalApiFacade::request('/api/claims/save-claim', 'post', $apiData);

            $responseData = $response->data;
            $user = auth()->user();
            $isClaimManager = $user?->hasRole(RolesEnum::ClaimsManager);

            if ($responseData['success'] && $isClaimManager) {
                $claim = $this->getClaimById($responseData['claimUID']);
                if ($claim !== null) {
                    $claim->update(['manager_id' => $user->id, 'manager_assigned_date' => now()]);
                } else {
                    LoggerService::warning('Claim not found in local database after API create; skipping manager assignment', extra: [
                        'claimUID' => $responseData['claimUID'] ?? null,
                    ]);
                }
            }

            return $responseData;

        } catch (\Exception $e) {
            LoggerService::error(' Error creating claim request', extra: [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request,
                'api_data' => $apiData ?? null,
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing claim request
     */
    public function updateClaim($uuid, $request): ClaimRequest
    {
        $claimRequest = $this->getClaimById($uuid);

        if (! $claimRequest) {
            throw new ModelNotFoundException('Claim not found.');
        }

        try {
            return DB::transaction(function () use ($claimRequest, $request) {
                // Update main claim request data
                $claimRequestData = $this->prepareClaimRequestData($request);
                $shouldDenyClaim = $this->isClaimDeclineReasonUpdated($claimRequest, $claimRequestData);
                $claimRequest->update($claimRequestData);

                if ($shouldDenyClaim) {
                    $this->claimsStatusesService->markClaimAsDenied($claimRequest);
                }

                // Update claim request detail with quote type-specific logic
                $quoteTypeId = $request->quote_type_id ?? $claimRequest->quote_type_id;
                $this->updateClaimRequestDetail($claimRequest, $request, $quoteTypeId);

                // Log successful update
                $this->logClaimUpdate($claimRequest);

                return $claimRequest->fresh(['claimRequestDetails', 'manager', 'claimStatus']);
            });
        } catch (\Exception $e) {
            $this->logClaimUpdateError($uuid, $e, $request);
            throw $e;
        }
    }

    /**
     * Get allowed fields for claim request update
     */
    protected function getAllowedClaimRequestFields(): array
    {
        $claimRequestFillable = (new ClaimRequest)->getFillable();
        $formSpecificFields = ['incident_story', 'whatsapp_consent', 'selected_policy_id', 'policy_not_listed'];

        return array_merge($claimRequestFillable, $formSpecificFields);
    }

    /**
     * Prepare claim request data for update
     */
    protected function prepareClaimRequestData($request): array
    {
        $allowedFields = $this->getAllowedClaimRequestFields();
        $claimRequestData = collect($request)->only($allowedFields)->toArray();

        // Map incident_story to incident field
        if (isset($claimRequestData['incident_story'])) {
            $claimRequestData['incident'] = $claimRequestData['incident_story'];
            unset($claimRequestData['incident_story']);
        }

        return $claimRequestData;
    }

    /**
     * Update or create claim request detail
     */
    protected function updateClaimRequestDetail(ClaimRequest $claimRequest, $request, ?int $quoteTypeId): void
    {
        $detailData = $this->prepareClaimDetailData($request, $quoteTypeId);

        if (empty($detailData)) {
            return;
        }

        $this->updateOrCreateClaimDetail($claimRequest, $detailData);
    }

    /**
     * Prepare claim detail data with quote type-specific field clearing
     */
    protected function prepareClaimDetailData($request, ?int $quoteTypeId): array
    {
        $claimRequestDetailFillable = (new ClaimRequestDetail)->getFillable();
        $detailData = collect($request)->only($claimRequestDetailFillable)->filter(fn ($value) => ! is_null($value))->toArray();

        return $this->clearQuoteTypeSpecificFields($detailData, $quoteTypeId);
    }

    /**
     * Clear fields based on quote type to maintain data integrity
     */
    protected function clearQuoteTypeSpecificFields(array $detailData, ?int $quoteTypeId): array
    {
        $carFields = ['car_make', 'car_model', 'model_year', 'plate_number'];
        $healthFields = ['service_type_id', 'request_reference_number'];

        if (in_array($quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike])) {
            // Clear health-related fields for car quotes
            foreach ($healthFields as $field) {
                $detailData[$field] = null;
            }
        } elseif ($quoteTypeId === QuoteTypeId::Health) {
            // Clear car-related fields for health quotes
            foreach ($carFields as $field) {
                $detailData[$field] = null;
            }
        } else {
            // Clear both car and health fields for other quote types
            foreach (array_merge($carFields, $healthFields) as $field) {
                $detailData[$field] = null;
            }
        }

        return $detailData;
    }

    /**
     * Update existing or create new claim detail record
     */
    protected function updateOrCreateClaimDetail(ClaimRequest $claimRequest, array $detailData): void
    {
        $claimRequestDetail = $claimRequest->claimRequestDetails()->first();

        if ($claimRequestDetail) {
            $claimRequestDetail->update($detailData);
        } else {
            $claimRequest->claimRequestDetails()->create($detailData);
        }
    }

    /**
     * Log successful claim update
     */
    protected function logClaimUpdate(ClaimRequest $claimRequest): void
    {
        LoggerService::info(' Claim request updated successfully - Claim UUID: '.$claimRequest->uuid, extra: [
            'code' => $claimRequest->code,
            'updated_by' => Auth::id(),
        ]);
    }

    /**
     * Log claim update error
     */
    protected function logClaimUpdateError(string $uuid, $e, $request): void
    {
        LoggerService::error(' Error updating claim request - Claim UUID: '.$uuid, extra: [
            'error' => $e->getMessage(),
            'claim_request_id' => $uuid,
            'data' => $request,
        ]);
    }

    /**
     * Update specific claim details (focused method for claim details form)
     */
    public function updateClaimDetails(ClaimRequest $claimRequest, $request): ClaimRequest
    {
        try {
            return DB::transaction(function () use ($claimRequest, $request) {
                $hasMainTableUpdates = $this->processClaimMainTableUpdates($claimRequest, $request);
                $hasDetailsTableUpdates = $this->processClaimDetailsTableUpdates($claimRequest, $request);

                $this->logClaimDetailsUpdate($claimRequest, $hasMainTableUpdates, $hasDetailsTableUpdates);

                return $claimRequest->fresh(['claimRequestDetails', 'manager', 'claimStatus', 'claimType', 'claimRequestType']);
            });
        } catch (\Exception $e) {
            $this->logClaimDetailsUpdateError($claimRequest, $e, $request);
            throw $e;
        }
    }

    /**
     * Get allowed fields for claim details update
     */
    protected function getAllowedClaimDetailsFields(): array
    {
        $claimRequestFillable = (new ClaimRequest)->getFillable();

        return array_intersect($claimRequestFillable, [
            'claim_type_id',
            'claim_number',
            'claim_decline_reason',
            'claim_request_type_id',
            'incident_date',
        ]);
    }

    /**
     * Get allowed fields for claim request detail update
     */
    protected function getAllowedClaimRequestDetailFields(): array
    {
        $claimRequestDetailFillable = (new ClaimRequestDetail)->getFillable();

        return array_intersect($claimRequestDetailFillable, [
            'plate_number',
            'car_make',
            'car_model',
            'model_year',
            'service_type_id',
        ]);
    }

    /**
     * Process updates to claim main table
     */
    protected function processClaimMainTableUpdates(ClaimRequest $claimRequest, $request): bool
    {
        $allowedFields = $this->getAllowedClaimDetailsFields();
        $claimRequestData = collect($request)->only($allowedFields)->toArray();

        if (empty($claimRequestData)) {
            return false;
        }

        $this->updateClaimMainTable($claimRequest, $claimRequestData);

        return true;
    }

    /**
     * Update claim main table and handle decline reason logic
     */
    protected function updateClaimMainTable(ClaimRequest $claimRequest, array $claimRequestData): void
    {
        $isClaimDeclineReasonUpdated = $this->isClaimDeclineReasonUpdated($claimRequest, $claimRequestData);

        $claimRequest->update($claimRequestData);

        if ($isClaimDeclineReasonUpdated) {
            $this->claimsStatusesService->markClaimAsDenied($claimRequest);
        }

        $this->logClaimMainTableUpdate($claimRequest, $claimRequestData);
    }

    /**
     * Check if claim decline reason is being set for the first time
     */
    protected function isClaimDeclineReasonUpdated(ClaimRequest $claimRequest, array $claimRequestData): bool
    {
        return empty($claimRequest->claim_decline_reason)
            && ! empty($claimRequestData['claim_decline_reason']);
    }

    /**
     * Log claim main table update
     */
    protected function logClaimMainTableUpdate(ClaimRequest $claimRequest, array $claimRequestData): void
    {
        LoggerService::info(' Claim request main table updated - Claim UUID: '.$claimRequest->uuid, extra: [
            'claim_uuid' => $claimRequest->uuid,
            'updated_fields' => array_keys($claimRequestData),
            'updated_by' => Auth::id(),
        ]);
    }

    /**
     * Process updates to claim details table
     */
    protected function processClaimDetailsTableUpdates(ClaimRequest $claimRequest, $request): bool
    {
        $allowedFields = $this->getAllowedClaimRequestDetailFields();
        $detailData = collect($request)->only($allowedFields)->toArray();

        if (empty($detailData)) {
            return false;
        }

        $this->updateOrCreateClaimRequestDetailRecord($claimRequest, $detailData);

        return true;
    }

    /**
     * Update existing or create new claim request detail record
     */
    protected function updateOrCreateClaimRequestDetailRecord(ClaimRequest $claimRequest, array $detailData): void
    {
        $claimRequestDetail = $claimRequest->claimRequestDetails()->first();

        if ($claimRequestDetail) {
            $claimRequestDetail->update($detailData);
        } else {
            $detailData['claim_request_id'] = $claimRequest->id;
            $claimRequest->claimRequestDetails()->create($detailData);
        }
    }

    /**
     * Log successful claim details update
     */
    protected function logClaimDetailsUpdate(ClaimRequest $claimRequest, bool $hasMainTableUpdates, bool $hasDetailsTableUpdates): void
    {
        LoggerService::info(' Claim details updated successfully - Claim UUID: '.$claimRequest->uuid, extra: [
            'claim_uuid' => $claimRequest->uuid,
            'code' => $claimRequest->code,
            'updated_by' => Auth::id(),
            'main_table_updates' => $hasMainTableUpdates,
            'details_table_updates' => $hasDetailsTableUpdates,
        ]);
    }

    /**
     * Log claim details update error
     */
    protected function logClaimDetailsUpdateError(ClaimRequest $claimRequest, $e, $request): void
    {
        LoggerService::error(' Error updating claim details - Claim UUID: '.$claimRequest->uuid, extra: [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'data' => $request,
            'updated_by' => Auth::id(),
        ]);
    }

    /**
     * Get dropdown data for forms
     */
    public function getDropdownData($request = null): array
    {
        $carMake = $request?->car_make;

        return [
            'lineOfBusiness' => $this->getLineOfBusinessOptions(),
            'claimTypes' => $this->lookupService->getClaimTypes(),
            'claimStatuses' => $this->claimsStatusesService->getClaimStatuses(),
            'claimSubStatuses' => $this->claimsStatusesService->getClaimSubStatuses(),
            'complaintStatuses' => $this->claimsStatusesService->getClaimComplaintStatuses(),
            'claimsManagers' => $this->userService->getClaimsManagers(),
            'claimRequestTypes' => $this->lookupService->getClaimRequestTypes(),
            'claimServiceTypes' => $this->lookupService->getClaimServiceTypes(),
            'carMake' => $this->getCarMake(),
            'carModel' => $carMake ? $this->getCarModelByMake($carMake) : [],
            'carModelYear' => $this->getCarModelYear(),
            'businessTypeOfInsurance' => $this->getBusinessTypeOfInsurance(),
        ];
    }

    /**
     * Get line of business options
     */
    public function getLineOfBusinessOptions(): array
    {
        return CacheManager::remember(
            CacheKeyEnum::QUOTE_TYPE_KEY,
            function () {
                return QuoteType::select('id', 'text')
                    ->forClaims()
                    ->withActive()
                    ->orderBy('text')
                    ->get()
                    ->toArray();
            }
        );
    }

    public function getCarMake(): array
    {
        return CacheManager::remember(CacheKeyEnum::CAR_MAKE_KEY, function () {
            return CarMake::select('code as id', 'text')
                ->active()
                ->get()
                ->toArray();
        });

    }

    public function getCarModelYear(): array
    {
        return CacheManager::remember(CacheKeyEnum::CAR_MODEL_YEAR_KEY, function () {
            return YearOfManufacture::select('text')
                ->orderedBySort()
                ->get()
                ->toArray();
        });

    }

    public function getBusinessTypeOfInsurance(): array
    {
        return CacheManager::remember(CacheKeyEnum::BUSINESS_TYPE_OF_INSURANCE_KEY, function () {
            return BusinessTypeOfInsurance::select('id', 'text')
                ->active()
                ->get()
                ->toArray();
        });
    }

    /**
     * Get car models by car make
     */
    public function getCarModelByMake(?string $carMake = null): array
    {
        $carMakeCode = CarMake::active()->where('text', $carMake)->value('code');

        if (! $carMakeCode) {
            return [];
        }

        return CarModel::activeWithCode($carMakeCode)
            ->select('id', 'text', 'code')
            ->orderBy('text')
            ->get()
            ->toArray();
    }

    public function isRequiredFieldsFilled(ClaimRequest $claimRequest): bool
    {

        $claimRequestDetails = $claimRequest->claimRequestDetails;

        $isCarOrBikeLOB = $claimRequest->isCarOrBikeLOB();

        $isRequiredFieldsFilled = false;

        if ($isCarOrBikeLOB) {
            $isRequiredFieldsFilled = $claimRequestDetails?->plate_number && $claimRequestDetails?->car_make && $claimRequestDetails?->car_model && $claimRequestDetails?->model_year;
        } else {
            $isRequiredFieldsFilled = $claimRequest?->policy_number && $claimRequest?->incident_date;
        }

        return $isRequiredFieldsFilled;
    }

    public function sendNotification(ClaimRequest $claimRequest, $request)
    {
        $claimActivity = DB::transaction(function () use ($claimRequest, $request) {
            $claimRequest->update(['claim_sub_status_id' => $request->claim_sub_status_id]); // Observer sets claim_status_id to closed when sub-status triggers closure

            return ClaimActivity::createForClaim(
                $claimRequest->id, $claimRequest->uuid, $request->claim_sub_status_id,
                $request->customer_message, $request->ai_optimized_message
            );
        });

        // Dispatch job AFTER transaction commits to ensure data consistency
        SendClaimSubStatusUpdateNotificationEmailJob::dispatch($claimRequest->uuid, $request->ai_optimized_message);

        return $claimActivity;
    }

    /**
     * Trigger Bird claims flow via Ken when claim has manager (e.g. after policy number is set/changed).
     * Accepts the ClaimRequest instance so callers (e.g. observer) can pass the in-memory model before save.
     */
    public function triggerBirdClaimsFlow(ClaimRequest $claimRequest): void
    {
        $claimRequest->loadMissing([
            'manager',
            'insuranceProvider.contacts',
            'claimRequestType',
            'quoteType',
            'claimRequestDetails',
        ]);

        $canTrigger = true;

        if (! $claimRequest->isCarOrBikeLOB()) {
            LoggerService::info(' Skipping Bird claims flow: claim is not a car or bike', extra: [
                'claim_uuid' => $claimRequest->uuid,
                'claim_code' => $claimRequest->code,
            ]);

            $canTrigger = false;
        }

        if (! $claimRequest->manager) {
            LoggerService::info(' Skipping Bird claims flow: claim has no manager', extra: [
                'claim_uuid' => $claimRequest->uuid,
                'claim_code' => $claimRequest->code,
            ]);

            $canTrigger = false;
        }

        $insuranceProvider = $claimRequest->insuranceProvider;
        if (! $insuranceProvider) {
            LoggerService::info(' Skipping Bird claims flow: claim has no insurance provider', extra: [
                'claim_uuid' => $claimRequest->uuid,
                'claim_code' => $claimRequest->code,
            ]);

            $canTrigger = false;
        }

        $insuranceProviderContact = null;
        if ($insuranceProvider) {
            $insuranceProviderContact = $insuranceProvider->contacts
                ->where('department', InsuranceProviderContactDepartmentEnum::CLAIM->value)
                ->whereNotNull('emails')
                ->firstWhere('quote_type_id', $claimRequest->quote_type_id);

            if (! $insuranceProviderContact) {
                LoggerService::info(' Skipping Bird claims flow: no matching insurance provider contact found', extra: [
                    'claim_uuid' => $claimRequest->uuid,
                    'claim_code' => $claimRequest->code,
                    'quote_type_id' => $claimRequest->quote_type_id,
                    'department' => InsuranceProviderContactDepartmentEnum::CLAIM->value,
                ]);

                $canTrigger = false;
            }
        }

        if (! $canTrigger) {
            return;
        }

        $claimRequestTypeCode = $claimRequest->claimRequestType?->code;

        $emailPayload = [
            'claimRefId' => $claimRequest->code,
            'claimRequestTypeCode' => $claimRequestTypeCode,
            'claimUID' => $claimRequest->uuid,
            'customerEmail' => $claimRequest->email,
            'customerMobile' => $claimRequest->mobile_no,
            'customerName' => $claimRequest->full_name,
            'isWAConsent' => $claimRequest->whatsapp_consent,
            'quoteTypeId' => $claimRequest->quote_type_id,
            'policyNumber' => $claimRequest->policy_number,
            'workflowType' => 'CLAIM_UPDATED',
            'subject' => $this->getClaimsFlowEmailSubject($claimRequest),
            'emailTo' => $insuranceProviderContact->emails,
            'emailCc' => $insuranceProviderContact->email_cc,
        ];
        try {
            Ken::request('/trigger-bird-claims-flow', 'post', $emailPayload);
        } catch (\Exception $e) {
            LoggerService::error(' Error triggering Bird claims flow', extra: [
                'error' => $e->getMessage(),
                'claim_uuid' => $claimRequest->uuid,
                'claim_code' => $claimRequest->code,
            ]);
            throw $e;
        }
    }

    private function getClaimsFlowEmailSubject(ClaimRequest $claimRequest): string
    {
        $lob = $claimRequest->quoteType?->text ?? 'Car/Bike';
        $parts = array_merge(
            ["New {$lob} Claim"],
            $this->getClaimsFlowEmailSubjectParts($claimRequest)
        );

        return implode(' - ', $parts);
    }

    /**
     * @return array<int, string>
     */
    private function getClaimsFlowEmailSubjectParts(ClaimRequest $claimRequest): array
    {
        $parts = [];

        if (filled($claimRequest->policy_number)) {
            $parts[] = "Policy Number [{$claimRequest->policy_number}]";
        }

        $details = $claimRequest->claimRequestDetails;
        if ($details !== null) {
            if (filled($details->plate_number)) {
                $parts[] = "Plate - [{$details->plate_number}]";
            }
            $vehicleParts = array_filter([$details->model_year, $details->car_make, $details->car_model]);
            if ($vehicleParts !== []) {
                $parts[] = '['.implode(', ', $vehicleParts).']';
            }
        }

        $insuredName = trim($claimRequest->full_name);
        if ($insuredName !== '') {
            $parts[] = "[{$insuredName}]";
        }

        return $parts;
    }

    /**
     * Get claim lead history (status changes by team lead) - all data for client-side pagination
     * Frontend will process old status from chronological data
     *
     * @return array
     */
    public function getClaimLeadHistory(int $claimId)
    {
        try {
            // Simple query using Eloquent ORM - frontend will process old status from chronological order
            $claimLeadHistory = ClaimActivity::with([
                'claimStatus:id,text,status_type',
                'createdBy:id,name',
            ])
                ->forClaimRequest($claimId)
                ->whereNotNull('status_id')
                ->whereHas('claimStatus', function ($query) {
                    $query->where('status_type', ClaimsEnum::CLAIM_STATUSES_STATUS_KEY->value);
                })
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($activity) {
                    $statusText = $activity->claimStatus?->text;

                    return [
                        'ModifiedAt' => $activity->created_at,
                        'Notes' => $activity->comment,
                        'ModifiedBy' => $activity->createdBy->name ?? null,
                        'NewStatus' => is_array($statusText) ? ($statusText['label'] ?? null) : null,
                        'created_at' => $activity->created_at, // Include for frontend sorting
                    ];
                });

            return $claimLeadHistory;

        } catch (\Exception $e) {
            LoggerService::error(' Error fetching claim lead history', extra: [
                'error' => $e->getMessage(),
                'claim_id' => $claimId,
                'user_id' => Auth::id(),
            ]);
            throw $e;
        }
    }

    /**
     * Update next follow-up for a claim
     */
    public function updateNextFollowUp(ClaimRequest $claim, $requestData = null): ClaimRequest
    {
        try {
            $nextFollowUpDatetime = $requestData?->next_follow_up_date;
            $notes = $requestData?->notes;
            // Update the claim with next follow-up
            $claim->updateNextFollowUp($nextFollowUpDatetime, $notes);

            LoggerService::info(' Next follow-up updated successfully', extra: [
                'claim_id' => $claim->id,
                'next_followup_datetime' => $nextFollowUpDatetime,
                'user_id' => Auth::id(),
            ]);

            return $claim->fresh();

        } catch (\Exception $e) {
            LoggerService::error(' Error updating next follow-up', extra: [
                'error' => $e->getMessage(),
                'claim_id' => $claim->id,
                'next_follow_up_datetime' => $nextFollowUpDatetime,
                'user_id' => Auth::id(),
            ]);

            throw $e;
        }
    }

    /**
     * Get next follow-up logs for a claim from audit trail
     *
     * @return array
     */
    public function getNextFollowUpLogs(int $claimId)
    {
        $audits = DB::table('audits as a')
            ->select(
                DB::raw('(SELECT name from users where id = a.user_id) as logged_by'),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.old_values, '$.next_followup_datetime')) AS old_follow_up_date"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.next_followup_datetime')) AS new_follow_up_date"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.old_values, '$.next_followup_notes')) AS old_notes"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.next_followup_notes')) AS new_notes"),
                'a.created_at as logged_at',
            )
            ->where(function ($query) {
                // Only get records where next_followup_datetime or next_followup_notes were changed
                $query->whereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.next_followup_datetime')"))
                    ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.next_followup_notes')"));
            })
            ->where(function ($query) use ($claimId) {
                $query->where('a.auditable_type', 'App\Models\\ClaimRequest')
                    ->where('a.auditable_id', $claimId);
            })
            ->orderBy('a.created_at', 'DESC')
            ->get()
            ->filter(function ($item) {
                // Filter out records where both datetime and notes are unchanged
                return ! is_null($item->new_follow_up_date) || ! is_null($item->new_notes);
            })
            ->values()
            ->toArray();

        return $audits;
    }

    public function optimizeMessageWithAI($request)
    {
        try {
            $apiData = [
                'original_message' => $request->message,
                'claim_reference' => $request->claim_uuid,
            ];

            return InstantWriterAIFacade::request('/message-optimizer/optimize', 'post', $apiData);
        } catch (\Exception $e) {
            LoggerService::error(' Error optimizing message', extra: [
                'error' => $e->getMessage(),
                'message' => $request->message,
            ]);

            throw $e;
        }
    }
    /**
     * Get a minimal claim by UUID for operations that only need identity and manager fields
     * (e.g. bulk assign). Uses a lightweight select to avoid loading full query/relations.
     */
    public function getClaimByUUID(string $uuid): ?ClaimRequest
    {
        return ClaimRequest::query()
            ->select(['id', 'uuid', 'manager_id', 'manager_assigned_date'])
            ->where('uuid', $uuid)
            ->first();
    }
}
