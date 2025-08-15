<?php

namespace App\Services;

use App\Enums\LookupsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\ClaimsEnum;
use App\Enums\QuoteTypeId;
use App\Facades\Capi;
use App\Models\Claim;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\YearOfManufacture;
use App\Models\ClaimRequest;
use App\Models\ClaimsStatus;
use App\Models\Lookup;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Traits\CentralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class ClaimsService extends BaseService
{
    use CentralTrait;

    protected $searchPrefix = 'claims.';
    protected $query;
    protected $perPage = 15;

    public function __construct()
    {
        parent::__construct();

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
            'whatsapp_consent',
            'approved_repair_amount',
            'approved_total_loss_amount',
            'approved_cash_loss_amount',
            'claim_decline_reason',
            'created_at',
        ])
            ->with([
                'quoteType:id,code,text',
                'claimType:id,code,text',
                'manager:id,name',
                'claimStatus:id,text',
                'claimSubStatus:id,text',
                'insuranceProvider:id,code,text',
                'claimRequestType:id,code,text',
                'claimRequestDetails' => function($query) {
                    $query->with('serviceType:id,code,text');
                },
                'personalQuote' => function($query) {
                    $query->with('advisor:id,name', 'insuranceProvider:id,code,text');
                },
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

        return $query->simplePaginate($this->perPage)->withQueryString();
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
    public function searchActivePolicies(?string $email = null, ?string $policyNumber = null, ?int $quoteTypeId = null, int $page = 1)
    {
        try {
            $isCarQuote = $quoteTypeId == QuoteTypeId::Car;
            $isHealthQuote = $quoteTypeId == QuoteTypeId::Health;
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
                ])
                ->leftJoin('insurance_provider', 'personal_quotes.insurance_provider_id', '=', 'insurance_provider.id')
                ->leftJoin('quote_type', 'personal_quotes.quote_type_id', '=', 'quote_type.id')
                ->whereNotNull('personal_quotes.policy_number')
                ->when($quoteTypeId, function ($query) use ($quoteTypeId) {
                    $query->where('personal_quotes.quote_type_id', $quoteTypeId);
                })
                ->whereIn('personal_quotes.quote_status_id', [QuoteStatusEnum::PolicyBooked]) // Active policy statuses
                ->when($email || $policyNumber, function ($query) use ($email, $policyNumber) {
                    $query->where(function ($subQuery) use ($email, $policyNumber) {
                        if ($email) {
                            $subQuery->where('personal_quotes.email', $email);
                        }
                        if ($policyNumber) {
                            if ($email) {
                                $subQuery->orWhere('personal_quotes.policy_number', $policyNumber);
                            } else {
                                $subQuery->where('personal_quotes.policy_number', $policyNumber);
                            }
                        }
                    });
                })
                ->orderBy('personal_quotes.policy_expiry_date', 'desc')
                ->simplePaginate($this->perPage);

            // Log the search
            LoggerService::info(self::class . '::' . __FUNCTION__ . ' - Policy search performed', [
                'email' => $email,
                'policy_number' => $policyNumber,
                'results_count' => count($policies),
                'user_id' => Auth::id(),
            ]);

            // Return pagination data structure
            return $policies;

        } catch (\Exception $e) {
            LoggerService::error(self::class . '::' . __FUNCTION__ . ' - Error searching active policies', extra: [
                'error' => $e->getMessage(),
                'email' => $email,
                'policy_number' => $policyNumber,
            ]);

            return [];
        }
    }

    /**
     * Create a new claim request
     */
    public function createClaim(array $data)
    {
        try {
            // Prepare data for API call
            $apiData = [
                'firstName' => $data['first_name'] ?? '',
                'lastName' => $data['last_name'] ?? '',
                'email' => $data['email'] ?? '',
                'mobileNo' => $data['mobile_no'] ?? '',
                'incident' => $data['incident_story'] ?? '',
                'customerId' => $data['customer_id'] ?? null,
                'policyNumber' => $data['policy_number'] ?? '',
                'insuranceProviderId' => $data['insurance_provider_id'] ?? null,
                'quoteTypeId' => $data['quote_type_id'] ?? null,
                'source' => $data['source'] ?? config('constants.SOURCE_NAME', 'system'),
                'quoteUID' => $data['selected_quote_uuid'] ?? '',
                'claimTypeId' => $data['claim_type_id'] ?? null,
            ];


            if (! empty($data['customer_id'])) {
                $apiData['customerId'] = $data['customer_id'];
            }
            if (! empty($data['insurance_provider_id'])) {
                $apiData['insuranceProviderId'] = $data['insurance_provider_id'];
            }

            // Add vehicle information if available
            if (! empty($data['car_make'])) {
                $apiData['carMake'] = $data['car_make'];
            }
            if (! empty($data['car_model'])) {
                $apiData['carModel'] = $data['car_model'];
            }

            // Add Health information if available
            if (! empty($data['claim_request_type_id'])) {
                $apiData['claimRequestTypeId'] = $data['claim_request_type_id'];
            }
            if (! empty($data['service_type_id'])) {
                $apiData['serviceTypeId'] = $data['service_type_id'];
            }
            if (! empty($data['request_reference_number'])) {
                $apiData['requestReferenceNumber'] = $data['request_reference_number'];
            }

            // Make API call to create claim
            $response = Capi::request('/api/v2-save-claim', 'post', $apiData);

            return $response;

        } catch (\Exception $e) {
            LoggerService::error(self::class . '::' . __FUNCTION__ . ' - Error creating claim request', extra: [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
                'api_data' => $apiData ?? null,
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing claim request
     */
    public function updateClaim($uuid, array $data): ClaimRequest
    {
        $claimRequest = $this->getClaimById($uuid);

        try {


            // Separate claim request data from detail data
            $claimRequestData = collect($data)->only([
                'incident_story', 'incident_date', 'first_name', 'last_name',
                'email', 'mobile_no', 'customer_id', 'source', 'manager_id', 'manager_assigned_date',
                'quote_uuid', 'quote_type_id', 'personal_quote_id', 'insurance_provider_id', 'policy_number',
                'claim_number', 'claim_status_id', 'claim_sub_status_id', 'claim_type_id', 'claim_request_type_id',
                'whatsapp_consent', 'selected_policy_id', 'policy_not_listed', 'claim_decline_reason',
                'approved_repair_amount', 'approved_total_loss_amount', 'approved_cash_loss_amount',
            ])->filter()->toArray();



            // Store incident_story as incident field
            if (isset($claimRequestData['incident_story'])) {
                $claimRequestData['incident'] = $claimRequestData['incident_story'];
                unset($claimRequestData['incident_story']);
            }
            $claimRequest->update($claimRequestData);

            // Handle claim request detail updates with quote type logic
            $detailData = collect($data)->only([
                'car_make', 'car_model', 'model_year', 'plat_number', 'service_type_id', 'request_reference_number', 'user_ip',
            ])->toArray();

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
                $detailData['plat_number'] = null;
            } else {
                // Clear both car and health detail fields
                $detailData['service_type_id'] = null;
                $detailData['request_reference_number'] = null;
                $detailData['car_make'] = null;
                $detailData['car_model'] = null;
                $detailData['model_year'] = null;
                $detailData['plat_number'] = null;
            }

            // Filter out empty strings but keep null values for database updates
            $detailData = array_filter($detailData, function($value) {
                return $value !== '';
            });

            if (! empty($detailData)) {
                $claimRequestDetail = $claimRequest->claimRequestDetails()->first();
                if ($claimRequestDetail) {
                    $claimRequestDetail->update($detailData);
                } else {
                    $claimRequest->claimRequestDetails()->create($detailData);
                }
            }

            // Log the update
            LoggerService::info(self::class . '::' . __FUNCTION__ . ' - Claim request updated successfully - Claim UUID: ' . $claimRequest->uuid, [
                'claim_uuid' => $claimRequest->uuid,
                'code' => $claimRequest->code,
                'updated_by' => auth()->id(),
            ]);


            return $claimRequest->fresh(['claimRequestDetails', 'manager', 'claimStatus']);
        } catch (\Exception $e) {
            LoggerService::error(self::class . '::' . __FUNCTION__ . ' - Error updating claim request - Claim UUID: ' . $uuid, extra: [
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
    public function updateClaimDetails($uuid, array $data): ClaimRequest
    {

        $claimRequest = $this->getClaimById($uuid);

        if (!$claimRequest) {
            throw new \Exception("Claim request not found with UUID: {$uuid}");
        }

        try {

            // Define which fields belong to the main claim request table
            $claimRequestFields = [
                'claim_type_id',
                'claim_number',
                'claim_decline_reason',
                'claim_request_type_id',
                'incident_date',
            ];

            // Define which fields belong to the claim request details table
            $claimRequestDetailFields = [
                'plat_number',
                'car_make',
                'car_model',
                'model_year',
                'service_type_id',
            ];

            // Separate data for claim request table
            $claimRequestData = collect($data)
                ->only($claimRequestFields)
                ->toArray();

            // Update claim request if there's data
            if (!empty($claimRequestData)) {
                $claimRequest->update($claimRequestData);

                LoggerService::info(self::class . '::' . __FUNCTION__ . ' - Claim request main table updated - Claim UUID: ' . $claimRequest->uuid, [
                    'claim_uuid' => $claimRequest->uuid,
                    'updated_fields' => array_keys($claimRequestData),
                    'updated_by' => Auth::id(),
                ]);
            }

            // Separate data for claim request details table
            $detailData = collect($data)
                ->only($claimRequestDetailFields)
                ->toArray();

            // Handle claim request details update/create
            if (!empty($detailData)) {
                $claimRequestDetail = $claimRequest->claimRequestDetails()->first();

                if ($claimRequestDetail) {
                    $claimRequestDetail->update($detailData);
                    LoggerService::info(self::class . '::' . __FUNCTION__ . ' - Claim request details updated - Claim UUID: ' . $claimRequest->uuid, [
                        'claim_uuid' => $claimRequest->uuid,
                        'detail_id' => $claimRequestDetail->id,
                        'updated_fields' => array_keys($detailData),
                        'updated_by' => Auth::id(),
                    ]);
                } else {
                    // Create new detail record if it doesn't exist
                    $detailData['claim_request_id'] = $claimRequest->id;
                    $claimRequestDetail = $claimRequest->claimRequestDetails()->create($detailData);
                    LoggerService::info(self::class . '::' . __FUNCTION__ . ' - Claim request details created - Claim UUID: ' . $claimRequest->uuid, [
                        'claim_uuid' => $claimRequest->uuid,
                        'detail_id' => $claimRequestDetail->id,
                        'created_fields' => array_keys($detailData),
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            // Log the overall update
            LoggerService::info(self::class . '::' . __FUNCTION__ . ' - Claim details updated successfully - Claim UUID: ' . $claimRequest->uuid, [
                'claim_uuid' => $claimRequest->uuid,
                'code' => $claimRequest->code,
                'updated_by' => Auth::id(),
                'main_table_updates' => !empty($claimRequestData),
                'details_table_updates' => !empty($detailData),
            ]);


            return $claimRequest->fresh(['claimRequestDetails', 'manager', 'claimStatus', 'claimType', 'claimRequestType']);

        } catch (\Exception $e) {
            LoggerService::error(self::class . '::' . __FUNCTION__ . ' - Error updating claim details - Claim UUID: ' . $uuid, extra: [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
                'updated_by' => auth()->id(),
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
            'claimStatuses' => $this->getClaimStatuses(),
            'claimSubStatuses' => $this->getClaimSubStatuses(),
            'claimsManagers' => $this->getClaimsManagers(),
            'claimRequestTypes' => $this->getClaimRequestTypes(),
            'claimServiceTypes' => $this->getClaimServiceTypes(),
            'carMake' => $this->getCarMake(),
            'carModel' => [],
            'carModelYear' => $this->getCarModelYear(),
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
        return ClaimsStatus::where('parent', false)
            ->where('is_active', 1)
            ->select('id', 'text', 'quote_type_id')
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Get claim statuses from lookup
     */
    public function getClaimStatuses(): array
    {
        return ClaimsStatus::where('parent', true)
            ->where('is_active', 1)
            ->select('id', 'text', 'quote_type_id')
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
     * Get claim request types
     */
    public function getClaimRequestTypes(): array
    {
        return Lookup::where('key', ClaimsEnum::CLAIM_REQUEST_TYPES_KEY->value)
            ->where('is_active', 1)
            ->select('id', 'text', 'code')
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }
    /**
     * Get claim request types
     */
    public function getClaimServiceTypes(): array
    {
        return Lookup::where('key', ClaimsEnum::CLAIM_SERVICE_TYPES_KEY->value)
            ->where('is_active', 1)
            ->select('id', 'text', 'code')
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }


    public function getCarMake(): array
    {
        return CarMake::select('code as id', 'text')->where('is_active', true)->get()->toArray();
    }

    public function getCarModelYear(): array
    {
        return YearOfManufacture::select('text')->orderBy('sort_order')->get()->toArray();
    }


    public function updateClaimSubStatusToClaimRegistered(ClaimRequest $claimRequest): void
    {
        try {
            $isCarQuoteType = $claimRequest->quote_type_id == QuoteTypeId::Car;
            $claimRegisterStatusKey = ClaimsEnum::CLAIM_SUB_STATUS_CLAIM_REGISTERED;
            if ($isCarQuoteType) {
                $claimRegisterStatusKey = ClaimsEnum::CLAIM_SUB_STATUS_CLAIM_REGISTERED_AWAITING_INSPECTION;
            }
            // Find the "Claim initiated" status for the specific quote type
            $claimInitiatedStatus = ClaimsStatus::where('text', $claimRegisterStatusKey)->where('quote_type_id', $claimRequest->quote_type_id)->where('is_active', 1)->where('parent', 0)->first();

            // If no specific status found for the quote type, try to find a general one
            if (!$claimInitiatedStatus) {
                $claimInitiatedStatus = ClaimsStatus::where('text', $claimRegisterStatusKey)->whereNull('quote_type_id')->where('is_active', 1)->where('parent', 0)->first();
            }

            if ($claimInitiatedStatus) {
                // Update the claim sub status without triggering another observer event
                $claimRequest->claim_sub_status_id = $claimInitiatedStatus->id;

                LoggerService::info(self::class . '::' . __FUNCTION__ . ' - Claim sub status updated to "Claim registered" - Claim UUID: ' . $claimRequest->uuid, extra: [
                    'claim_request_id' => $claimRequest->id,
                    'claim_uuid' => $claimRequest->uuid,
                    'claim_sub_status_id' => $claimInitiatedStatus->id,
                    'quote_type_id' => $claimRequest->quote_type_id,
                    'trigger' => 'claim_number_entered',
                    'updated_by' => Auth::id(),
                ]);
            } else {
                LoggerService::warning(self::class . '::' . __FUNCTION__ . ' - Could not find "Claim registered" status - Claim UUID: ' . $claimRequest->uuid, extra: [
                    'claim_request_id' => $claimRequest->id,
                    'claim_uuid' => $claimRequest->uuid,
                    'quote_type_id' => $claimRequest->quote_type_id,
                ]);
            }
        } catch (\Exception $e) {
            LoggerService::error(self::class . '::' . __FUNCTION__ . ' - Error updating claim sub status to "Claim registered" - Claim UUID: ' . $claimRequest->uuid, extra: [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function checkSubStatusForClaimClosure(ClaimRequest $claimRequest, $newClaimSubStatusCode): bool
    {
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
            $subStatusListForClaimClosed[] = [
                ClaimsEnum::CLAIM_SUB_STATUS_REQUEST_APPROVED->value,
                ClaimsEnum::CLAIM_SUB_STATUS_ANSWERED_AND_CLOSED->value,
            ];
        }

        if ($isLifeQuoteType) {
            $subStatusListForClaimClosed = [
                ClaimsEnum::CLAIM_SUB_STATUS_CLAIM_PAID->value,
                ClaimsEnum::CLAIM_SUB_STATUS_CLAIM_DENIED->value,
            ];
        }

        return in_array($newClaimSubStatusCode, $subStatusListForClaimClosed);
    }

    public function markClaimAsClosed(ClaimRequest $claimRequest): void
    {
        $claimStatusClosed = ClaimsStatus::where('text', ClaimsEnum::CLAIM_STATUS_CLOSED)->where('is_active', 1)->first();
        if ($claimStatusClosed) {
            $claimRequest->update(['claim_status_id' => $claimStatusClosed->id]);
            LoggerService::info(self::class . '::' . __FUNCTION__ . ' - Claim status updated to "Closed" - Claim UUID: ' . $claimRequest->uuid, extra: [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'claim_status_id' => $claimStatusClosed->id,
                'updated_by' => Auth::id(),
            ]);
        }
    }

    public function isRequiredFieldsFilled(ClaimRequest $claimRequest): bool
    {

        $claimRequestDetails = $claimRequest->claimRequestDetails;

        $isCarQuoteType = $claimRequest->quote_type_id == QuoteTypeId::Car;
        $isHealthQuoteType = $claimRequest->quote_type_id == QuoteTypeId::Health;
        $isLifeQuoteType = $claimRequest->quote_type_id == QuoteTypeId::Life;


        $isRequiredFieldsFilled = false;

        if ($isCarQuoteType) {
            $isRequiredFieldsFilled = $claimRequestDetails->plat_number && $claimRequestDetails->car_make && $claimRequestDetails->car_model && $claimRequestDetails->model_year;
        }

        $isRequiredFieldsFilled = $claimRequest->policy_number && $claimRequest->claim_number && $claimRequest->incident_date;

        return $isRequiredFieldsFilled;
    }

    /**
     * Update claim status and sub status
     */
    public function updateClaimStatus(string $uuid, array $data): ClaimRequest
    {
        $claimRequest = $this->getClaimById($uuid);

        if (!$claimRequest) {
            throw new \Exception("Claim request not found with UUID: {$uuid}");
        }

        try {
            // Prepare the status update data
            $statusUpdateData = [];

            if (isset($data['claim_sub_status_id'])) {
                $statusUpdateData['claim_sub_status_id'] = $data['claim_sub_status_id'];
            }

            if (isset($data['claim_status_id'])) {
                $statusUpdateData['claim_status_id'] = $data['claim_status_id'];
            } else {
                $subStatus = ClaimsStatus::find($data['claim_sub_status_id']);
                $targetStatus = $this->checkSubStatusForClaimClosure($claimRequest, $subStatus->text) ? ClaimsEnum::CLAIM_STATUS_CLOSED->value : ClaimsEnum::CLAIM_STATUS_OPEN->value;
                $statusUpdateData['claim_status_id'] = ClaimsStatus::where('text', $targetStatus)->where('parent', true)->where('is_active', 1)->first()?->id;
            }

            // Update the claim request
            if (!empty($statusUpdateData)) {
                $claimRequest->update($statusUpdateData);

                LoggerService::info(self::class . '::' . __FUNCTION__ . ' - Claim status updated successfully - Claim UUID: ' . $claimRequest->uuid, [
                    'claim_request_id' => $claimRequest->id,
                    'claim_uuid' => $claimRequest->uuid,
                    'code' => $claimRequest->code,
                    'updated_fields' => array_keys($statusUpdateData),
                    'old_claim_status_id' => $claimRequest->getOriginal('claim_status_id'),
                    'new_claim_status_id' => $claimRequest->claim_status_id,
                    'old_claim_sub_status_id' => $claimRequest->getOriginal('claim_sub_status_id'),
                    'new_claim_sub_status_id' => $claimRequest->claim_sub_status_id,
                    'updated_by' => auth()->id(),
                ]);
            }

            return $claimRequest->fresh(['claimStatus', 'claimSubStatus', 'manager']);

        } catch (\Exception $e) {
            LoggerService::error(self::class . '::' . __FUNCTION__ . ' - Error updating claim status - Claim UUID: ' . $uuid, extra: [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'claim_request_id' => $uuid,
                'data' => $data,
                'updated_by' => auth()->id(),
            ]);

            throw $e;
        }
    }

}
