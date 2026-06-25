<?php

namespace App\Services;

use App\Builders\TravelQuoteQueryBuilder;
use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\InsuranceProviderEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TravelQuoteEnum;
use App\Facades\Ken;
use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Jobs\OCB\SendTravelOCBIntroEmailJob;
use App\Models\ApplicationStorage;
use App\Models\Customer;
use App\Models\CustomerMembers;
use App\Models\InsuranceProvider;
use App\Models\Payment;
use App\Models\QuoteBatches;
use App\Models\TravelDestination;
use App\Models\TravelMemberDetail;
use App\Models\TravelPlan;
use App\Models\TravelQuote;
use App\Models\TravelQuotePlan;
use App\Models\TravelQuoteRequestDetail;
use App\Repositories\CustomerMembersRepository;
use App\Services\Logger\LoggerService;
use App\Traits\AddPremiumAllLobs;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteSyncTrait;
use App\Traits\RolePermissionConditions;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;

class TravelQuoteService extends BaseService
{
    protected $query;
    protected $leadAllocationService;

    use AddPremiumAllLobs;
    use GenericQueriesAllLobs;
    use PersonalQuoteSyncTrait;
    use RolePermissionConditions;

    public function __construct(LeadAllocationService $leadAllocationService, protected TravelQuoteQueryBuilder $travelQuoteQueryBuilder)
    {
        $this->leadAllocationService = $leadAllocationService;
        $this->query = TravelQuote::as('tqr')->select([
            'tqr.id',
            'tqr.uuid',
            DB::raw('DATE_FORMAT(tqr.created_at, "%d-%m-%Y %H:%i") as created_at'),
            DB::raw('DATE_FORMAT(tqr.updated_at, "%d-%m-%Y %H:%i") as updated_at'),
            'tqr.code',
            'tqr.days_cover_for',
            'tqr.details',
            'tqr.destination',
            'tqr.travel_cover_for_id',
            'tcf.TEXT AS travel_cover_for_id_text',
            'tqr.previous_quote_id',
            'tqr.first_name',
            'tqr.last_name',
            // 'tqr.email',
            // 'tqr.mobile_no',
            DB::raw('DATE_FORMAT(tqr.dob, "%d-%m-%Y") as dob'),
            'tqr.premium',
            'tqr.paid_at',
            'tqr.payment_paid_at',
            'tqr.source',
            'tqr.policy_number',
            'tqr.nationality_id',
            'n.TEXT AS nationality_id_text',
            'qs.id as quote_status_id',
            'qs.text as quote_status_id_text',
            'u.id as advisor_id',
            DB::raw("
                CASE
                    WHEN u.email = '".PolicyIssuanceEnum::API_POLICY_ISSUANCE_AUTOMATION_USER_EMAIL."' THEN 'Auto Issued'
                    ELSE u.name
                END as advisor_id_text
            "),
            'tqr.previous_advisor_id',
            DB::raw("
                CASE
                    WHEN uadv.email = '".PolicyIssuanceEnum::API_POLICY_ISSUANCE_AUTOMATION_USER_EMAIL."' THEN 'Auto Issued'
                    ELSE uadv.name
                END as previous_advisor_id_text
            "),
            'tqr.payment_status_id',
            'ps.text AS payment_status_id_text',
            'tqr.plan_id',
            'tp.text AS plan_id_text',
            // 'tp.id as plan_new_id_text',
            'tpip.text AS travel_plan_provider_text',
            'tpip.code AS plan_provider_code',
            'tqr.region_cover_for_id',
            'r.TEXT AS region_cover_for_id_text',
            DB::raw('DATE_FORMAT(tqrd.next_followup_date, "%d-%m-%Y %H:%i:%s") as next_followup_date'),
            'lu.text as transaction_type_text',
            'tqrd.transapp_code',
            'tqrd.insly_id',
            'ls.text as lost_reason',
            'tqrd.notes',
            'tqrd.advisor_assigned_date',
            'tqr.currently_located_in_id',
            'cli.text as currently_located_in_id_text',
            'tqr.destination_id',
            'nationality.country_name as destination_id_text',
            'tqr.is_ecommerce',
            'tqr.renewal_batch',
            'rb.name as renewal_batch_text',
            'tqr.renewal_import_code',
            'tqr.previous_quote_policy_number',
            DB::raw('DATE_FORMAT(tqr.previous_policy_expiry_date, "%d-%m-%Y") as previous_policy_expiry_date'),
            DB::raw('DATE_FORMAT(tqr.previous_policy_start_date, "%d-%m-%Y") as previous_policy_start_date'),
            DB::raw('DATE_FORMAT(tqr.policy_expiry_date, "%d-%m-%Y") as policy_expiry_date'),
            'tqr.device',
            'tqr.previous_quote_policy_premium',
            'tqr.policy_issuance_date',
            'tqr.policy_start_date',
            'tqr.customer_id',
            'tqr.parent_duplicate_quote_id',
            'tqr.has_arrived_destination',
            'tqr.has_arrived_uae',
            'tqr.start_date',
            'tqr.end_date',
            'tqr.insurer_api_status_id',
            'tqr.api_issuance_status_id',
            'tqr.start_date',
            'tqr.end_date',
            'tqr.direction_code',
            'tqr.coverage_code',
            'tqr.primary_member_id',
            'tqr.risk_score',
            'tqr.kyc_decision',
            'tqr.is_documents_valid',
            // 'tqr.prefill_plan_id',
            DB::raw('COALESCE(insured.customer_type, "'.CustomerTypeEnum::Individual.'") as customer_type'),
            'insured.first_name as insured_first_name',
            'insured.last_name as insured_last_name',
            'insured_kyc.id as insured_kyc_id',
            DB::raw('IF(insured.id_type = "emiratesId", insured.id_number, "") as emirates_id_number'),
            'insured.id_type as insured_id_type',
            'insured.id_number as insured_id_number',
            'c.emirates_id_expiry_date',
            'c.receive_marketing_updates',
            'qrem.entity_id',
            'ent.code as entity_code',
            'ent.trade_license_no',
            'ent.company_name',
            'ent.company_address',
            'qrem.entity_type_code',
            'ent.industry_type_code',
            'ent.emirate_of_registration_id',
            'et.passport_number',
            DB::raw('DATE_FORMAT(tqr.transaction_approved_at, "%d-%m-%Y %H:%i:%s") as transaction_approved_at'),
            'tqr.price_vat_not_applicable',
            'tqr.price_vat_applicable',
            'tqr.price_with_vat',
            'tqr.vat',
            'tqr.insurer_quote_number',
            'tqr.policy_issuance_status_id',
            'tqr.policy_issuance_status_other',
            DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
            'tqr.policy_booking_date',
            'tqr.insly_migrated',
            'tqr.sic_advisor_requested',
            'tqr.aml_status',
            'tqr.insurer_aml_status',
            'tqr.departure_country_id',
            'tqr.insurance_provider_id',
            'tqr.gender',
            DB::raw('
                CASE
                    WHEN tqr.insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningPending.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningPending).'"
                    WHEN tqr.insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningCleared.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningCleared).'"
                    WHEN tqr.insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningFailed.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningFailed).'"
                    WHEN tqr.insurer_aml_status IS NULL THEN "'.AMLStatusCode::InsurerAMLScreeningNA.'"
                    ELSE tqr.insurer_aml_status
                END AS insurer_aml_status_display
            '),
            'c.pcp_tag',
            'tqr.pc_qualified',
            DB::raw(Customer::formattedPcpTagCase().' as pcp_tag_formatted'),
            DB::raw(TravelQuote::formattedPcQualifiedCase('tqr').' as pc_qualified_formatted'),
            // Sub-source fields
            'tqr.sub_source_id',
            'tqr.sub_source_options_id',
            'tqr.additional_notes',
            'ss.text as sub_source_text',
            'ss.description as sub_source_description',
            'sso.text as sub_source_option_text',
            'sso.description as sub_source_option_description',
            'ub.branch_id as advisor_primary_branch_id',
            'b.name as lead_branch_name',
            'b.id as lead_branch_id',
            'tqr.is_branch_applicable',
        ])
            ->leftJoin('payments as py', 'py.code', '=', 'tqr.code')
            ->leftJoin('travel_cover_for as tcf', 'tcf.id', '=', 'tqr.travel_cover_for_id')
            ->leftJoin('travel_quote_request_detail as tqrd', 'tqr.id', '=', 'tqrd.travel_quote_request_id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'tqrd.lost_reason_id')
            ->leftJoin('lookups as lu', 'lu.id', '=', 'tqr.transaction_type_id')
            ->leftJoin('nationality as n', 'n.id', '=', 'tqr.nationality_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'tqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'tqr.advisor_id')
            ->leftJoin('users as uadv', 'uadv.id', '=', 'tqr.previous_advisor_id')
            ->leftJoin('region as r', 'r.id', '=', 'tqr.region_cover_for_id')
            ->leftJoin('currently_located_in as cli', 'cli.id', '=', 'tqr.currently_located_in_id')
            ->leftJoin('nationality', 'nationality.id', '=', 'tqr.destination_id')
            ->leftJoin('travel_plan as tp', 'tp.id', '=', 'tqr.plan_id')
            ->leftJoin('insurance_provider as tpip', 'tpip.id', '=', 'tp.provider_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'py.payment_status_id')
            ->leftJoin('customer as c', 'tqr.customer_id', 'c.id')
            ->leftJoin('renewal_batches as rb', 'tqr.renewal_batch_id', '=', 'rb.id')
            ->leftJoin('embedded_transactions as et', 'et.code', 'tqr.code')
            ->leftJoin('quote_request_entity_mapping as qrem', function ($entityMappingJoin) {
                $entityMappingJoin->on('qrem.quote_type_id', '=', DB::raw(QuoteTypeId::Travel));
                $entityMappingJoin->on('qrem.quote_request_id', '=', 'tqr.id');
            })
            ->leftJoin('customer_insured as ic', function ($insuredCustomerMapping) {
                $insuredCustomerMapping->on('ic.quote_type_id', '=', DB::raw(QuoteTypeId::Travel));
                $insuredCustomerMapping->on('ic.quote_request_id', '=', 'tqr.id');
                $insuredCustomerMapping->where('ic.is_active', '=', true);
            })
            ->leftJoin('insured', 'ic.insured_id', '=', 'insured.id')
            ->leftJoin('insured_kyc', 'insured.id', '=', 'insured_kyc.insured_id')
            ->leftJoin('entities as ent', 'qrem.entity_id', '=', 'ent.id')
            ->leftJoin('lookups as ss', 'ss.id', '=', 'tqr.sub_source_id')
            ->leftJoin('lookups as sso', 'sso.id', '=', 'tqr.sub_source_options_id')
            ->leftJoin('user_branches as ub', function ($join) {
                $join->on('ub.user_id', '=', 'tqr.advisor_id')
                    ->where('ub.is_primary', '=', 1)
                    ->where('ub.status', '=', 1);
            })
            ->leftJoin('branches as b', 'b.id', '=', 'tqr.branch_id');
    }

    private function applyUtmJoin(): void
    {
        if (auth()->user()?->can(PermissionsEnum::VIEW_UTM_SECTION)) {
            $this->query
                ->leftJoin('personal_quotes as pq', function ($join) {
                    $join->on('pq.uuid', '=', 'tqr.uuid')
                        ->where('pq.quote_type_id', '=', QuoteTypeId::Travel);
                })
                ->leftJoin('personal_quote_details as pqd', 'pqd.personal_quote_id', '=', 'pq.id')
                ->addSelect(
                    'pqd.utm_source',
                    'pqd.utm_medium',
                    'pqd.utm_campaign',
                    'pqd.utm_content',
                    'pqd.utm_term',
                );
        }
    }

    public function getCustomerTravelInfo(int $quoteRequestId, string $quoteType)
    {
        $model = $this->getModelObject($quoteType);

        if (! class_exists($model)) {
            return false;
        }

        $customerTravelInfo = DB::table('travel_quote_request as tqr')
            ->leftJoin('customer_members as cm', function ($join) use ($model) {
                $join->on('cm.quote_id', '=', 'tqr.id')
                    ->where('cm.quote_type', '=', ltrim($model, '\\'));
            })
            ->select('tqr.id', 'tqr.code', 'tqr.customer_id', 'cm.gender', 'cm.first_name', 'cm.last_name', 'cm.dob', 'cm.nationality_id', 'cm.passport')
            ->where('tqr.id', $quoteRequestId)
            ->first();

        return $customerTravelInfo;
    }

    public function checkCustomerTravelInfoIsComplete(array $travelQuoteRequest): array
    {
        $message = '';
        $requiredProperty = collect(['first_name', 'dob', 'nationality_id']);

        $missingDetails = [];
        foreach ($requiredProperty as $value) {

            if (empty($travelQuoteRequest[$value])) {
                $propertyName = match ($value) {
                    'dob' => 'date of birth',
                    'nationality_id' => 'nationality',
                    default => str_replace(['-', '_'], ' ', $value)
                };
                array_push($missingDetails, ucwords($propertyName));
            }
        }

        if (($travelQuoteRequest['id_type'] ?? null) === 'passport' && empty($travelQuoteRequest['id_number'])) {
            $missingDetails[] = 'Passport';
        }

        $missingDetailCount = count($missingDetails);
        if ($missingDetailCount) {
            $message = 'Missing Info: '.implode(', ', $missingDetails);
        }

        return ['status' => $missingDetailCount ? false : true, 'message' => $message];
    }

    public function saveTravelQuote(Request $request)
    {
        $members = [];
        $travelQuote = [
            'directionCode' => $request->direction_code,
            'regionCoverForId' => 3,
            'firstName' => $request->first_name,
            'lastName' => $request->last_name,
            'email' => $request->email,
            'mobileNo' => $request->mobile_no,
            'nationalityId' => $request->nationality_id,
            'destinationIds' => $request->destination_ids ?? [],
            'tripStarted' => ($request->has_arrived_uae == '1' || $request->has_arrived_destination == '1') ? 1 : 0,
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => config('constants.APP_URL'),
            'departureCountryId' => $request->departure_country_id ?? null,
            // Sub-source fields from CreateLeadModal
            'subSourceId' => $request->sub_source_id ?? null,
            'subSourceOptionsId' => $request->sub_source_options_id ?? null,
            'additionalNotes' => $request->additional_notes ?? null,
        ];

        LoggerService::info(self::class.' - saveTravelQuote', ['data' => $travelQuote]);

        // Log lead source parameters for Travel quotes
        LoggerService::info('Travel saveTravelQuote - Lead source parameters:', [
            'type' => $request->input('type'),
            'subSourceId' => $request->sub_source_id,
            'subSourceOptionsId' => $request->sub_source_options_id,
            'additionalNotes' => $request->additional_notes,
        ]);
        if ($request->has_arrived_destination == '0' || $request->has_arrived_uae == '0') {

            foreach ($request->members as $member) {
                $memberDob = Carbon::parse($member['dob'])->format('Y-m-d');
                $memberData = new \stdClass;
                if (isset($member['primary'])) {
                    $memberData->primary = true;
                    $travelQuote['dob'] = $memberDob;
                } else {
                    $memberData->primary = false;
                }
                if (isset($member['id'])) {
                    $memberData->id = $member['id'];
                }
                $memberData->dob = $memberDob;
                $memberData->gender = $member['gender'];
                $memberData->uaeResident = (bool) (isset($member['uae_resident']) && $member['uae_resident'] == '1') ? true : false;
                $memberData->nationalityId = $request->nationality_id;
                array_push($members, $memberData);
            }
            $travelQuote['members'] = $members;
            $travelQuote['coverageCode'] = $request->coverage_code;
            $travelQuote['startDate'] = $request->start_date;

            if ($request->coverage_code == TravelQuoteEnum::COVERAGE_CODE_SINGLE_TRIP) {
                $travelQuote['endDate'] = $request->end_date;
            }
        }

        if ($request->direction_code == TravelQuoteEnum::TRAVEL_UAE_INBOUND) {
            $travelQuote['hasArrivedUae'] = $request->has_arrived_uae;
        } else {
            $travelQuote['hasArrivedDestination'] = $request->has_arrived_destination;
            if ($request->has_arrived_destination == '0') {
                if ($request->has('region_cover_for_id')) {
                    $travelQuote['regionCoverForId'] = (int) $request->region_cover_for_id;
                }
            }
        }

        if (! auth()->user()->hasRole(RolesEnum::Admin) && ! auth()->user()->hasRole(RolesEnum::CallDesk)) {
            $travelQuote['advisorId'] = auth()->user()->id;
        }

        if ($request->uuid != null) {
            $travelQuote['quoteUID'] = $request->uuid;
            $travelQuoteData = TravelQuote::where('uuid', $travelQuote['quoteUID'])->first();
            $selectedColumns = ['id', 'gender', 'dob'];
            $travelersMembers = CustomerMembersRepository::getMemberInfo('quote_id', $travelQuoteData->id, QuoteTypes::TRAVEL->name, CustomerTypeEnum::Individual, $selectedColumns);
            $travelQuote['members'] = $travelersMembers;
            $this->setQuoteUpdatedAt($travelQuoteData->id);
            $response = Ken::request('/get-revised-travel-quote-plans', 'post', $travelQuote);

            return $response;
        }
        // LoggerService::info(self::class.' - saveTravelQuote: Going to Create Travel Quote on CAPI...');
        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-travel-quote', $travelQuote);
        // LoggerService::info(self::class.' - saveTravelQuote: Capi Request Completed', ['response' => $response]);

        if (isset($response->quoteUID)) {
            $this->savePremium(quoteTypeCode::TravelQuote, $request, $response);

            $this->selfAssign(QuoteTypes::TRAVEL, $response->quoteUID);

            if ($travelQuote['source'] != LeadSourceEnum::IMCRM) {
                SendTravelOCBIntroEmailJob::dispatch($response->quoteUID);
            } else {
                LoggerService::info(self::class.'Lead source is IMCRM so skipping SendTravelOCBIntroEmailJob');
            }
            LoggerService::info(self::class." lead source is renewal upload so about to dispatch SendOCBTravelRenewalIntroEmailJob Ref-ID: {$response->quoteUID} | Time:  ".now());

            $customerId = app(CustomerService::class)->getCustomerIdByEmail($request->email);
            if ($request->has('addressObj') && ! empty(array_filter((array) $request->input('addressObj')))) {
                app(CustomerAddressService::class)->createOrUpdateCustomerAddress($request->input('addressObj'), $customerId, $response->quoteUID);
            }
        }

        return $response;
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query = DB::table('travel_quote_request as tqr')
            ->select(
                'tqr.id',
                'tqr.uuid',
                'tqr.first_name',
                'tqr.last_name',
                'tqr.code',
                'tqr.created_at',
                'u.name AS advisor_name',
                DB::raw("'Travel' as lead_type"),
                'u.id as advisor_id',
                'qs.text as lead_status'
            )
            ->leftJoin('users as u', 'u.id', '=', 'tqr.advisor_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'tqr.quote_status_id')
            ->orderBy('advisor_id', 'ASC');
        if (! empty($CDBID)) {
            $query->where('tqr.id', '=', $CDBID);
        }
        if (! empty($email)) {
            $query->where('tqr.email', '=', $email);
        }
        if (! empty($mobile_no)) {
            $query->where('tqr.mobile_no', '=', $mobile_no);
        }

        return $query;
    }

    public function getLeadsForAssignment()
    {
        return TravelQuote::orderBy('created_at', 'desc')->get();
    }

    public function getGridData($model = null, $requestParams = [])
    {
        $query = $this->travelQuoteQueryBuilder->processGridData($requestParams);
        $this->whereBasedOnRole($query, 'travel_quote_request', null, user: $requestParams['user'] ?? null);
        $this->adjustQueryByDateFilters($query, 'travel_quote_request', requestParams: $requestParams, useJoin: false);

        return $query;

    }

    public function postProcessTravelQuotes($quotes)
    {
        return $quotes->map(function ($quote) {
            $quote->branch_name = ! $quote->is_branch_applicable ? 'N/A' : ($quote?->branch?->name ?? app(BranchAssignmentService::class)->getBranchName($quote->advisor?->primaryBranch?->branch_id, QuoteTypeId::Travel));

            return $quote;
        });
    }

    private function parseDate($date, $isStartOfDay)
    {
        if ($date != '') {
            $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
            if ($isStartOfDay) {
                return Carbon::createFromFormat($dateFormat, $date)->startOfDay()->toDateString();
            } else {
                return Carbon::createFromFormat($dateFormat, $date)->endOfDay()->toDateString();
            }
        }
    }

    private function getQuerySuffix($item)
    {
        switch ($item) {
            case 'travel_cover_for':
                return 'tcf';
                break;
            case 'region':
                return 'r';
                break;
            case 'advisor':
                return 'u';
                break;
            case 'quote_status':
                return 'qs';
                break;
            case 'nationality':
                return 'n';
                break;
            default:
                return 'tqr';
                break;
        }
    }

    public function getEntity($id)
    {
        $this->applyUtmJoin();

        return $this->query->addSelect(['tqr.email', 'tqr.mobile_no'])->where('tqr.uuid', $id)->first();
    }

    public function getEntityPlain($id)
    {
        return TravelQuote::where('id', $id)->with([
            'child',
            'parent',
            'plan',
            'payments' => function ($payment) {
                $payment->with([
                    'paymentSplits' => function ($paymentSplit) {
                        $paymentSplit->with([
                            'paymentStatus',
                            'paymentMethod',
                            'documents',
                            'verifiedByUser',
                            'processJob',
                            'paymentCharges',
                        ]);
                        $paymentSplit->orderBy('sr_no');
                    },
                ]);
                $payment->orderBy('created_at');
            },
        ])->first();
    }

    public function getSelectedLostReason($id)
    {
        $entity = TravelQuoteRequestDetail::where('travel_quote_request_id', $id)->first();
        $lostId = 0;
        if (! is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }

        return $lostId;
    }

    public function getDetailEntity($id)
    {
        return TravelQuoteRequestDetail::firstOrCreate(['travel_quote_request_id' => $id]);
    }

    public function updateTravelQuote(Request $request, $id)
    {
        $travelQuote = TravelQuote::where('uuid', $id)->first();
        $travelQuote->first_name = $request->first_name;
        $travelQuote->last_name = $request->last_name;
        $travelQuote->nationality_id = $request->nationality_id;
        $travelQuote->premium = $request->premium;
        $fetchLatestRating = (
            $travelQuote->direction_code != $request->direction_code ||
            $travelQuote->coverage_code != $request->coverage_code ||
            $travelQuote->destination_id != $request->destination_id ||
            $travelQuote->region_cover_for_id != $request->region_cover_for_id ||
            $travelQuote->start_date != $request->start_date
        );

        if (
            $travelQuote->days_cover_for != $request->days_cover_for ||
            $travelQuote->destination_id != $request->destination_id ||
            $travelQuote->currently_located_in_id != $request->currently_located_in_id ||
            $travelQuote->travel_cover_for_id != $request->travel_cover_for_id ||
            $travelQuote->region_cover_for_id != $request->region_cover_for_id ||
            $travelQuote->departure_country_id != $request->departure_country_id ||
            $travelQuote->start_date != $request->start_date ||
            $travelQuote->end_date != $request->end_date ||
            $travelQuote->direction_code != $request->direction_code
        ) {
            $travelQuote->quote_updated_at = Carbon::now();
        }
        $travelQuote->days_cover_for = $request->days_cover_for;

        // Handle multiple destinations using the existing TravelDestinations relation
        if (is_array($request->destination_ids) && ! empty($request->destination_ids)) {
            // Delete existing destinations first
            $travelQuote->TravelDestinations()->delete();

            // Create new destination records
            foreach ($request->destination_ids as $destinationId) {
                $travelQuote->TravelDestinations()->create([
                    'uuid' => $travelQuote->uuid,
                    'destination_id' => $destinationId,
                ]);
            }

            // Set the primary destination_id to the first one in the array
            $travelQuote->destination_id = $request->destination_ids[0];
        }

        $travelQuote->currently_located_in_id = $request->currently_located_in_id;
        $travelQuote->travel_cover_for_id = $request->travel_cover_for_id;
        (isset($request->region_cover_for_id) && $request->region_cover_for_id != 'undefined') && $travelQuote->region_cover_for_id = $request->region_cover_for_id;
        $travelQuote->policy_start_date = $request->policy_start_date;
        $travelQuote->start_date = $request->start_date ?? null;
        $travelQuote->end_date = $request->end_date ?? null;
        $travelQuote->direction_code = $request->direction_code ?? null;
        $travelQuote->coverage_code = $request->coverage_code ?? null;
        $travelQuote->departure_country_id = $request->departure_country_id ?? null;

        $travelQuote->details = $request->details;

        // Update lead source fields from CreateLeadModal
        if ($request->has('sub_source_id')) {
            $travelQuote->sub_source_id = $request->sub_source_id;
        }
        if ($request->has('sub_source_options_id')) {
            $travelQuote->sub_source_options_id = $request->sub_source_options_id;
        }
        if ($request->has('additional_notes')) {
            $travelQuote->additional_notes = $request->additional_notes;
        }

        $travelQuote->save();

        if ($fetchLatestRating) {
            LoggerService::info('Fields changed, Fetching quote plans with latest rating', extra: [
                'getLatestRating' => true,
                'uuid' => $travelQuote->uuid,
            ]);
            $this->getQuotePlans($id, ['getLatestRating' => true]);
        }

        $customerId = app(CustomerService::class)->getCustomerIdByEmail($travelQuote->email);
        if (($request->has('addressObj') && ! empty(array_filter((array) $request->input('addressObj'))))) {
            app(CustomerAddressService::class)->sendAddressNotificationToCustomer($travelQuote, $request->input('addressObj'), QuoteTypeId::Travel);
            app(CustomerAddressService::class)->createOrUpdateCustomerAddress($request->input('addressObj'), $customerId, $travelQuote->uuid);
            SyncCourierQuoteWithMacrm::dispatch($travelQuote, QuoteTypeId::Travel);
        }

        if (! empty($request->destination_ids)) {
            $travelQuote->load('travelDestinations');

            $existingIds = $travelQuote->travelDestinations->pluck('destination_id')->toArray();
            $newIds = $request->destination_ids;

            // Determine which IDs need to be inserted and which to delete
            $idsToInsert = array_diff($newIds, $existingIds);
            $idsToDelete = array_diff($existingIds, $newIds);

            // Delete outdated destinations
            if (! empty($idsToDelete)) {
                TravelDestination::where([
                    'uuid' => $id,
                    'quote_id' => $travelQuote->id,
                    'customer_id' => $travelQuote->customer_id,
                ])->whereIn('destination_id', $idsToDelete)->delete();
            }

            // Insert new destinations
            if (! empty($idsToInsert)) {
                $destinationData = array_map(fn ($destinationId) => [
                    'uuid' => $id,
                    'quote_id' => $travelQuote->id,
                    'customer_id' => $travelQuote->customer_id,
                    'destination_id' => $destinationId,
                ], $idsToInsert);

                TravelDestination::insert($destinationData);
            }
        }

        if (isset($request->return_to_view)) {
            return redirect('quote/travel/'.$id)->with('success', 'Travel Quote has been updated');
        }
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'code' => 'input|title',
            'customer_type' => 'input|title',
            'first_name' => 'input|text|required',
            'last_name' => 'input|text|required',
            'email' => 'input|email|required',
            'mobile_no' => 'input|title|number|required',
            'quote_status_id' => 'select|title|multiple',
            'advisor_id' => 'select|title|multiple',
            'created_at' => 'input|date|title|range',
            'updated_at' => 'input|date|title',
            'dob' => 'input|date|title',
            'next_followup_date' => 'input|date|title|range',
            'transapp_code' => 'readonly|none',
            'lost_reason' => 'input|text',
            'source' => 'input|text',
            'premium' => 'input|number|title',
            'policy_number' => 'input|text',
            'nationality_id' => 'select|title|required',
            'destination_id' => 'select' | 'title',
            'previous_quote_id' => 'readonly|title',
            'policy_expiry_date' => 'input|date|title|range',
            'is_renewal' => '|static|Yes,No',
            'is_ecommerce' => '|static|title|Yes,No',
            'renewal_batch' => 'input|number|title',
            'payment_status_id' => 'select|title',
            'previous_quote_policy_number' => 'input|title',
            'renewal_import_code' => 'input|text',
            'previous_policy_expiry_date' => 'input|date|title|range',
            'renewal_batch' => 'input|none',
            'previous_quote_policy_premium' => 'input|title',
            'parent_duplicate_quote_id' => 'input|title',
            'policy_start_date' => 'input|date',
            'direction_code' => 'input|text|required',
            'departure_country_id' => 'select|title|required_if:direction_code,'.TravelQuoteEnum::TRAVEL_UAE_INBOUND,
            'region_cover_for_id' => 'select|title|required',
        ];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'days_cover_for':
                $title = 'How many days would you like cover for?';
                break;
            case 'advisor_id':
                $title = 'Advisor';
                break;
            case 'quote_status_id':
                $title = 'Lead Status';
                break;
            case 'code':
                $title = 'Ref-ID';
                break;
            case 'created_at':
                $title = 'Created Date';
                break;
            case 'updated_at':
                $title = 'Last Modified Date';
                break;
            case 'dob':
                $title = 'Date of Birth';
                break;
            case 'region_cover_for_id':
                $title = 'Which regions do you need cover for?';
                break;
            case 'nationality_id':
                $title = 'Nationality';
                break;
            case 'next_followup_date':
                $title = 'Next Followup Date';
                break;
            case 'currently_located_in_id':
                $title = 'Currently Located In ';
                break;
            case 'travel_cover_for_id':
                $title = 'Who would you like cover for?';
                break;
            case 'destination_id':
                $title = 'Destination';
                break;
            case 'mobile_no':
                $title = 'Mobile Number';
                break;
            case 'previous_quote_id':
                $title = 'Previous Quote Id';
                break;
            case 'is_ecommerce':
                $title = 'Ecommerce';
                break;
            case 'policy_expiry_date':
                $title = 'Expiry Date';
                break;
            case 'renewal_batch':
                $title = 'Renewal Batch #';
                break;
            case 'payment_status_id':
                $title = 'Payment Status';
                break;
            case 'previous_quote_policy_number':
                $title = 'Previous Policy Number';
                break;
            case 'previous_policy_expiry_date':
                $title = 'Previous Policy Expiry Date';
                break;
            case 'previous_quote_policy_premium':
                $title = 'Previous Policy Price';
                break;
            case 'premium':
                $title = 'Price';
                break;
            case 'parent_duplicate_quote_id':
                $title = 'PARENT REF-ID';
                break;
            case 'customer_type':
                $title = 'Customer Type';
                break;
            default:
                break;
        }

        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            'create' => 'policy_start_date,previous_quote_policy_premium,parent_duplicate_quote_id,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,is_ecommerce,is_renewal,previous_quote_id,id,created_at,id,code,advisor_id,updated_at,quote_status_id,next_followup_date,lost_reason,source,transapp_code,renewal_batch,payment_status_id,renewal_import_code,policy_expiry_date,policy_number',
            'list' => 'policy_start_date,previous_quote_policy_premium,parent_duplicate_quote_id,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,email,mobile_no,travel_cover_for_id,details,nationality_id,next_followup_date,days_cover_for,renewal_batch,renewal_import_code',
            'update' => 'previous_quote_policy_premium,parent_duplicate_quote_id,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,is_ecommerce,is_renewal,previous_quote_id,created_at,id,code,advisor_id,updated_at,quote_status_id,next_followup_date,lost_reason,source,transapp_code,renewal_batch,payment_status_id,renewal_import_code,policy_expiry_date,policy_number',
            'show' => 'is_renewal,policy_number,policy_expiry_date,premium,source,previous_quote_id,payment_status_id,quote_status_id',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'created_at', 'is_ecommerce', 'payment_status_id', 'advisor_id'];
    }

