<?php

namespace App\Services;

use App\Enums\ClaimsEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\LookupsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Facades\Capi;
use App\Models\CarMake;
use App\Models\ClaimActivity;
use App\Models\ClaimRequest;
use App\Models\ClaimStatus;
use App\Models\DocumentType;
use App\Models\Lookup;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Models\User;
use App\Models\YearOfManufacture;
use App\Services\Logger\LoggerService;
use App\Traits\CentralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SendGoogleReviewEmailJob;
use ZipArchive;
use Exception;

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

        if (! empty($filters['manager_id'])) {
            $query->where('manager_id', $filters['manager_id']);
        }

        if (! empty($filters['quote_type_id'])) {
            $query->where('quote_type_id', $filters['quote_type_id']);
        }

        if (! empty($filters['policy_number'])) {
            $query->where('policy_number', 'like', '%'.$filters['policy_number'].'%');
        }

        // Date filtering - handle start date, end date, or both
        if (! empty($filters['created_at_start']) && ! empty($filters['created_at_end'])) {
            $query->whereBetween('created_at', [$filters['created_at_start'], $filters['created_at_end']]);
        }

        // Filter by car details stored in claim_request_details table
        if (! empty($filters['car_make'])) {
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Filtering by car_make: '.$filters['car_make']);
            $query->whereHas('claimRequestDetails', function ($subQuery) use ($filters) {
                $subQuery->where('car_make', 'like', '%'.$filters['car_make'].'%');
            });
        }

        if (! empty($filters['car_model'])) {
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Filtering by car_model: '.$filters['car_model']);
            $query->whereHas('claimRequestDetails', function ($subQuery) use ($filters) {
                $subQuery->where('car_model', 'like', '%'.$filters['car_model'].'%');
            });
        }

        if (! empty($filters['model_year'])) {
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Filtering by model_year: '.$filters['model_year']);
            $query->whereHas('claimRequestDetails', function ($subQuery) use ($filters) {
                $subQuery->where('model_year', $filters['model_year']);
            });
        }

        if (! empty($filters['plat_number'])) {
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Filtering by plat_number: '.$filters['plat_number']);
            $query->whereHas('claimRequestDetails', function ($subQuery) use ($filters) {
                $subQuery->where('plat_number', 'like', '%'.$filters['plat_number'].'%');
            });
        }

        return $query;
    }

    public function getFilters(Request $request)
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
            'assigned_manager_id',
            'lead_manager_id',
            'manager_assigned_date',
            'quote_type_id',
            'policy_number',
            'complaint_status',
            'next_follow_up_date',
            'plat_number',
            'car_make',
            'car_model',
            'model_year',
            'assigned_status',
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
                        $subQuery->where('personal_quotes.email', $email);
                        if ($policyNumber) {
                            $subQuery->orWhere('personal_quotes.policy_number', $policyNumber);
                        }
                    });
                })
                ->orderBy('personal_quotes.policy_expiry_date', 'desc')
                ->simplePaginate($this->perPage);

            // Log the search
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Policy search performed', [
                'email' => $email,
                'policy_number' => $policyNumber,
                'results_count' => count($policies),
                'user_id' => auth()->id(),
            ]);

            // Return pagination data structure
            return $policies;

        } catch (\Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error searching active policies', extra: [
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
            $response = Capi::request('/api/v2-claims', 'post', $apiData);

            if(isset($response->claimUID) && $response->claimUID){
                // Send Claim Intimation Email
            }

            return $response;

        } catch (\Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error creating claim request', extra: [
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
            $detailData = array_filter($detailData, function ($value) {
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
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Claim request updated successfully - Claim UUID: '.$claimRequest->uuid, [
                'claim_uuid' => $claimRequest->uuid,
                'code' => $claimRequest->code,
                'updated_by' => auth()->id(),
            ]);

            return $claimRequest->fresh(['claimRequestDetails', 'manager', 'claimStatus']);
        } catch (\Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error updating claim request - Claim UUID: '.$uuid, extra: [
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

        if (! $claimRequest) {
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
            if (! empty($claimRequestData)) {
                $claimRequest->update($claimRequestData);

                LoggerService::info(self::class.'::'.__FUNCTION__.' - Claim request main table updated - Claim UUID: '.$claimRequest->uuid, [
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
            if (! empty($detailData)) {
                $claimRequestDetail = $claimRequest->claimRequestDetails()->first();

                if ($claimRequestDetail) {
                    $claimRequestDetail->update($detailData);
                    LoggerService::info(self::class.'::'.__FUNCTION__.' - Claim request details updated - Claim UUID: '.$claimRequest->uuid, [
                        'claim_uuid' => $claimRequest->uuid,
                        'detail_id' => $claimRequestDetail->id,
                        'updated_fields' => array_keys($detailData),
                        'updated_by' => Auth::id(),
                    ]);
                } else {
                    // Create new detail record if it doesn't exist
                    $detailData['claim_request_id'] = $claimRequest->id;
                    $claimRequestDetail = $claimRequest->claimRequestDetails()->create($detailData);
                    LoggerService::info(self::class.'::'.__FUNCTION__.' - Claim request details created - Claim UUID: '.$claimRequest->uuid, [
                        'claim_uuid' => $claimRequest->uuid,
                        'detail_id' => $claimRequestDetail->id,
                        'created_fields' => array_keys($detailData),
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            // Log the overall update
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Claim details updated successfully - Claim UUID: '.$claimRequest->uuid, [
                'claim_uuid' => $claimRequest->uuid,
                'code' => $claimRequest->code,
                'updated_by' => Auth::id(),
                'main_table_updates' => ! empty($claimRequestData),
                'details_table_updates' => ! empty($detailData),
            ]);

            return $claimRequest->fresh(['claimRequestDetails', 'manager', 'claimStatus', 'claimType', 'claimRequestType']);

        } catch (\Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error updating claim details - Claim UUID: '.$uuid, extra: [
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
        return ClaimStatus::where('parent', false)
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
        return ClaimStatus::where('parent', true)
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
            $claimInitiatedStatus = ClaimStatus::where('text', $claimRegisterStatusKey)->where('quote_type_id', $claimRequest->quote_type_id)->where('is_active', 1)->where('parent', 0)->first();

            // If no specific status found for the quote type, try to find a general one
            if (! $claimInitiatedStatus) {
                $claimInitiatedStatus = ClaimStatus::where('text', $claimRegisterStatusKey)->whereNull('quote_type_id')->where('is_active', 1)->where('parent', 0)->first();
            }

            if ($claimInitiatedStatus) {
                // Update the claim sub status without triggering another observer event
                $claimRequest->claim_sub_status_id = $claimInitiatedStatus->id;

                LoggerService::info(self::class.'::'.__FUNCTION__.' - Claim sub status updated to "Claim registered" - Claim UUID: '.$claimRequest->uuid, extra: [
                    'claim_request_id' => $claimRequest->id,
                    'claim_uuid' => $claimRequest->uuid,
                    'claim_sub_status_id' => $claimInitiatedStatus->id,
                    'quote_type_id' => $claimRequest->quote_type_id,
                    'trigger' => 'claim_number_entered',
                    'updated_by' => Auth::id(),
                ]);
            } else {
                LoggerService::warning(self::class.'::'.__FUNCTION__.' - Could not find "Claim registered" status - Claim UUID: '.$claimRequest->uuid, extra: [
                    'claim_request_id' => $claimRequest->id,
                    'claim_uuid' => $claimRequest->uuid,
                    'quote_type_id' => $claimRequest->quote_type_id,
                ]);
            }
        } catch (\Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error updating claim sub status to "Claim registered" - Claim UUID: '.$claimRequest->uuid, extra: [
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

        return in_array($newClaimSubStatusCode, $subStatusListForClaimClosed);
    }

    public function markClaimAsClosed(ClaimRequest $claimRequest): void
    {
        $claimStatusClosed = ClaimStatus::where('text', ClaimsEnum::CLAIM_STATUS_CLOSED)->where('is_active', 1)->first();
        if ($claimStatusClosed) {
            $claimRequest->update(['claim_status_id' => $claimStatusClosed->id]);
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Claim status updated to "Closed" - Claim UUID: '.$claimRequest->uuid, extra: [
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
    public function updateClaimStatus(string $uuid, $request): ClaimRequest
    {
        $claimRequest = $this->getClaimById($uuid);

        if (! $claimRequest) {
            throw new \Exception("Claim request not found with UUID: {$uuid}");
        }

        try {
            // Prepare the status update data
            $statusUpdateData['claim_status_id'] = $request->claim_status_id;
            $notes = $request->notes;

            ClaimActivity::createForClaim(
                $claimRequest->id, $claimRequest->uuid, $request->claim_status_id,  $notes
            );

            // Update the claim request
                $claimRequest->update($statusUpdateData);

                LoggerService::info(self::class.'::'.__FUNCTION__.' - Claim status updated successfully - Claim UUID: '.$claimRequest->uuid, [
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

            return $claimRequest->fresh(['claimStatus', 'claimSubStatus', 'manager']);

        } catch (\Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error updating claim status - Claim UUID: '.$uuid, extra: [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'claim_request_id' => $uuid,
                'data' => $request,
                'updated_by' => auth()->id(),
            ]);

            throw $e;
        }
    }

    public function getClaimDocumentTypes($quoteTypeId)
    {
        $claimDocumentTypes = DocumentType::active()
            ->whereIn('category', [DocumentTypeCode::CLAIM])
            ->where('quote_type_id', $quoteTypeId)
            ->sortDocumentType()
            ->get();

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
        $targetStatus = $this->checkSubStatusForClaimClosure($claimRequest, $subStatus->text) ? ClaimsEnum::CLAIM_STATUS_CLOSED->value : null;
        if ($targetStatus) {
            $updateClaimData['claim_status_id'] = ClaimStatus::where('text', $targetStatus)->where('parent', true)->where('is_active', 1)->first()?->id;
        }

        $claimRequest->update($updateClaimData);

        $claimActivity = ClaimActivity::createForClaim(
            $claimRequest->id, $claimRequest->uuid, $request->claim_sub_status_id,
             $request->customer_message, $request->ai_optimized_message
        );

        return $claimActivity;
    }

    /**
     * Upload multiple documents for a claim
     */
    public function uploadClaimDocuments(ClaimRequest $claim, array $files, array $documentData): array
    {
        $uploadedDocuments = [];
        $errors = [];

        foreach ($files as $file) {
            try {
                $document = app(QuoteDocumentService::class)->uploadQuoteDocument(
                    $file,
                    array_merge($documentData, [
                        'claim_id' => $claim->id,
                        'quote_id' => $claim->id,
                        'claim_uuid' => $claim->uuid,
                        'quote_uuid' => $claim->uuid,
                    ]),
                    $claim
                );

                if ($document) {
                    $uploadedDocuments[] = $document;

                    LoggerService::info(self::class.'::'.__FUNCTION__.' - Document uploaded successfully', extra: [
                        'claim_uuid' => $claim->uuid,
                        'document_id' => $document->id ?? null,
                        'document_name' => $document->original_name ?? 'Unknown',
                        'document_type' => $documentData['document_type_code'],
                        'user_id' => auth()->id(),
                    ]);
                } else {
                    $errors[] = "Failed to upload document: {$file->getClientOriginalName()}";
                }
            } catch (\Exception $e) {
                $errors[] = "Error uploading {$file->getClientOriginalName()}: {$e->getMessage()}";

                LoggerService::error(self::class.'::'.__FUNCTION__.' - Document upload failed', extra: [
                    'claim_uuid' => $claim->uuid,
                    'file_name' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ], exception: $e);
            }
        }

        // Update claim status if needed based on document uploads
        $this->updateClaimStatusOnDocumentUpload($claim, $uploadedDocuments, $documentData);

        return [
            'uploaded_documents' => $uploadedDocuments,
            'errors' => $errors,
            'success_count' => count($uploadedDocuments),
            'error_count' => count($errors),
        ];
    }

    /**
     * Update claim status based on document upload
     */
    protected function updateClaimStatusOnDocumentUpload(ClaimRequest $claim, array $uploadedDocuments, array $documentData): void
    {
        if (empty($uploadedDocuments)) {
            return;
        }

        // Check if this is a critical document type that affects claim processing
        $criticalDocumentTypes = $this->getCriticalDocumentTypes();

        if (in_array($documentData['document_type_code'], $criticalDocumentTypes)) {
            // Log the critical document upload for business logic
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Critical document uploaded', extra: [
                'claim_uuid' => $claim->uuid,
                'document_type' => $documentData['document_type_code'],
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Get critical document types that affect claim processing
     */
    protected function getCriticalDocumentTypes(): array
    {
        // Define document types that are critical for claim processing
        return [
            'POLICE_REPORT',
            'MEDICAL_REPORT',
            'REPAIR_ESTIMATE',
            'INVOICE',
            'PROOF_OF_LOSS',
            'CLAIM_FORM',
        ];
    }

    /**
     * Delete a claim document with validation
     */
    public function deleteClaimDocument(ClaimRequest $claim, $documentId): bool
    {
        try {
            $document = $claim->documents()->where('id', $documentId)->first();

            if (!$document) {
                LoggerService::warning(self::class.'::'.__FUNCTION__.' - Document not found', extra: [
                    'claim_uuid' => $claim->uuid,
                    'document_id' => $documentId,
                    'user_id' => auth()->id(),
                ]);
                return false;
            } 

            $document->delete();

            LoggerService::info(self::class.'::'.__FUNCTION__.' - Document deleted successfully', extra: [
                'claim_uuid' => $claim->uuid,
                'document_id' => $documentId,
                'document_name' => $document->original_name,
                'user_id' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error deleting document', extra: [
                'claim_uuid' => $claim->uuid,
                'document_id' => $documentId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], exception: $e);

            return false;
        }
    } 

    public function updateClaimSubStatusToRepairApprovedAndWIP(ClaimRequest $claimRequest): void
    {
        $claimStatusClosed = ClaimStatus::where('text', ClaimsEnum::CLAIM_SUB_STATUS_REPAIR_APPROVED_AND_WORK_IN_PROGRESS)->where('is_active', 1)->first();
        if ($claimStatusClosed) {
            $claimRequest->update(['claim_sub_status_id' => $claimStatusClosed->id]);
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Claim status updated to "Closed" - Claim UUID: '.$claimRequest->uuid, extra: [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'claim_sub_status_id' => $claimStatusClosed->id,
                'updated_by' => Auth::id(),
            ]);
        }
    }

    public function updateClaimSubStatusToTotalLossOfferLetterShared(ClaimRequest $claimRequest): void
    {
        $claimStatusClosed = ClaimStatus::where('text', ClaimsEnum::CLAIM_SUB_STATUS_TOTAL_LOSS_OFFER_LETTER_SHARED)->where('is_active', 1)->first();
        if ($claimStatusClosed) {
            $claimRequest->update(['claim_sub_status_id' => $claimStatusClosed->id]);
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Claim status updated to "Closed" - Claim UUID: '.$claimRequest->uuid, extra: [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'claim_sub_status_id' => $claimStatusClosed->id,
                'updated_by' => Auth::id(),
            ]);
        }
    }

    public function updateClaimSubStatusToCashLossApproved(ClaimRequest $claimRequest): void
    {
        $claimStatusClosed = ClaimStatus::where('text', ClaimsEnum::CLAIM_SUB_STATUS_CASH_LOSS_APPROVED)->where('is_active', 1)->first();
        if ($claimStatusClosed) {
            $claimRequest->update(['claim_sub_status_id' => $claimStatusClosed->id]);
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Claim status updated to "Closed" - Claim UUID: '.$claimRequest->uuid, extra: [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'claim_sub_status_id' => $claimStatusClosed->id,
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

        return $closedStatus !== null && $closedStatus->text === ClaimsEnum::CLAIM_STATUS_CLOSED->value;
    }

    /**
     * Dispatch Google review email job for the claim request
     */
    public function dispatchGoogleReviewEmail(ClaimRequest $claimRequest): void
    {
        try {
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Dispatching Google review email job - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'customer_email' => $claimRequest->email,
            ]);

            // Dispatch the job to send Google review email
            SendGoogleReviewEmailJob::dispatch($claimRequest->uuid);

        } catch (\Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Failed to dispatch Google review email job - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Create and download ZIP file containing all claim documents
     * @return array
     * @throws Exception
     */
    public function createDocumentsZip(ClaimRequest $claim): array
    {
        $documents = $claim->documents;

        $this->validateDocumentsForZip($documents);

        $zipFileName = $this->generateZipFileName($claim);
        $zipFilePath = storage_path('temp/' . $zipFileName);
        
        $result = [
            'success' => false,
            'file_path' => null,
            'file_name' => $zipFileName,
            'processed_count' => 0,
            'total_count' => count($documents),
            'errors' => [],
        ];

        try {
            $zip = new ZipArchive;
            
            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Could not create ZIP file at: ' . $zipFilePath);
            }

            $processedDocuments = $this->addDocumentsToZip($zip, $documents, $claim);
            $zip->close();

            if (empty($processedDocuments)) {
                $this->cleanupZipFile($zipFilePath);
                throw new Exception('No documents were successfully added to the ZIP file');
            }

            $result['success'] = true;
            $result['file_path'] = $zipFilePath;
            $result['processed_count'] = count($processedDocuments);

            LoggerService::info(self::class.'::'.__FUNCTION__.' - ZIP file created successfully', extra: [
                'claim_uuid' => $claim->uuid,
                'processed_documents_count' => count($processedDocuments),
                'zip_file_name' => $zipFileName,
                'user_id' => Auth::id(),
            ]);

            return $result;

        } catch (Exception $e) {
            $this->cleanupZipFile($zipFilePath);
            
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error creating ZIP file', extra: [
                'claim_uuid' => $claim->uuid,
                'error' => $e->getMessage(),
                'zip_file_path' => $zipFilePath,
                'user_id' => Auth::id(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate documents for ZIP creation
     *
     * @throws Exception
     */
    private function validateDocumentsForZip($documents): void
    {
        if (!$documents || (is_countable($documents) && count($documents) === 0)) {
            throw new Exception('No documents available for this claim');
        }

        // If it's a collection, convert to array for processing
        if ($documents instanceof \Illuminate\Support\Collection) {
            $documents = $documents->toArray();
        }

        // Validate document structure
        foreach ($documents as $document) {
            $docUrl = is_array($document) ? ($document['doc_url'] ?? null) : $document->doc_url ?? null;
            $originalName = is_array($document) ? ($document['original_name'] ?? null) : $document->original_name ?? null;
            
            if (!$docUrl || !$originalName) {
                throw new Exception('Invalid document structure: missing required fields (doc_url or original_name)');
            }
        }
    }

    /**
     * Generate ZIP file name for claim documents
     *
     * @return string
     */
    private function generateZipFileName(ClaimRequest $claim): string
    {
        $firstName = $this->sanitizeFileName($claim->first_name ?? 'Customer');
        $lastName = $this->sanitizeFileName($claim->last_name ?? 'Docs');
        $claimCode = $this->sanitizeFileName($claim->uuid);
        
        return "Claim_{$claimCode}_{$firstName}_{$lastName}_" . date('Y-m-d_H-i-s') . '.zip';
    }

    /**
     * Sanitize filename to remove invalid characters
     *
     * @param string $filename
     * @return string
     */
    private function sanitizeFileName(string $filename): string
    {
        // Remove or replace invalid filename characters
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
        return substr($filename, 0, 50); // Limit length
    }

    /**
     * Add documents to ZIP archive
     *
     */
    private function addDocumentsToZip(ZipArchive $zip, $documents, ClaimRequest $claim): array
    {
        $disk = Storage::disk('azureIM');
        $processedDocuments = [];
        $documentCounts = []; // Track duplicate names

        // Convert collection to array if needed
        if ($documents instanceof \Illuminate\Support\Collection) {
            $documents = $documents->toArray();
        }

        foreach ($documents as $document) {
            try {
                // Handle both array and object formats
                $docUrl = is_array($document) ? $document['doc_url'] : $document->doc_url;
                $originalName = is_array($document) ? $document['original_name'] : $document->original_name;
                $documentId = is_array($document) ? ($document['id'] ?? null) : $document->id ?? null;

                if (!$disk->exists($docUrl)) {
                    LoggerService::warning(self::class.'::'.__FUNCTION__.' - Document does not exist', extra: [
                        'doc_url' => $docUrl,
                        'document_name' => $originalName,
                        'document_id' => $documentId,
                        'claim_uuid' => $claim->uuid,
                    ]);
                    continue;
                }

                // Handle duplicate filenames
                $finalName = $this->getUniqueFileName($originalName, $documentCounts);
                
                $contents = $disk->get($docUrl);
                
                if ($zip->addFromString($finalName, $contents)) {
                    $processedDocuments[] = [
                        'name' => $finalName,
                        'original_name' => $originalName,
                        'id' => $documentId,
                    ];
                } else {
                    LoggerService::warning(self::class.'::'.__FUNCTION__.' - Failed to add document to ZIP', extra: [
                        'document_name' => $originalName,
                        'final_name' => $finalName,
                        'document_id' => $documentId,
                        'claim_uuid' => $claim->uuid,
                    ]);
                }

            } catch (Exception $e) {
                $documentName = 'unknown';
                try {
                    $documentName = is_array($document) ? ($document['original_name'] ?? 'unknown') : $document->original_name ?? 'unknown';
                } catch (Exception $nameEx) {
                    // Fallback if we can't get the name
                }

                LoggerService::warning(self::class.'::'.__FUNCTION__.' - Error processing document', extra: [
                    'document_name' => $documentName,
                    'error' => $e->getMessage(),
                    'claim_uuid' => $claim->uuid,
                ]);
            }
        }

        return $processedDocuments;
    }

    /**
     * Get unique filename to handle duplicates
     *
     * @param string $originalName
     * @param array &$documentCounts
     * @return string
     */
    private function getUniqueFileName(string $originalName, array &$documentCounts): string
    {
        if (!isset($documentCounts[$originalName])) {
            $documentCounts[$originalName] = 1;
            return $originalName;
        }

        $documentCounts[$originalName]++;
        $pathInfo = pathinfo($originalName);
        $name = $pathInfo['filename'] ?? $originalName;
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        
        return $name . '_(' . $documentCounts[$originalName] . ')' . $extension;
    }

    /**
     * Clean up ZIP file if it exists
     *
     * @param string $zipFilePath
     */
    private function cleanupZipFile(string $zipFilePath): void
    {
        if (file_exists($zipFilePath)) {
            try {
                unlink($zipFilePath);
            } catch (Exception $e) {
                LoggerService::warning(self::class.'::'.__FUNCTION__.' - Failed to cleanup ZIP file', extra: [
                    'file_path' => $zipFilePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get claim lead history (status changes by team lead) - all data for client-side pagination
     * 
     * @param int $claimId
     * @return array
     */
    public function getClaimLeadHistory(int $claimId)
    {
        try {
            // Single optimized query using LAG window function to get previous status
            $claimLeadHistory = DB::table('claim_activities as ca')
                ->join('claim_statuses as cs', 'ca.status_id', '=', 'cs.id')
                ->join('users as u', 'ca.created_by_id', '=', 'u.id')
                ->select(
                    DB::raw('DATE_FORMAT(ca.created_at, "%d-%m-%Y %H:%i:%s") as ModifiedAt'),
                    'ca.comment as Notes',
                    'u.name as ModifiedBy',
                    'cs.text as NewStatus',
                    // Use LAG window function to get the previous status in the same query
                    DB::raw('LAG(cs.text) OVER (ORDER BY ca.created_at ASC) as oldStatus')
                )
                ->where('ca.claim_request_id', $claimId)
                ->where('cs.parent', true) // Only get parent statuses (main claim statuses)
                ->orderBy('ca.created_at', 'desc')
                ->get();

             

            LoggerService::info(self::class.'::'.__FUNCTION__.' - Claim lead history fetched for client-side pagination', extra: [
                'claim_id' => $claimId,
                'total_records' => count($claimLeadHistory),
                'user_id' => auth()->id(),
            ]);

            return $claimLeadHistory;

        } catch (\Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error fetching claim lead history', [
                'error' => $e->getMessage(),
                'claim_id' => $claimId,
                'user_id' => auth()->id(),
            ]);

            throw $e;
        }
    }

    /**
     * Get claim sub-status logs (sub-status changes by claims manager) - all data for client-side pagination
     * 
     * @param int $claimId
     * @return array
     */
    public function getClaimSubStatusLogs(int $claimId)
    {
        try {
            // Single optimized query using LAG window function to get previous sub-status
            $claimSubStatusLogs = DB::table('claim_activities as ca')
                ->join('claim_statuses as cs', 'ca.status_id', '=', 'cs.id')
                ->join('users as u', 'ca.created_by_id', '=', 'u.id')
                ->select(
                    DB::raw('DATE_FORMAT(ca.created_at, "%d-%m-%Y %H:%i:%s") as ModifiedAt'),
                    'u.name as ModifiedBy',
                    'cs.text as NewSubStatus',
                    'ca.comment as Notes',
                    // Use LAG window function to get the previous sub-status in the same query
                    DB::raw('LAG(cs.text) OVER (ORDER BY ca.created_at ASC) as OldSubStatus')
                )
                ->where('ca.claim_request_id', $claimId)
                ->where('cs.parent', false) // Only get sub-statuses (not parent statuses)
                ->whereNotNull('ca.status_id')
                ->orderBy('ca.created_at', 'desc')
                ->get();

             

            LoggerService::info(self::class.'::'.__FUNCTION__.' - Claim sub-status logs fetched for client-side pagination', extra: [
                'claim_id' => $claimId,
                'total_records' => count($claimSubStatusLogs),
                'user_id' => auth()->id(),
            ]);

            return $claimSubStatusLogs;

        } catch (\Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error fetching claim sub-status logs', extra: [
                'error' => $e->getMessage(),
                'claim_id' => $claimId,
                'user_id' => auth()->id(),
            ]);

            throw $e;
        }
    }

}
