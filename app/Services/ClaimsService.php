<?php

namespace App\Services;

use App\Enums\ClaimsEnum;
use App\Enums\CacheKeyEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
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
use App\Models\YearOfManufacture;
use App\Services\Logger\LoggerService;
use App\Traits\CentralTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\Cache\CacheManager;

class ClaimsService extends BaseService
{
    use CentralTrait;

    protected ClaimStatusesService $claimsStatusesService;
    protected $searchPrefix = 'claims.';
    protected $query;
    protected $claimListQuery;
    protected $perPage = 15;

    public function __construct(ClaimStatusesService $claimsStatusesService)
    {
        parent::__construct();
        $this->claimsStatusesService = $claimsStatusesService;

        $this->claimListQuery = ClaimRequest::select([
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
                'claimType:id,code,text',
                'claimRequestDetails' => function ($query) {
                    $query->with('serviceType:id,code,text');
                },
                'manager:id,name',
            ]);

        $this->query = ClaimRequest::select([
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
                    $query->with('serviceType:id,code,text');
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
        // Apply filters
        $filters = $this->getFilters($request);
        $query = $this->applyFilters($this->claimListQuery, $filters);

        return $query->simplePaginate($this->perPage)->withQueryString();
    }

    /**
     * Get claims data for export with all necessary relationships
     */
    public function getClaimsDataForExport($requestParams = [])
    {
        $query = $this->query;

        // Apply filters if provided using the same filtering logic as regular claims listing
        if (! empty($requestParams)) {
            // Use the same filter structure as getClaimsData
            $query = $this->applyFilters($query, $requestParams);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function applyFilters($query, $filters)
    {
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
            'manager_id',
            'manager_assigned_date',
            'quote_type_id',
            'policy_number',
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
        return $this->query->where('uuid', $uuid)->first();

    }

    /**
     * Search active policies by email or policy number
     */
    public function searchActivePolicies($request)
    {
        $email = $request->email;
        $policyNumber = $request->policy_number;
        $quoteTypeId = $request->quote_type_id;

        $policies = PersonalQuote::query()
            ->select([
                'personal_quotes.id',
                'personal_quotes.uuid',
                'personal_quotes.code as ref_id',
                'personal_quotes.policy_number',
                DB::raw("TRIM(CONCAT(personal_quotes.first_name, ' ', personal_quotes.last_name)) as customer_name"),
                'personal_quotes.policy_expiry_date',
                'personal_quotes.policy_start_date',
                'personal_quotes.email',
                'personal_quotes.mobile_no',
                'personal_quotes.quote_type_id',
                'personal_quotes.id as quote_id',
                'personal_quotes.customer_id',
                'personal_quotes.insurance_provider_id',
                'personal_quotes.quote_status_id',
                'insurance_provider.text as currently_insured_with',
                'quote_type.text as product',
                // Car-specific fields
                DB::raw('CASE WHEN personal_quotes.quote_type_id = '.QuoteTypeId::Car.' THEN car_make.text ELSE NULL END as car_make'),
                DB::raw('CASE WHEN personal_quotes.quote_type_id = '.QuoteTypeId::Car.' THEN car_model.text ELSE NULL END as car_model'),
                DB::raw('CASE WHEN personal_quotes.quote_type_id = '.QuoteTypeId::Car.' THEN car_quote_request.year_of_manufacture ELSE NULL END as model_year'),
                DB::raw('CASE WHEN personal_quotes.quote_type_id = '.QuoteTypeId::Car.' THEN car_quote_request_detail.plate_number ELSE NULL END as plate_number'),
            ])
            ->leftJoin('insurance_provider', 'personal_quotes.insurance_provider_id', '=', 'insurance_provider.id')
            ->leftJoin('quote_type', 'personal_quotes.quote_type_id', '=', 'quote_type.id')
            // Car-specific joins - only when quote_type_id is Car
            ->leftJoin('car_quote_request', function ($join) {
                $join->on('car_quote_request.uuid', '=', 'personal_quotes.uuid')
                    ->where('personal_quotes.quote_type_id', '=', QuoteTypeId::Car);
            })
            ->leftJoin('car_make', function ($join) {
                $join->on('car_make.id', '=', 'car_quote_request.car_make_id')
                    ->where('personal_quotes.quote_type_id', '=', QuoteTypeId::Car);
            })
            ->leftJoin('car_model', function ($join) {
                $join->on('car_model.id', '=', 'car_quote_request.car_model_id')
                    ->where('personal_quotes.quote_type_id', '=', QuoteTypeId::Car);
            })
            ->leftJoin('car_quote_request_detail', function ($join) {
                $join->on('car_quote_request_detail.car_quote_request_id', '=', 'car_quote_request.id')
                    ->where('personal_quotes.quote_type_id', '=', QuoteTypeId::Car);
            })
            ->whereNotNull('personal_quotes.policy_number')
            ->when($quoteTypeId, function ($query) use ($quoteTypeId) {
                $query->where('personal_quotes.quote_type_id', $quoteTypeId);
            })
            ->whereIn('personal_quotes.quote_status_id', [QuoteStatusEnum::PolicyBooked]) // Active policy statuses
            ->when($email || $policyNumber, function ($query) use ($email, $policyNumber) {
                $query->where(function ($subQuery) use ($email, $policyNumber) {
                    $subQuery->where('personal_quotes.email', $email);
                    if ($policyNumber) {
                        $subQuery->orWhere('personal_quotes.policy_number', $policyNumber);
                    }
                });
            })
            ->orderBy('personal_quotes.policy_expiry_date', 'desc')
            ->simplePaginate($this->perPage);

        // Return pagination data structure
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

            return $response->data;

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

        try {

            // Get fillable fields from ClaimRequest model and add form-specific fields
            $claimRequestFillable = (new ClaimRequest)->getFillable();
            $formSpecificFields = ['incident_story', 'whatsapp_consent', 'selected_policy_id', 'policy_not_listed'];
            $allowedFields = array_merge($claimRequestFillable, $formSpecificFields);

            // Separate claim request data from detail data
            $claimRequestData = collect($request)->only($allowedFields)->filter()->toArray();

            // Store incident_story as incident field
            if (isset($claimRequestData['incident_story'])) {
                $claimRequestData['incident'] = $claimRequestData['incident_story'];
                unset($claimRequestData['incident_story']);
            }
            $claimRequest->update($claimRequestData);

            // Handle claim request detail updates with quote type logic
            $claimRequestDetailFillable = (new ClaimRequestDetail)->getFillable();
            $detailData = collect($data)->only($claimRequestDetailFillable)->toArray();

            // Handle quote type specific field clearing
            $quoteTypeId = $data['quote_type_id'] ?? null;
            // Clear fields based on quote type (don't filter null values as we want to set them)
            if ($quoteTypeId == QuoteTypeId::Car) {
                // Clear health-related detail fields
                $detailData['service_type_id'] = null;
                $detailData['request_reference_number'] = null;
            } elseif ($quoteTypeId == QuoteTypeId::Health) {
                // Clear car-related detail fields
                $detailData['car_make'] = null;
                $detailData['car_model'] = null;
                $detailData['model_year'] = null;
                $detailData['plate_number'] = null;
            } else {
                // Clear both car and health detail fields
                $detailData['service_type_id'] = null;
                $detailData['request_reference_number'] = null;
                $detailData['car_make'] = null;
                $detailData['car_model'] = null;
                $detailData['model_year'] = null;
                $detailData['plate_number'] = null;
            }

            if (! empty($detailData)) {
                $claimRequestDetail = $claimRequest->claimRequestDetails()->first();
                if ($claimRequestDetail) {
                    $claimRequestDetail->update($detailData);
                } else {
                    $claimRequest->claimRequestDetails()->create($detailData);
                }
            }

            // Log the update
            LoggerService::info(' Claim request updated successfully - Claim UUID: '.$claimRequest->uuid, extra: [
                'claim_uuid' => $claimRequest->uuid,
                'code' => $claimRequest->code,
                'updated_by' => Auth::id(),
            ]);

            return $claimRequest->fresh(['claimRequestDetails', 'manager', 'claimStatus']);
        } catch (\Exception $e) {
            LoggerService::error(' Error updating claim request - Claim UUID: '.$uuid, extra: [
                'error' => $e->getMessage(),
                'claim_request_id' => $uuid,
                'data' => $data,
            ]);

            throw $e;
        }
    }

    /**
     * Update specific claim details (focused method for claim details form)
     */
    public function updateClaimDetails(ClaimRequest $claimRequest, $request): ClaimRequest
    {
        try {

            // Get allowed fields from model fillable arrays (filtered for this specific update method)
            $claimRequestFillable = (new ClaimRequest)->getFillable();
            $claimRequestFields = array_intersect($claimRequestFillable, [
                'claim_type_id',
                'claim_number',
                'claim_decline_reason',
                'claim_request_type_id',
                'incident_date',
            ]);

            // Get allowed detail fields from model fillable array (filtered for this specific update method)
            $claimRequestDetailFillable = (new ClaimRequestDetail)->getFillable();
            $claimRequestDetailFields = array_intersect($claimRequestDetailFillable, [
                'plate_number',
                'car_make',
                'car_model',
                'model_year',
                'service_type_id',
            ]);

            // Separate data for claim request table
            $claimRequestData = collect($request)->only($claimRequestFields)->toArray();

            // Update claim request if there's data
            if (! empty($claimRequestData)) {
                $isClaimDeclineReasonUpdated = empty($claimRequest->claim_decline_reason) && ! empty($claimRequestData['claim_decline_reason']);
                $claimRequest->update($claimRequestData);

                if ($isClaimDeclineReasonUpdated) {
                    $this->claimsStatusesService->markClaimAsClosed($claimRequest);
                }

                LoggerService::info(' Claim request main table updated - Claim UUID: '.$claimRequest->uuid, extra: [
                    'claim_uuid' => $claimRequest->uuid,
                    'updated_fields' => array_keys($claimRequestData),
                    'updated_by' => Auth::id(),
                ]);
            }

            // Separate data for claim request details table
            $detailData = collect($request)->only($claimRequestDetailFields)->toArray();

            // Handle claim request details update/create
            if (! empty($detailData)) {
                $claimRequestDetail = $claimRequest->claimRequestDetails()->first();

                if ($claimRequestDetail) {
                    $claimRequestDetail->update($detailData);

                } else {
                    // Create new detail record if it doesn't exist
                    $detailData['claim_request_id'] = $claimRequest->id;
                    $claimRequestDetail = $claimRequest->claimRequestDetails()->create($detailData);
                }
            }

            // Log the overall update
            LoggerService::info(' Claim details updated successfully - Claim UUID: '.$claimRequest->uuid, extra: [
                'claim_uuid' => $claimRequest->uuid,
                'code' => $claimRequest->code,
                'updated_by' => Auth::id(),
                'main_table_updates' => ! empty($claimRequestData),
                'details_table_updates' => ! empty($detailData),
            ]);

            return $claimRequest->fresh(['claimRequestDetails', 'manager', 'claimStatus', 'claimType', 'claimRequestType']);

        } catch (\Exception $e) {
            LoggerService::error(' Error updating claim details - Claim UUID: '.$claimRequest->uuid, extra: [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request,
                'updated_by' => Auth::id(),
            ]);

            throw $e;
        }
    }

    /**
     * Get dropdown data for forms
     */
    public function getDropdownData($request = null): array
    {
        $carMake = $request?->car_make;
        $cachedData = CacheManager::remember(
            CacheKeyEnum::CLAIM_CACHE_DROPDOWN_DATA_KEY,
            function () {
                $lookupService = new LookupService;
                $userService = app(UserService::class);

                return [
                    'lineOfBusiness' => $this->getLineOfBusinessOptions(),
                    'claimTypes' => $lookupService->getClaimTypes(),
                    'claimStatuses' => $this->claimsStatusesService->getClaimStatuses(),
                    'claimSubStatuses' => $this->claimsStatusesService->getClaimSubStatuses(),
                    'complaintStatuses' => $this->claimsStatusesService->getClaimComplaintStatuses(),
                    'claimsManagers' => $userService->getClaimsManagers(),
                    'claimRequestTypes' => $lookupService->getClaimRequestTypes(),
                    'claimServiceTypes' => $lookupService->getClaimServiceTypes(),
                    'carMake' => $this->getCarMake(),
                    'carModelYear' => $this->getCarModelYear(),
                ];
            }
        );

        // Add dynamic carModel based on parameter
        $cachedData['carModel'] = $carMake ? $this->getCarModelByMake($carMake) : [];

        return $cachedData;
    }

    /**
     * Get line of business options
     */
    public function getLineOfBusinessOptions(): array
    {
        return  CacheManager::remember(
            CacheKeyEnum::CLAIM_CACHE_QUOTE_TYPE_KEY,
            function () {
                return QuoteType::select('id', 'text')
                        ->whereIn('id', [
                            QuoteTypeId::Car, QuoteTypeId::Travel, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Bike, QuoteTypeId::Cycle, QuoteTypeId::Jetski, QuoteTypeId::Business, QuoteTypeId::Yacht,
                            QuoteTypeId::Health, QuoteTypeId::Life,
                        ])->where('is_active', 1)->orderBy('text')->get()->toArray();
            }
        );
    }

    public function getCarMake(): array
    {
        return CarMake::select('code as id', 'text')->where('is_active', true)->get()->toArray();
    }

    public function getCarModelYear(): array
    {
        return YearOfManufacture::select('text')->orderBy('sort_order')->get()->toArray();
    }

    /**
     * Get car models by car make
     */
    public function getCarModelByMake(string $carMake): array
    {
        $carMakeCode = CarMake::where('text', $carMake)->where('is_active', true)->value('code');

        if (! $carMakeCode) {
            return [];
        }

        return CarModel::where('car_make_code', $carMakeCode)
            ->where('is_active', true)
            ->select('id', 'text', 'code')
            ->orderBy('text')
            ->get()
            ->toArray();
    }

    public function isRequiredFieldsFilled(ClaimRequest $claimRequest): bool
    {

        $claimRequestDetails = $claimRequest->claimRequestDetails;

        $isCarQuoteType = $claimRequest->quote_type_id == QuoteTypeId::Car;
        $isHealthQuoteType = $claimRequest->quote_type_id == QuoteTypeId::Health;
        $isLifeQuoteType = $claimRequest->quote_type_id == QuoteTypeId::Life;

        $isRequiredFieldsFilled = false;

        if ($isCarQuoteType) {
            $isRequiredFieldsFilled = $claimRequestDetails?->plate_number && $claimRequestDetails?->car_make && $claimRequestDetails?->car_model && $claimRequestDetails?->model_year;
        } else {

            $isRequiredFieldsFilled = $claimRequest?->policy_number && $claimRequest?->claim_number && $claimRequest?->incident_date;
        }

        return $isRequiredFieldsFilled;
    }

    public function getClaimDocumentTypes($quoteTypeId)
    {
        $claimDocumentTypes = DocumentType::active()->whereIn('category', [DocumentTypeCode::CLAIM])->where('quote_type_id', $quoteTypeId)->sortDocumentType()->get();

        $documentTypesByCategory = $claimDocumentTypes->groupBy('category');
        $orderedDocumentTypesByCategory = collect();

        if ($documentTypesByCategory->has(DocumentTypeCode::CLAIM)) {
            $orderedDocumentTypesByCategory->put(DocumentTypeCode::CLAIM, $documentTypesByCategory->get(DocumentTypeCode::CLAIM));
        }

        return $orderedDocumentTypesByCategory;
    }

    public function sendNotification(ClaimRequest $claimRequest, $request)
    {
        $updateClaimData['claim_sub_status_id'] = $request->claim_sub_status_id;
        $subStatus = ClaimStatus::find($request->claim_sub_status_id);
        $targetStatus = $this->claimsStatusesService->checkSubStatusForClaimClosure($claimRequest, $subStatus->id) ? ClaimsEnum::CLAIM_STATUS_CLOSED->value : null;
        if ($targetStatus) {
            $updateClaimData['claim_status_id'] = ClaimStatus::where('text', $targetStatus)->where('status_type', ClaimsEnum::CLAIM_STATUSES_STATUS_KEY->value)->where('is_active', 1)->first()?->id;
        }

        $claimRequest->update($updateClaimData);

        $claimActivity = ClaimActivity::createForClaim(
            $claimRequest->id, $claimRequest->uuid, $request->claim_sub_status_id,
            $request->customer_message, $request->ai_optimized_message
        );

        return $claimActivity;
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
            // Simple query - frontend will process old status from chronological order
            $claimLeadHistory = DB::table('claim_activities as ca')
                ->join('claim_statuses as cs', 'ca.status_id', '=', 'cs.id')
                ->join('users as u', 'ca.created_by_id', '=', 'u.id')
                ->select(
                    'ca.created_at as ModifiedAt',
                    'ca.comment as Notes',
                    'u.name as ModifiedBy',
                    'cs.text as NewStatus',
                    'ca.created_at as created_at' // Include for frontend sorting
                )
                ->where('ca.claim_request_id', $claimId)
                ->where('cs.status_type', ClaimsEnum::CLAIM_STATUSES_STATUS_KEY->value) // Only get status_type statuses (main claim statuses)
                ->orderBy('ca.created_at', 'asc') // Order chronologically for frontend processing
                ->get();

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
}