    public function fillRenewalProperties($model)
    {
        $model->renewalSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'previous_quote_policy_number', 'payment_status_id', 'previous_policy_expiry_date', 'renewal_batch', 'previous_quote_policy_premium'];
        $model->renewalSkipProperties = [
            'create' => 'policy_start_date,premium,previous_quote_policy_premium,parent_duplicate_quote_id,policy_expiry_date,policy_number,previous_policy_expiry_date,previous_quote_policy_number,is_ecommerce,is_renewal,previous_quote_id,id,created_at,id,code,advisor_id,updated_at,quote_status_id,next_followup_date,lost_reason,source,transapp_code,renewal_batch,payment_status_id,renewal_import_code',
            'list' => 'policy_start_date,premium,parent_duplicate_quote_id,policy_expiry_date,policy_number,is_ecommerce,is_renewal,dob,email,mobile_no,region_cover_for_id,travel_cover_for_id,details,nationality_id,days_cover_for,next_followup_date,lost_reason,source,transapp_code,currently_located_in_id,destination_id,renewal_import_code,previous_quote_id',
            'update' => 'premium,previous_quote_policy_premium,parent_duplicate_quote_id,policy_expiry_date,policy_number,previous_policy_expiry_date,previous_quote_policy_number,is_ecommerce,is_renewal,previous_quote_id,created_at,id,code,advisor_id,updated_at,quote_status_id,next_followup_date,lost_reason,source,transapp_code,renewal_batch,payment_status_id,renewal_import_code',
            'show' => 'source,premium,policy_expiry_date,policy_number,is_ecommerce,is_renewal,previous_quote_id,payment_status_id,quote_status_id',
        ];
    }

    public function fillNewBusinessProperties($model)
    {
        $model->newBusinessSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'policy_number', 'premium'];
        $model->newBusinessSkipProperties = [
            'create' => 'policy_start_date,previous_quote_policy_premium,parent_duplicate_quote_id,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date',
            'list' => 'policy_start_date,previous_quote_policy_premium,parent_duplicate_quote_id,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id,is_ebp_renewal,health_team_type,next_followup_date,lost_reason,source,transapp_code,lead_type_id,policy_expiry_date,previous_quote_id',
            'update' => 'previous_quote_policy_premium,parent_duplicate_quote_id,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date',
            'show' => 'source,member_category_id,salary_band_id,gender,is_renewal,id,next_followup_date,previous_quote_id,payment_status_id,quote_status_id,premium,policy_number',
        ];
    }

    public function getQuotePlans($id, $extraData = [])
    {
        $quoteUuId = TravelQuote::where('uuid', '=', $id)->value('uuid');
        $plansApiEndPoint = config('constants.KEN_API_ENDPOINT').'/get-travel-quote-plans';
        $plansApiToken = config('constants.KEN_API_TOKEN');
        $plansApiTimeout = config('constants.KEN_API_TIMEOUT');
        $plansApiUserName = config('constants.KEN_API_USER');
        $plansApiPassword = config('constants.KEN_API_PWD');
        $authBasic = base64_encode($plansApiUserName.':'.$plansApiPassword);

        $plansDataArr = [
            'quoteUID' => $quoteUuId,
            'lang' => 'en',
            ...$extraData,
        ];

        $client = new Client;

        try {
            $kenRequest = $client->post(
                $plansApiEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'x-api-token' => $plansApiToken,
                        'Authorization' => 'Basic '.$authBasic,
                    ],
                    'body' => json_encode($plansDataArr),
                    'timeout' => $plansApiTimeout,
                ]
            );

            $getStatusCode = $kenRequest->getStatusCode();

            if ($getStatusCode == 200) {
                $getContents = $kenRequest->getBody();
                $getdecodeContents = json_decode($getContents);

                return $getdecodeContents;

            }
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $contents = (string) $response->getBody();
            $response = json_decode($contents);

            if (isset($response->message)) {
                $responseBodyAsString = $response->message;
            } elseif (isset($response->error)) {
                $responseBodyAsString = $response->error;
            } elseif (isset($response->msg)) {
                $responseBodyAsString = $response->msg;
            } else {
                $responseBodyAsString = 'Quote unavailable for the selected location and region. Please call 800 ALFRED.';
            }

            return $responseBodyAsString;
        }
    }

    public function getMembersDetail($id)
    {
        return TravelMemberDetail::where('travel_quote_request_id', $id)->with('nationality', 'relation')->get();
    }

    public function getAboveAgeMembers($id)
    {
        return CustomerMembers::where('quote_id', $id)
            ->where('quote_type', TravelQuote::class)
            ->whereDate('dob', '<=', now()->subYears(GenericRequestEnum::TRAVEL_SENIOR_MEMBER_AGE))->count();
    }

    public function getDuplicateEntityByCode($code)
    {
        return TravelQuote::where('parent_duplicate_quote_id', $code)->first();
    }

    public function processManualLeadAssignment($request): array
    {
        if ($request->selectTmLeadId == '' || $request->selectTmLeadId == null) {
            $leadsIds = array_map('intval', explode(',', trim($request->entityId, ',')));
        } else {
            $leadsIds = array_map('intval', explode(',', trim($request->selectTmLeadId, ',')));
        }
        $userId = (int) $request->assigned_to_id_new;
        $quoteBatch = QuoteBatches::latest()->first();
        Log::info('Leads ids to assign: '.json_encode($leadsIds).' Quote Batch with ID: '.$quoteBatch->id.' and Name: '.$quoteBatch->name);
        $result = [];
        foreach ($leadsIds as $leadId) {
            $lead = $this->getEntityPlain($leadId);

            $this->handleAssignment($lead, $userId, $quoteBatch, QuoteTypes::TRAVEL, TravelQuoteRequestDetail::class, 'travel_quote_request_id');
        }

        return $result;
    }

    public function getEntityPlainByUUID($uuid)
    {
        return TravelQuote::where('uuid', $uuid)->first();
    }

    public function validateRequest($request)
    {
        $userId = $request->assigned_to_id_new;
        $leadsIds = $request->selectTmLeadId == null || $request->selectTmLeadId == '' ? $request->entityId : $request->selectTmLeadId;
        if ($leadsIds == '' || $leadsIds == null) {
            return 'Please select lead(s) to assign';
        }
        if (substr($leadsIds, 0, 1) == ',') {
            $leadsIds = substr($leadsIds, 1);
        }
        $leadsIds = array_map('intval', explode(',', $leadsIds));
        foreach ($leadsIds as $leadId) {
            $entity = $this->getEntityPlain($leadId);
            if ($entity->quote_status_id == QuoteStatusEnum::TransactionApproved && auth()->user()->cannot(PermissionsEnum::ASSIGN_PAID_LEADS)) {
                return 'One of the selected lead is in Transaction Approved state. Please unselect the lead and try again.';
            }
        }
        if ($userId == '' || $userId == null) {
            return 'Please select user to assign leads';
        }

        return 'true';
    }

    public function getQuoteStatuses()
    {
        return [QuoteStatusEnum::NewLead, QuoteStatusEnum::Quoted, QuoteStatusEnum::FollowedUp, QuoteStatusEnum::InNegotiation, QuoteStatusEnum::PaymentPending];
    }

    public function listQuotePlans($id)
    {
        $listQuotePlans = '';
        $quotePlans = $this->getQuotePlans($id);
        if (isset($quotePlans->message) && $quotePlans->message != '') {
            $listQuotePlans = $quotePlans->message;
        } else {
            if (gettype($quotePlans) != 'string') {
                $listQuotePlans = $quotePlans->quotes->plans;
            } else {
                $listQuotePlans = $quotePlans;
            }
        }

        return $listQuotePlans;
    }

    public function sortedPlansList($id): array
    {
        $result = [];
        $plans = $this->listQuotePlans($id);
        $collection = collect($plans);

        $seniorPlans = $collection->where('isSeniorPlan', true);
        $normalPlans = $collection->where('isSeniorPlan', false);

        $result['normalPlans'] = array_values($normalPlans->toArray());
        $result['seniorPlans'] = array_values($seniorPlans->toArray());

        return $result;
    }

    public function listTravelQuotePlans($id)
    {
        $travelQuotePlans = TravelQuotePlan::where('travel_quote_request_id', $id)->first();

        return $travelQuotePlans;
    }

    public function updateManualPlansBulk($request)
    {
        if ($request->planIds && isset($request->toggle) && isset($request->quote_uuid)) {
            $isDisabled = $request->toggle;
            $plansArray = [];
            foreach ($request->planIds as $planId) {
                $apiArray = [
                    'planId' => (int) $planId,
                    'isDisabled' => filter_var($isDisabled, FILTER_VALIDATE_BOOLEAN),
                ];
                array_push($plansArray, $apiArray);
            }

            $dataArray = [
                'quoteUID' => $request->quote_uuid,
                'plans' => $plansArray,
            ];
            $response = Ken::request('/save-manual-travel-quote-plan', 'post', $dataArray);

            return $response;
        }
    }

    public function exportPlansPdf($quoteType, $data, $quotePlans = null)
    {

        $planIds = $data['plan_ids'];
        $addons = (isset($data['addons'])) ? $data['addons'] : null;

        $selectedPlanIds = isset($data['selectedPlanIds']) ? $data['selectedPlanIds'] : [];
        $hasAdultAndSeniorMember = isset($data['hasAdultAndSeniorMember']) ? $data['hasAdultAndSeniorMember'] : false;
        $quotePlans = $this->getQuotePlans($data['quote_uuid']);
        if (! isset($quotePlans->quotes->plans)) {
            return ['error' => 'Quote plans not available'];
        }

        $providerIds = collect($quotePlans->quotes->plans)->pluck('insuranceProviderId')->toArray();
        $providers = InsuranceProvider::whereIn('id', $providerIds)->get()->keyBy('id')->toArray();

        $quote = $this->getQuoteObject($quoteType, $data['quote_uuid']);
        $quote->load(['advisor' => function ($q) {
            $q->select('id', 'email', 'mobile_no', 'name', 'landline_no', 'profile_photo_path');
        }, 'customer']);
        $pdf = PDF::setOption(['isHtml5ParserEnabled' => true, 'dpi' => 150, 'isRemoteEnabled' => true])
            ->loadView('pdf.travel_quote_plans', compact('quotePlans', 'planIds', 'quote', 'addons', 'providers', 'selectedPlanIds', 'hasAdultAndSeniorMember'));

        // generate pdf with file name e.g. InsuranceMarket.ae™ Motor Insurance Comparison for Rahul.pdf
        $pdfName = 'InsuranceMarket.ae™ Travel Insurance Comparison for '.$quote->first_name.' '.$quote->last_name.'.pdf';

        return ['pdf' => $pdf, 'name' => $pdfName];
    }

    public function setQuoteUpdatedAt($id)
    {
        $travelQuote = TravelQuote::find($id);
        $travelQuote->quote_updated_at = Carbon::now();
        $travelQuote->save();
    }

    public function createDuplicateLead($leadModal, $quoteStatusId)
    {
        if (! $leadModal) {
            LoggerService::warning('Cannot create duplicate lead: Lead model is null');

            return false; // Add validation to avoid failure if $leadModal is null
        }

        $newLeadCode = $leadModal->code.'-1';
        LoggerService::info("Starting duplicate lead creation process for {$leadModal->code} -> {$newLeadCode}");
        $leadExists = TravelQuote::where('code', $newLeadCode)->exists();
        if ($leadExists) {
            // Lead with the code already exists
            LoggerService::warning("Duplicate lead creation failed: Lead with code {$newLeadCode} already exists");

            return false;
        }
        $duplicateLead = $leadModal->replicate();
        $duplicateLead->parent_id = $leadModal->id;
        $duplicateLead->uuid = $leadModal->uuid.'-1';
        $duplicateLead->code = $newLeadCode;
        $duplicateLead->source = TravelQuoteEnum::IMCRM_BOOKING;
        $duplicateLead->quote_status_id = $quoteStatusId;
        $duplicateLead->region_cover_for_id = $leadModal->region_cover_for_id;
        $duplicateLead->insurer_quote_number = null;
        $duplicateLead->save();

        if ($duplicateLead) {
            LoggerService::info("Successfully created duplicate lead with code: {$duplicateLead->code}");
            $this->updatePersonalQuote($duplicateLead->uuid, QuoteTypeId::Travel, ['quote_id' => $duplicateLead->id]);
            // update morph relation in payments table
            $paymentsCount = $leadModal->payments()->where('code', $newLeadCode)->count();
            if ($paymentsCount > 0) {
                LoggerService::info("Updating payment relations: {$paymentsCount} payment(s) found for {$newLeadCode}");
                $leadModal->payments()->where('code', $newLeadCode)->update(['paymentable_id' => $duplicateLead->id]);
            }

            // update morph relation in quote_documents table,which are associated with split payments
            Payment::where('code', $newLeadCode)->with('paymentSplits')->get()->each(function ($payment) use ($duplicateLead) {
                $payment->paymentSplits->each(function ($split) use ($duplicateLead) {
                    $split->documents()->update(['quote_documentable_id' => $duplicateLead->id]);
                });
            });

            // Update the above 65 age member
            $aboveAgeMemberCount = $this->getAboveAgeMembers($leadModal->id);
            LoggerService::info("Above age member count for lead code {$leadModal->code}: {$aboveAgeMemberCount}");
            if ($aboveAgeMemberCount > 0) {
                LoggerService::info("Updating above age members for lead code {$leadModal->code} to duplicate lead code {$duplicateLead->code}");
                $this->updateAboveAgeMember($leadModal->id, $duplicateLead->id);
            }

            LoggerService::info("Updating plan and premium for parent lead code {$leadModal->code} and child lead code {$duplicateLead->code}");
            // update plan & premium for parent & child lead
            $this->updatePlanAndPremium($leadModal, $duplicateLead);

            // Duplicate travel destinations
            $destinationsCount = $leadModal->TravelDestinations()->count();
            if ($destinationsCount > 0) {
                LoggerService::info("Duplicating {$destinationsCount} travel destinations for lead {$duplicateLead->code}");
                $leadModal->TravelDestinations()->get()->each(function ($destination) use ($duplicateLead) {
                    $duplicateDestination = $destination->replicate();
                    $duplicateDestination->quote_id = $duplicateLead->id;
                    $duplicateDestination->uuid = $duplicateLead->uuid;
                    $duplicateDestination->save();
                });
            }

            // duplicate customer acceptance logs
            $customerAcceptanceLogs = $leadModal->customerAcceptanceLogs()->get();
            if ($customerAcceptanceLogs->count() > 0) {
                LoggerService::info("Duplicating {$customerAcceptanceLogs->count()} customer acceptance logs for lead {$duplicateLead->code}");
                $customerAcceptanceLogs->each(function ($customerAcceptanceLog) use ($duplicateLead) {
                    $duplicateCustomerAcceptanceLog = $customerAcceptanceLog->replicate();
                    $duplicateCustomerAcceptanceLog->quote_uuid = $duplicateLead->uuid;
                    $duplicateCustomerAcceptanceLog->save();
                });
            }

            LoggerService::info("Duplicate lead creation completed successfully: {$leadModal->code} -> {$duplicateLead->code}");
        } else {
            LoggerService::error("Failed to create duplicate lead for {$leadModal->code}");
        }

        return true;
    }

    private function updatePlanAndPremium($parentLead, $childLead)
    {
        $parentPayment = Payment::where('code', $parentLead->code)->first();
        $childPayment = Payment::where('code', $childLead->code)->first();

        $parentLead->premium = $parentPayment->total_price;
        $parentLead->plan_id = $parentPayment->plan_id;
        $parentLead->insurance_provider_id = $parentPayment->insurance_provider_id;
        $parentLead->save();
        LoggerService::info("Updated parent lead code {$parentLead->code} with premium {$parentLead->premium} and plan ID {$parentLead->plan_id}");

        $childLead->premium = $childPayment->total_price;
        $childLead->plan_id = $childPayment->plan_id;
        $childLead->insurance_provider_id = $childPayment->insurance_provider_id;
        $childLead->save();
        LoggerService::info("Updated child lead code {$childLead->code} with premium {$childLead->premium} and plan ID {$childLead->plan_id}");
    }

    public function getTravelDestinations($id)
    {
        return TravelDestination::where('quote_id', $id)->with('destination:id,code,country_name')->get();
    }

    public function getTransactionApprovedQuoteStatus($leadId)
    {
        $transactionApprovedAudit = DB::table('audits as a')
            ->where(function ($query) use ($leadId) {
                $query->where('a.auditable_type', 'App\Models\\'.QuoteTypes::TRAVEL->value.'Quote')
                    ->where('a.auditable_id', $leadId)
                    ->where(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.quote_status_id'))"), QuoteStatusEnum::TransactionApproved);
            })
            ->orderBy('a.created_at', 'DESC')
            ->first();

        return $transactionApprovedAudit;
    }

    public function travelPlanModify($data)
    {
        if (($response = $this->isPlanModifyAllowed($data)) === true) {
            $isUpdate = false;
            $discountedPremium = $data['actual_premium'];

            if (isset($data['is_create']) && $data['is_create'] == 1) {
                $discountedPremium = $data['actual_premium'];
            } elseif (isset($data['discounted_premium'])) {
                $discountedPremium = $data['discounted_premium'];
                $isUpdate = true;
            }

            $addons = $data['addons'] ?? [];

            $travelPlanData = [
                'quoteUID' => $data['travel_quote_uuid'],
                'update' => $isUpdate,
                'url' => strval($data['current_url']),
                'ipAddress' => request()->ip(),
                'userAgent' => request()->header('User-Agent'),
                'userId' => strval(auth()->id()),
                'plans' => [
                    [
                        'planId' => (int) $data['travel_plan_id'],
                        'actualPremium' => (float) $data['actual_premium'],
                        'discountPremium' => (float) $discountedPremium,
                        'addons' => $addons,
                    ],
                ],
            ];

            $response = Ken::request('/save-manual-travel-quote-plan', 'post', $travelPlanData);
        }

        return $response;
    }

    private function isPlanModifyAllowed($data)
    {
        if ($enablePlanValidation = ApplicationStorage::where('key_name', ApplicationStorageEnums::ENABLE_PLAN_MODIFY_VALIDATION)->first()) {
            if (! $enablePlanValidation->value) {
                LoggerService::info('plan modification validation is disabled from backend');

                return true;
            }
        }

        $logPrefix = 'fn: isPlanModifyAllowed ';
        $quote = TravelQuote::where('uuid', $data['travel_quote_uuid'])->with('paymentStatus')->first();
        $paymentStatuses = [
            PaymentStatusEnum::NEW,
            PaymentStatusEnum::PENDING,
            PaymentStatusEnum::DECLINED,
            PaymentStatusEnum::AUTHORISED,
            PaymentStatusEnum::PAID,
            PaymentStatusEnum::PARTIALLY_PAID,
            PaymentStatusEnum::OVERDUE,
            PaymentStatusEnum::CREDIT_APPROVED,
            PaymentStatusEnum::CANCELLED,
            PaymentStatusEnum::REFUNDED,
            PaymentStatusEnum::DISPUTED,
            PaymentStatusEnum::FAILED,
            PaymentStatusEnum::DRAFT,
        ];

        if (in_array($quote->payment_status_id, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])) {
            if (auth()->user()->hasRole(RolesEnum::TravelAdvisor) && $quote->quote_status_id !== QuoteStatusEnum::PolicyIssued) {
                LoggerService::info($logPrefix.' plan modify allowed to advisor for uuid '.$quote->uuid);

                return true;
            } elseif (auth()->user()->hasRole(RolesEnum::TravelManager) && $quote->quote_status_id !== QuoteStatusEnum::PolicyIssued) {
                LoggerService::info($logPrefix.' plan modify allowed to travel manager for uuid '.$quote->uuid);

                return true;
            }
        }

        if (auth()->user()->hasAnyRole([RolesEnum::TravelManager, RolesEnum::TravelAdvisor]) && $quote->quote_status_id !== QuoteStatusEnum::PolicyIssued) {
            if (in_array($quote->payment_status_id, $paymentStatuses) || $quote->payment_status_id == '' || $quote->payment_status_id == null) {
                LoggerService::info($logPrefix.' plan modify allowed for uuid '.$quote->uuid);

                return true;
            }
        }

        LoggerService::info($logPrefix.' plan modification is not allowed for uuid '.$quote->uuid);

        vAbort('Plan Modification is not allowed');
    }

    public function updatedAccessAgainstPaymentStatus($record)
    {
        $paymentStatuses = [
            PaymentStatusEnum::NEW,
            PaymentStatusEnum::PENDING,
            PaymentStatusEnum::DECLINED,
            PaymentStatusEnum::AUTHORISED,
            PaymentStatusEnum::PAID,
            PaymentStatusEnum::PARTIALLY_PAID,
            PaymentStatusEnum::OVERDUE,
            PaymentStatusEnum::CREDIT_APPROVED,
            PaymentStatusEnum::CANCELLED,
            PaymentStatusEnum::REFUNDED,
            PaymentStatusEnum::DISPUTED,
            PaymentStatusEnum::FAILED,
            PaymentStatusEnum::DRAFT,
        ];

        $access['travelAdvisorCanEdit'] = false;
        $access['travelManagerCanEdit'] = false;

        // Travel Advisor Validations
        if (auth()->user()->hasRole(RolesEnum::TravelAdvisor) && ! empty($record->payment_status_id)) {
            if (
                in_array($record->payment_status_id, [PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::CAPTURED]) &&
                $record->quote_status_id !== QuoteStatusEnum::PolicyIssued
            ) {
                $access['travelAdvisorCanEdit'] = true;
            }
        }

        // Travel Manager Validations
        if (auth()->user()->hasRole(RolesEnum::TravelManager) && ! empty($record->payment_status_id)) {
            if (
                in_array($record->payment_status_id, [PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::CAPTURED]) &&
                $record->quote_status_id !== QuoteStatusEnum::PolicyIssued
            ) {
                $access['travelManagerCanEdit'] = true;
            }
        }

        if (auth()->user()->hasAnyRole([RolesEnum::TravelManager, RolesEnum::TravelAdvisor]) && $record->quote_status_id !== QuoteStatusEnum::PolicyIssued) {
            if ((! empty($record->payment_status_id) && in_array($record->payment_status_id, $paymentStatuses)) || $record->payment_status_id == '' || $record->payment_status_id == null) {
                $access['travelAdvisorCanEdit'] = true;
                $access['travelManagerCanEdit'] = true;
            }
        }

        return $access;
    }

    // Update above age members to new quote
    private function updateAboveAgeMember($oldQuoteId, $newQuoteId)
    {
        CustomerMembers::where('quote_id', $oldQuoteId)
            ->where('quote_type', 'App\Models\TravelQuote')
            ->whereDate('dob', '<=', now()->subYears(65))
            ->update(['quote_id' => $newQuoteId]);
    }

    public function getPerMemberPrice($planId)
    {
        $travelPlan = TravelPlan::with(['insuranceProviderQuoteType' => function ($query) {
            $query->where('quote_type_id', QuoteTypeId::Travel);
        }])->where('id', $planId)->first();

        if ($travelPlan && $travelPlan->insuranceProviderQuoteType) {
            return $travelPlan->insuranceProviderQuoteType->per_member_price;
        }

        return false;
    }

    /**
     * Update customer profile details (lead level) if provider is AXA
     * Gets the first member and updates the quote's first_name and last_name
     *
     * @param  string  $quoteType
     * @param  string  $uuid
     * @return void
     */
    public function updateCustomerProfileDetails($quoteType, $uuid)
    {
        try {
            if (strtolower($quoteType) !== strtolower(QuoteTypes::TRAVEL->value)) {
                LoggerService::info(__CLASS__.'::'.__FUNCTION__.' - Not a Travel quote, skipping update customer profile details', [
                    'quote_type' => $quoteType,
                ]);

                return;
            }

            $quoteObject = $this->getQuoteObject(strtolower($quoteType), $uuid, 'uuid');

            LoggerService::startQuoteLogging($quoteObject);
            LoggerService::info(__CLASS__.'::'.__FUNCTION__.' - Update customer profile details');

            $payment = $quoteObject->payments()->mainLeadPayment()->first();
            $insuranceProvider = getInsuranceProvider($payment, QuoteTypes::TRAVEL->value);

            if ($insuranceProvider?->code !== InsuranceProviderEnum::AXA->value) {
                LoggerService::info(__CLASS__.'::'.__FUNCTION__.' - Provider is not AXA, skipping update', [
                    'provider_code' => $insuranceProvider?->code ?? 'null',
                ]);

                return;
            }

            // Get the primary member using primary_member_id
            if (! $quoteObject->primary_member_id) {
                LoggerService::info(__CLASS__.'::'.__FUNCTION__.' - No primary member set for this lead');

                return;
            }

            $customerMember = CustomerMembers::find($quoteObject->primary_member_id);

            if (! $customerMember) {
                LoggerService::info(__CLASS__.'::'.__FUNCTION__.' - Primary member not found', [
                    'primary_member_id' => $quoteObject->primary_member_id,
                ]);

                return;
            }

            $quoteObject->first_name = $customerMember->first_name;
            $quoteObject->last_name = $customerMember->last_name;
            $quoteObject->save();

            LoggerService::info(__CLASS__.'::'.__FUNCTION__.' - Successfully updated customer profile details', [
                'first_name' => $customerMember->first_name,
                'last_name' => $customerMember->last_name,
            ]);

        } catch (\Exception $e) {
            LoggerService::error(__CLASS__.'::'.__FUNCTION__.' - Error updating customer profile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function memberHasAuthorizedPayment(CustomerMembers $member, TravelQuote $quote): bool
    {
        $payments = $quote->payments()->mainLeadPayment()->get();

        if ($payments->isEmpty()) {
            return false;
        }

        $rawDob = $member->getRawOriginal('dob') ?? null;
        $memberAge = 0;
        if ($rawDob !== null && $rawDob !== '') {
            try {
                $memberAge = Carbon::parse($rawDob)->age;
            } catch (\Throwable) {
                $memberAge = 0;
            }
        }

        $memberIsSenior = $memberAge >= GenericRequestEnum::TRAVEL_SENIOR_MEMBER_AGE;
        $relevantPayments = $payments->filter(function (Payment $payment) use ($memberIsSenior): bool {
            $code = $payment->code ?? null;
            $isSeniorPaymentCode = is_string($code) && $code !== '' && str_ends_with(trim($code), '-1');

            return $memberIsSenior ? $isSeniorPaymentCode : ! $isSeniorPaymentCode;
        });

        if ($relevantPayments->isEmpty()) {
            return false;
        }

        $confirmedOrSettledStatuses = PaymentStatusEnum::getConfirmedOrSettledPaymentStatuses();

        return $relevantPayments->contains(function (Payment $payment) use ($confirmedOrSettledStatuses): bool {
            return in_array((int) $payment->payment_status_id, $confirmedOrSettledStatuses, true);
        });
    }
}
