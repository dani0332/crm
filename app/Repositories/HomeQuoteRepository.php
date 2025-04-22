<?php

namespace App\Repositories;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\HomePossessionType;
use App\Enums\LeadSourceEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentTooltip;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\TeamNameEnum;
use App\Facades\Capi;
use App\Jobs\SaveCustomerAddressJob;
use App\Jobs\SendHomeOCBIntroEmailJob;
use App\Models\ApplicationStorage;
use App\Models\DocumentType;
use App\Models\Emirate;
use App\Models\HomeQuote;
use App\Models\PersonalQuote;
use App\Models\SubArea;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\CustomerService;
use App\Services\DropdownSourceService;
use App\Services\EmailStatusService;
use App\Services\Logger\LoggerService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Traits\AddPremiumAllLobs;
use App\Traits\CentralTrait;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class HomeQuoteRepository extends BaseRepository
{
    use AddPremiumAllLobs, CentralTrait, GenericQueriesAllLobs;

    public function model()
    {
        return PersonalQuote::class;
    }

    public function fetchExport()
    {
        return $this->filter()->with(
            ['advisor', 'nationality', 'insuranceProvider']
        )->orderBy('created_at', 'desc');
    }

    public function fetchGetData(bool $forExport = false, bool $forTotalLeadsCount = false)
    {
        $excludeCreatedAtFilters = [
            'email',
            'mobile_no',
            'code',
            'renewal_batch',
            'previous_quote_policy_number',
            'payment_due_date',
            'booking_date',
        ];

        // Check if any of the exclude filters are active
        $shouldExcludeCreatedAtFilters = $this->hasActiveFilters($excludeCreatedAtFilters);
        
        return $this->byQuoteTypeCode(QuoteTypes::HOME)
            ->with($this->getWithRelations())
            ->when(auth()->user()->hasRole(RolesEnum::HomeAdvisor), fn ($query) => $query->where('advisor_id', auth()->id()))
            ->when(request()->filled('advisors'), function ($query) {
                $advisors = (array) request('advisors');
                $hasUnassigned = in_array('-1', $advisors);
                $hasOtherAdvisors = count(array_filter($advisors, fn($id) => $id !== '-1')) > 0;

                // If both unassigned and specific advisors are selected
                if ($hasUnassigned && $hasOtherAdvisors) {
                    $filteredAdvisors = array_filter($advisors, fn($id) => $id !== '-1');
                    return $query->where(function($q) use ($filteredAdvisors) {
                        $q->whereIn('advisor_id', $filteredAdvisors)
                          ->orWhereNull('advisor_id');
                    });
                }
                
                // If only unassigned is selected
                if ($hasUnassigned) {
                    return $query->whereNull('advisor_id');
                }
                
                // If only specific advisors are selected
                return $query->whereIn('advisor_id', $advisors);
            })
            ->when(request()->has('is_renewal'), fn ($query) => $this->applyRenewalFilter($query))
            ->tap(fn ($query) => $this->applyFilters($query))
            ->when(! $shouldExcludeCreatedAtFilters, function ($query) {
                if (request()->filled('created_at_start') && request()->filled('created_at_end')) {
                    $query->whereBetween('personal_quotes.created_at', [request('created_at_start'), request('created_at_end')]);
                } else {
                    $query->whereBetween('personal_quotes.created_at', $this->getDateRange());
                }
            })
            ->filter(! $forExport, $forTotalLeadsCount)
            ->withFakeLeadCriteria($forTotalLeadsCount)
            ->orderBy('personal_quotes.created_at', 'desc')
            ->when(
                $forTotalLeadsCount,
                fn ($query) => $query->count(),
                fn ($query) => $query->when($forExport, fn ($query) => $query->get(), fn ($query) => $query->simplePaginate()->withQueryString())
            );
    }

    /**
     * Check if any of the specified filters are active.
     */
    private function hasActiveFilters(array $fields): bool
    {
        foreach ($fields as $field) {
            if (request()->filled($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the eager load relationships for the query.
     */
    private function getWithRelations(): array
    {
        return [
            'quoteDetail.lostReason',
            'quoteStatus',
            'advisor',
            'nationality',
            'homeQuote',
            'homeQuote.homeQuoteRequestDetail',
            'homeQuote.homeQuoteRequestDetail.lostReason',
            'payments' => function ($query) {
                $query->with([
                    'paymentStatus',
                    'personalPlan',
                    'paymentMethod',
                    'paymentStatusLogs',
                    'insuranceProvider',
                    'paymentable',
                    'paymentSplits.paymentStatus',
                    'paymentSplits.paymentMethod',
                    'paymentSplits.verifiedByUser',
                    'paymentSplits.documents',
                    'paymentSplits.processJob',
                ]);
            },
        ];
    }

    /**
     * Apply the renewal filter to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function applyRenewalFilter($query): void
    {
        if (request('is_renewal') === quoteTypeCode::yesText) {
            $query->whereNotNull('personal_quotes.previous_quote_policy_number');
        } elseif (request('is_renewal') === quoteTypeCode::noText) {
            $query->whereNull('personal_quotes.previous_quote_policy_number');
        }
    }

    public function fetchCreate($data)
    {
        $baseQuoteData = [
            'quoteTypeId' => intval(QuoteTypes::HOME->id()),
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => URL::current(),
            'createdById' => auth()->id(),
            'advisorId' => (! auth()->user()->hasRole(RolesEnum::Admin)) ? auth()->id() : null,
        ];

        // only add keys in payload if they are set and not empty
        $optionalFields = [
            'hasContents' => 'has_contents',
            'hasBuilding' => 'has_building',
            'hasClaimedLosses' => 'have_claimed_losses',
            'buildingValue' => 'building_aed',
            'hasPersonalBelongings' => 'has_personal_belongings',
            'ownerOccupancyTypeId' => 'owner_occupancy_type_id',
            'accommodationTypeId' => 'ilivein_accommodation_type_id',
            'possessionTypeId' => 'iam_possesion_type_id',
            'subAreaId' => 'sub_area_id',
            'contentsValueId' => 'contents_aed',
            'personalBelongingsValueId' => 'personal_belongings_aed',
            'coverageTypeId' => 'type_of_coverage_you_need',
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'email' => 'email',
            'mobileNo' => 'mobile_no',
            'dob' => 'dob',
            'nationalityId' => 'nationality_id',
            'gender' => 'gender',
            'companyName' => 'company_name',
            'companyAddress' => 'company_address',
        ];

        foreach ($optionalFields as $key => $field) {
            if (isset($data[$field]) && ! empty($data[$field])) {
                $baseQuoteData[$key] = $data[$field];
            }
        }

        $quoteData = $baseQuoteData;

        $response = Capi::request('/api/v2-save-home-quote', 'post', $quoteData);

        try {
            if (isset($response->quoteUID)) {
                LoggerService::startQuoteLogging($response->quoteUID);
                info('Dispatching SaveCustomerAddressJob');
                // add Address fields to Customer Address table
                $addressData = $data['addressObj'] ?? [];
                if (! empty($addressData)) {
                    SaveCustomerAddressJob::dispatch($response->quoteUID, $addressData);
                }

                // dispatch job to send Home OCB intro email if user is not an admin
                if (! auth()->user()->hasRole(RolesEnum::Admin)) {
                    SendHomeOCBIntroEmailJob::dispatch($response->quoteUID)->delay(now()->addMinutes(1));
                }
                LoggerService::endLogging();
            }
        } catch (\Exception $e) {
            info('Failed to dispatch SaveCustomerAddressJob', ['error' => $e->getMessage()]);
        }

        return $response;
    }

    public function fetchGetShowFormOptions($quote)
    {
        $cacheExpiry = now()->endOfDay();

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::HOME->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee', 'quoteStatus')->orderBy('created_at', 'desc')->get();
        $quoteNotes = QuoteNoteRepository::getBy($quote->id, quoteTypeCode::Home);

        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = (new LookupService)->getSendUpdateOptions(QuoteTypes::HOME->id());
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }
        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails(QuoteTypes::HOME->value, $quote);
        $quoteDocuments = (new QuoteDocumentService)->getQuoteDocuments(QuoteTypes::HOME->value, $quote->id);
        $bookPolicyDetails = $this->bookPolicyPayload($quote, QuoteTypes::HOME->value, $quote->payments, $quoteDocuments);

        @[$documentTypes, $paymentDocument] = Cache::remember('home_document_types', $cacheExpiry, function () {
            return app(QuoteDocumentService::class)->getDocumentTypes(QuoteTypeId::Home);
        });

        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($quote);
        $isQuoteDocumentEnabled = app(QuoteDocumentService::class)->isEnabled(QuoteTypes::HOME->value);
        $leadStatuses = Cache::remember('home_lead_statuses', $cacheExpiry, function () {
            return app(DropdownSourceService::class)->getDropdownSource('quote_status_id', QuoteTypeId::Home);
        });
        $amlStatusName = AMLStatusCode::getName($quote->aml_status);

        // fetch sub area for the quote
        // Check if homeQuote exists and sub_area_id is valid before fetching the sub area
        if (isset($quote->homeQuote) && $quote->homeQuote->sub_area_id) {
            $subArea = $this->getSubArea($quote->homeQuote->sub_area_id);
            $quote->homeQuote->subArea = $subArea ?? null;
        }

        $quoteStatuses = Cache::remember('home_quote_statuses', $cacheExpiry, function () {
            $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::HOME->id())->get();

            return collect($quoteStatuses)->filter(function ($value) {
                return ! in_array($value['id'], [QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::AMLScreeningFailed]);
            })->values();
        });
        $quoteStatuses = app(CentralService::class)->lockTransactionStatus($quote, QuoteTypes::HOME->id(), $quoteStatuses);

        $planURL = $this->getEcomQuoteLink(QuoteTypes::HOME, $quote->uuid);
        $allowedDuplicateLOB = app(CRUDService::class)->getAllowedDuplicateLOB('home', $quote->code);
        $emailStatuses = app(EmailStatusService::class)->getEmailStatus(QuoteTypeId::Home, $quote->id);
        $customerAddressData = $quote->customerAddressData ?: app(CustomerService::class)->getCustomerAddressData($quote);
        $lookUpData = $quote->lookUpData ?: app(LookupService::class)->getHomeLookUpData();

        // Check if TAP integration is enabled
        $isFuncsEnabled = ['tapIntegration' => isTapEnabled()];

        return [
            'documentTypes' => $documentTypes,
            'paymentDocument' => $paymentDocument,
            'storageUrl' => storageUrl(),
            'quoteType' => QuoteTypes::HOME,
            'quoteTypeId' => QuoteTypeId::Home,
            'quoteStatuses' => $quoteStatuses,
            'emailStatuses' => $emailStatuses,
            'quote' => $quote,
            'activities' => $activities,
            'advisors' => UserRepository::getPersonalQuoteAdvisors(QuoteTypes::HOME->value),
            'duplicateAllowedLobs' => (new CentralService)->duplicateAllowedLobsList(QuoteTypes::HOME->value, $quote->code),
            'customerAdditionalContacts' => CustomerRepository::GetAdditionalContacts($quote->customer_id, $quote->mobile_no),
            'lostReasons' => LostReasonRepository::orderBy('text', 'asc')->get(),
            'quoteStatusEnum' => QuoteStatusEnum::asArray(),
            'modelType' => QuoteTypes::HOME,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::HomeManager),
            'embeddedProducts' => EmbeddedProductRepository::byQuoteType(QuoteTypes::HOME->id(), $quote->id),
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'nationalities' => NationalityRepository::withActive()->get(),
            'memberRelations' => LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get(),
            'membersDetails' => CustomerMembersRepository::getBy($quote->id, QuoteTypes::HOME->name),
            'industryType' => LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get(),
            'emirates' => Emirate::withActive()->select('id', 'text')->get(),
            'UBOsDetails' => CustomerMembersRepository::getBy($quote->id, QuoteTypes::HOME->name, CustomerTypeEnum::Entity),
            'UBORelations' => LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get(),
            'isNewPaymentStructure' => app(SplitPaymentService::class)->isNewPaymentStructure($quote->payments),
            'paymentMethods' => (new LookupService)->getPaymentMethods(),
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'payments' => $quote->payments,
            'insuranceProviders' => InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::HOME->id()),
            'vatPercentage' => ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()?->value ?? 0,
            'amlStatusName' => $amlStatusName,
            'leadSource' => LeadSourceEnum::asArray(),
            'quoteNotes' => $quoteNotes,
            'cdnPath' => config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/',
            'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
            'noteDocumentType' => DocumentType::where('code', DocumentTypeCode::OD)->first(),
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'sendUpdateEnum' => $sendUpdateEnum,
            'linkedQuoteDetails' => $linkedQuoteDetails,
            'bookPolicyDetails' => $bookPolicyDetails,
            'lockLeadSectionsDetails' => $lockLeadSectionsDetails,
            'permissions' => [
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
            ],
            'leadStatuses' => $leadStatuses,
            'planURL' => $planURL,
            'allowedDuplicateLOB' => $allowedDuplicateLOB,
            'customerAddressData' => $customerAddressData,
            'lookUpData' => $lookUpData,
            'isFuncsEnabled' => $isFuncsEnabled,
            'homePossessionTypeEnum' => HomePossessionType::asArray(),
        ];
    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        return Capi::request('/api/v1-save-'.strtolower(QuoteTypes::HOME->value).'-quote', 'post', $dataArr);
    }

    public function fetchUpdate($uuid, $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            try {
                // Find the quote by UUID or fail if not found
                $quote = $this->byQuoteTypeId(QuoteTypes::HOME->id())
                    ->where('uuid', $uuid)
                    ->firstOrFail();

                // Prepare the data for the PersonalQuote update
                $personalQuoteColumns = (new PersonalQuote)->allowedColumns();
                $quoteData = Arr::only($data, $personalQuoteColumns);
                $quoteData['updated_by_id'] = auth()->user()->id;

                // Update PersonalQuote data
                $quote->update($quoteData);

                // Mapping frontend fields to backend column names
                $fieldMapping = [
                    'iam_possesion_type_id' => 'possession_type_id',
                    'ilivein_accommodation_type_id' => 'accommodation_type_id',
                    'address' => 'address',
                    'has_contents' => 'has_contents',
                    'has_building' => 'has_building',
                    'has_personal_belongings' => 'has_personal_belongings',
                    'contents_aed' => 'contents_value_id',
                    'building_aed' => 'building_value',
                    'personal_belongings_aed' => 'personal_belongings_value_id',
                    'have_claimed_losses' => 'has_claimed_losses',
                    'owner_occupancy_type_id' => 'owner_occupancy_type_id',
                    'sub_area_id' => 'sub_area_id',
                    'type_of_coverage_you_need' => 'coverage_type_id',
                ];

                // Map the data to database columns
                $mappedData = [];
                foreach ($fieldMapping as $frontendField => $dbField) {
                    // Ensure we only add data that exists in the request
                    if (array_key_exists($frontendField, $data)) {
                        $mappedData[$dbField] = $data[$frontendField];
                    }
                }

                $mappedData['personal_quote_id'] = $quote->id;

                // Track which fields were changed
                $fieldsChanged = false;
                $existingHomeQuote = HomeQuote::where('uuid', $uuid)->first();

                if ($existingHomeQuote) {
                    // Check if any of the fields that require a plan update have changed
                    $fieldsThatTriggerPlanUpdate = [
                        'possession_type_id',
                        'accommodation_type_id',
                        'coverage_type_id',
                        'building_value',
                        'contents_value_id',
                        'personal_belongings_value_id',
                    ];

                    foreach ($fieldsThatTriggerPlanUpdate as $field) {
                        if (isset($mappedData[$field]) && $mappedData[$field] != $existingHomeQuote->$field) {
                            $fieldsChanged = true;
                            break;
                        }
                    }

                    // If a record with this UUID exists, update it directly
                    $existingHomeQuote->update($mappedData);
                } else {
                    // No record exists with this UUID, so it's safe to create a new one
                    $mappedData['uuid'] = $uuid;
                    HomeQuote::create($mappedData);
                    $fieldsChanged = true; // New record, so treat as changed
                }

                // Refresh the quote to load the updated or newly created homeQuote
                $quote->refresh();

                // Process address data if provided
                if (! empty($data['addressObj']) || is_array($data['addressObj'])) {
                    // Update or create the customer address
                    SaveCustomerAddressJob::dispatch($uuid, $data['addressObj']);
                }

                // If specific fields changed, call the getQuotePlans method with getLatestRating=true
                if ($fieldsChanged) {
                    app(\App\Services\HomeQuoteService::class)->getQuotePlans($quote->id, ['getLatestRating' => true]);
                }

                // Return the updated quote
                return $quote;
            } catch (\Exception $e) {
                // Log error details to help with debugging
                info("Failed to update quote with UUID: {$uuid}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'data' => $data,
                ]);
                throw $e; // Re-throw exception to trigger transaction rollback
            }
        });
    }

    public function fetchCardsView(Request $request)
    {
        $quotes = [
            ['id' => QuoteStatusEnum::NewLead, 'title' => quoteStatusCode::NEW_LEAD, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::NewLead, $request)],
            ['id' => QuoteStatusEnum::Allocated, 'title' => quoteStatusCode::ALLOCATED, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::Allocated, $request)],
            ['id' => QuoteStatusEnum::Quoted, 'title' => quoteStatusCode::QUOTED, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::Quoted, $request)],
            ['id' => QuoteStatusEnum::FollowedUp, 'title' => quoteStatusCode::FOLLOWEDUP, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::FollowedUp, $request)],
            ['id' => QuoteStatusEnum::InNegotiation, 'title' => quoteStatusCode::NEGOTIATION, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::InNegotiation, $request)],
            ['id' => QuoteStatusEnum::PaymentPending, 'title' => quoteStatusCode::PAYMENTPENDING, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::PaymentPending, $request)],
            ['id' => QuoteStatusEnum::TransactionApproved, 'title' => quoteStatusCode::TRANSACTIONAPPROVED, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::TransactionApproved, $request)],
            ['id' => QuoteStatusEnum::PolicyIssued, 'title' => quoteStatusCode::POLICY_ISSUED, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::PolicyIssued, $request)],
        ];

        $quoteStatusEnums = QuoteStatusEnum::asArray();
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        $userId = auth()->id();
        $userTeams = auth()->user()->getUserTeams($userId)->toArray();
        if (array_intersect([TeamNameEnum::HOME], $userTeams)) {
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::Allocated,
                QuoteStatusEnum::InNegotiation,
            ])->values()->toArray();
        } elseif (array_intersect([TeamNameEnum::HOME_RENEWALS], $userTeams)) {
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::NewLead,
                QuoteStatusEnum::InNegotiation,
            ])->values()->toArray();
        }

        $totalLeads = 0;
        $hasOtherFilters = count(array_diff_key(request()->all(), ['page' => ''])) > 0;

        foreach ($quotes as $item) {
            $totalLeads += $item['data']['total_leads'];
        }

        $advisors = app(CRUDService::class)->getAdvisorsByModelType(quoteTypeCode::Home);
        $leadStatuses = app(DropdownSourceService::class)->getDropdownSource('quote_status_id', QuoteTypeId::Home);

        return inertia('HomeQuote/Cards', [
            'quotes' => $quotes,
            'quoteStatusEnum' => $quoteStatusEnums,
            'lostReasons' => $lostReasons,
            'leadStatuses' => $leadStatuses,
            'advisors' => $advisors,
            'teams' => $userTeams,
            'quoteTypeId' => QuoteTypes::HOME->id(),
            'quoteType' => QuoteTypes::HOME->value,
            'totalCount' => count(request()->all()) > 1 || $hasOtherFilters ? $totalLeads : self::fetchGetData(true, true),
        ]);
    }
    public function getDateRange()
    {
        $from = now()->startOfDay()->toDateTimeString(); // default from date
        $to = now()->endOfDay()->toDateTimeString(); // default to date

        return [$from, $to];
    }

    public function getSubArea($subAreaId)
    {
        if (! $subAreaId) {
            return null;
        }

        return Cache::remember("sub_area_{$subAreaId}", now()->endOfDay(), function () use ($subAreaId) {
            return SubArea::select('id', 'text')->where('id', $subAreaId)->first();
        });
    }

    /**
     * Apply dynamic filters to the query.
     */
    private function applyFilters($query): void
    {
        $filters = $this->getFilterMappings();

        foreach ($filters as $field => $condition) {
            if (request()->filled($field)) {
                $condition($query, request($field));
            }
        }

        // Handle date range filters using adjustQueryByDateFilters
        if (request()->filled('payment_due_date') || request()->filled('booking_date')) {
            $this->adjustQueryByDateFilters($query, 'personal_quotes');
        }
    }

    /**
     * Define filter mappings for dynamic filtering.
     */
    private function getFilterMappings(): array
    {
        return [
            'code' => fn ($query, $value) => $query->where('personal_quotes.code', $value),
            'first_name' => fn ($query, $value) => $query->where('personal_quotes.first_name', 'like', "%$value%"),
            'last_name' => fn ($query, $value) => $query->where('personal_quotes.last_name', 'like', "%$value%"),
            'email' => fn ($query, $value) => $query->where('personal_quotes.email', 'like', "%$value%"),
            'mobile_no' => fn ($query, $value) => $query->where('personal_quotes.mobile_no', 'like', "%$value%"),
            'quote_status_id' => fn ($query, $value) => $query->where('personal_quotes.quote_status_id', $value),
            'policy_expiry_date' => fn ($query, $value) => $query->whereDate('personal_quotes.policy_expiry_date', '>=', $value),
            'policy_expiry_date_end' => fn ($query, $value) => $query->whereDate('personal_quotes.policy_expiry_date', '<=', $value),
            'previous_quote_policy_number' => fn ($query, $value) => $query->where('personal_quotes.previous_quote_policy_number', $value),
            'renewal_batches' => fn ($query, $value) => $query->whereIn('personal_quotes.renewal_batch', (array) $value),
            'advisor_assigned_date' => fn ($query, $value) => $query->whereDate('personal_quotes.advisor_assigned_date', $value),
            'insurer_tax_invoice_number' => fn ($query, $value) => $query->whereHas('payments', function ($query) use ($value) {
                $query->where('insurer_tax_number', $value);
            }),
            'insurer_commission_tax_invoice_number' => fn ($query, $value) => $query->whereHas('payments', function ($query) use ($value) {
                $query->where('insurer_commmission_invoice_number', $value);
            }),
        ];
    }

    public function fetchGetFormOptions()
    {
        // Fetch look up data
        $lookUpData = $this->getHomeLookUpData();

        // Fetch active nationalities
        $nationalities = $this->getNationalities();

        return $this->prepareResponse($lookUpData, $nationalities);
    }

    private function getHomeLookUpData()
    {
        return app(LookupService::class)->getHomeLookUpData();
    }

    private function getNationalities()
    {
        return NationalityRepository::withActive()->get();
    }

    private function prepareResponse($lookUpData, $nationalities)
    {
        return [
            'lookUpData' => $lookUpData,
            'homePossessionTypeEnum' => HomePossessionType::asArray(),
            'nationalities' => $nationalities,
        ];
    }

    public function fetchGetBy($column, $value)
    {
        try {
            $quote = $this->getQuoteWithRelations($column, $value);

            $this->setQuoteAdditionalData($quote);

            $this->appendExternalData($quote);

            return $quote;
        } catch (\Exception $e) {
            info('Error fetching quote data: '.$e->getMessage());
            throw $e; // Rethrow the exception so it propagates to the controller
        }
    }

    private function getQuoteWithRelations($column, $value)
    {
        $response = $this->byQuoteTypeId(QuoteTypes::HOME->id())
            ->where($column, $value)
            ->with([
                'insuranceProvider',
                'quoteDetail.lostReason',
                'quoteStatus',
                'advisor',
                'nationality',
                'plans',
                'homeQuote',
                'homeQuote.nationality',
                'homeQuote.possessionType',
                'homeQuote.accommodationType',
                'homeQuote.homeQuoteRequestDetail',
                'homeQuote.homeQuoteRequestDetail.lostReason',
                'createdBy',
                'updatedBy',
                'customer.additionalContactInfo',
                'documents' => function ($q) {
                    $q->with('createdBy')->orderBy('created_at', 'desc');
                },
                'insured' => function ($q) {
                    $q->where('customer_insured.quote_type_id', QuoteTypeId::Home);
                },
                'quoteRequestEntityMapping' => function ($entityMapping) {
                    $entityMapping->with('entity');
                },
                'payments' => function ($q) {
                    $q->with([
                        'paymentStatus',
                        'personalPlan',
                        'paymentMethod',
                        'paymentStatusLogs',
                        'insuranceProvider',
                        'paymentSplits' => function ($q) {
                            $q->with([
                                'paymentStatus',
                                'paymentMethod',
                                'documents',
                                'verifiedByUser',
                                'paymentCharges',
                            ])
                                ->orderBy('sr_no', 'asc');
                        },
                    ]);
                },
            ])
            ->select([
                $this->getTable().'.*',
                DB::raw('IF(EXISTS (
                SELECT *
                FROM quote_request_entity_mapping
                WHERE quote_type_id = '.QuoteTypeId::Home.' AND quote_request_id = '.$this->getTable().'.id),
                "'.CustomerTypeEnum::Entity.'", "'.CustomerTypeEnum::Individual.'")
            as customer_type'),
            ])
            ->firstOrFail();

        if ($response?->insured) {
            $response->emirates_id_number = $response?->insured?->id_type == 'emiratesId' ? $response?->insured?->id_number : null;
        }

        return $response;
    }

    private function setQuoteAdditionalData($quote)
    {
        $data = ! empty($quote) ? $quote->toArray() : [];

        $quote->lost_reason = $data['quote_detail']['lost_reason']['text'] ?? null;
        $quote->previous_advisor_id_text = $data['quote_detail']['previous_advisor']['name'] ?? null;
        $quote->transaction_type_text = $data['transaction_type']['text'] ?? null;

        // Setting permissions or appending additional fields to payments
        $quote->payments->each->setAppends(['allow', 'copy_link_button', 'edit_button', 'approve_button', 'approved_button']);
    }

    private function appendExternalData($quote)
    {
        // fetch look up data
        try {
            $lookUpData = app(LookupService::class)->getHomeLookUpData();
            if ($lookUpData) {
                $quote->lookUpData = $lookUpData;
            }
        } catch (\Exception $e) {
            info('Error fetching home lookup data: '.$e->getMessage());
            $quote->lookUpData = [];
        }

        // fetch customer address data and append it
        try {
            $customerAddressData = app(CustomerService::class)->getCustomerAddressData($quote);
            if ($customerAddressData) {
                $quote->customerAddressData = $customerAddressData;
            }
        } catch (\Exception $e) {
            info('Error fetching customer address data: '.$e->getMessage());
            $quote->customerAddressData = [];
        }
    }
}
