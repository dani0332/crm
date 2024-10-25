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
use App\Models\ApplicationStorage;
use App\Models\DocumentType;
use App\Models\Emirate;
use App\Models\HomeQuote;
use App\Models\PersonalQuote;
use App\Models\SubArea;
use App\Services\AMLService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\DropdownSourceService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Traits\AddPremiumAllLobs;
use App\Traits\CentralTrait;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class HomeQuoteRepository extends BaseRepository
{
    use AddPremiumAllLobs, GenericQueriesAllLobs, CentralTrait;

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

    public function fetchGetData($forExport = false, $forTotalLeadsCount = false)
    {
        // dd('fetchGetData', request()->all());

        // by default add the created_at check
        $addCreatedDateCheck = true;
        if (
            // check if any of the filters are applied, only then don't add the created_at check
            request()->filled('email') ||
            request()->filled('mobile_no') ||
            request()->filled('code') ||
            request()->filled('created_at_start') ||
            request()->filled('created_at_end') ||
            request()->filled('renewal_batch') ||
            request()->filled('previous_quote_policy_number') ||
            request()->filled('payment_due_date') ||
            request()->filled('booking_date')
        ) {
            $addCreatedDateCheck = false;
        }
        $homeQuery = $this->byQuoteTypeCode(QuoteTypes::HOME)->with([
            'quoteDetail.lostReason',
            'quoteStatus',
            'advisor',
            'nationality',
            'homeQuote',
            'homeQuote.homeQuoteRequestDetail',
            'homeQuote.homeQuoteRequestDetail.lostReason',
        ])
            ->when(auth()->user()->hasRole(RolesEnum::HomeAdvisor), function ($query) {
                $query->where('advisor_id', auth()->id());
            })
            ->when(request()->filled('advisors'), fn($q) => $q->whereIn('advisor_id', (array) request('advisors')))
            ->when(request()->has('is_renewal'), function ($query) {
                if (request('is_renewal') === quoteTypeCode::yesText) {
                    $query->whereNotNull('previous_quote_policy_number');
                } elseif (request('is_renewal') === quoteTypeCode::noText) {
                    $query->whereNull('previous_quote_policy_number');
                }
            })
            ->when($addCreatedDateCheck, function ($query) {
                $query->whereBetween('created_at', $this->getDateRange());
            })
            ->filter(! $forExport, $forTotalLeadsCount)
            ->withFakeLeadCriteria($forTotalLeadsCount)
            ->orderBy('created_at', 'desc')
            ->when(
                $forTotalLeadsCount,
                fn($q) => $q->count(),
                fn($query) => $query->when($forExport, fn($q) => $q->get(), fn($q) => $q->simplePaginate())
            );

        return $homeQuery;
    }

    public function fetchGetFormOptions()
    {
        $dropdownSource['iam_possesion_type_id'] = app(DropdownSourceService::class)->getDropdownSource('iam_possesion_type_id');
        $dropdownSource['ilivein_accommodation_type_id'] = app(DropdownSourceService::class)->getDropdownSource('ilivein_accommodation_type_id');
        $dropdownSource['sub_area_id'] = app(DropdownSourceService::class)->getDropdownSource('sub_area_id');

        return [
            'dropdownSource' => $dropdownSource,
            'homePossessionTypeEnum' => HomePossessionType::asArray(),
        ];
    }

    public function fetchCreate($data)
    {
        $quoteData = [
            'hasContents' => $data['has_contents'],
            'hasBuilding' => $data['has_building'],
            'haveClaimedLosses' => $data['have_claimed_losses'],
            'buildingAed' => $data['building_aed'],
            'hasPersonalBelongings' => $data['has_personal_belongings'],
            'isPropertyRentedHolidayHome' => $data['is_property_rented_holiday_home'],
            'propertyAccommodationTypeId' => $data['ilivein_accommodation_type_id'],
            'possesionTypeId' => $data['iam_possesion_type_id'],
            'subAreaId' => $data['sub_area_id'],
            'contentsValueId' => $data['contents_aed'],
            'personalBelongingsValueId' => $data['personal_belongings_aed'],
            'quoteTypeId' => intval(QuoteTypes::HOME->id()),
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'email' => $data['email'],
            'mobileNo' => $data['mobile_no'],
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => URL::current(),
            'createdById' => auth()->id(),
            'advisorId' => (! auth()->user()->hasRole(RolesEnum::Admin)) ? auth()->id() : null,
        ];

        info('Home Quote Create :' . json_encode($quoteData));

        $response = Capi::request('/api/v2-save-home-quote-test', 'post', $quoteData);

        info('Home Quote Create Response :' . json_encode($response));

        // if (isset($response->quoteUID)) {
        //     $this->savePremium(quoteTypeCode::HomeQuote, (object) $data, $response);
        // }

        return $response;
    }

    public function fetchGetBy($column, $value)
    {

        $quote = $this->byQuoteTypeId(QuoteTypes::HOME->id())
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
                            ])
                                ->orderBy('sr_no', 'asc');
                        },
                    ]);
                },
            ])
            ->select([
                $this->getTable() . '.*',
                DB::raw('IF(EXISTS (
                    SELECT *
                    FROM quote_request_entity_mapping
                    WHERE quote_type_id = ' . QuoteTypeId::Home . ' AND quote_request_id = ' . $this->getTable() . '.id),
                    "' . CustomerTypeEnum::Entity . '", "' . CustomerTypeEnum::Individual . '")
                as customer_type'),
            ])
            ->firstOrFail();
        $quote->payments->each->setAppends(['allow', 'copy_link_button', 'edit_button', 'approve_button', 'approved_button']);

        $data = ! empty($quote) ? $quote->toArray() : [];
        $quote->lost_reason = $data['quote_detail']['lost_reason']['text'] ?? null;
        $quote->previous_advisor_id_text = $data['quote_detail']['previous_advisor']['name'] ?? null;
        $quote->transaction_type_text = $data['transaction_type']['text'] ?? null;

        return $quote;
    }

    public function fetchGetShowFormOptions($quote)
    {
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

        @[$documentTypes, $paymentDocument] = app(QuoteDocumentService::class)->getDocumentTypes(QuoteTypeId::Home);
        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($quote);
        $isQuoteDocumentEnabled = app(QuoteDocumentService::class)->isEnabled(QuoteTypes::HOME->value);
        $leadStatuses = app(DropdownSourceService::class)->getDropdownSource('quote_status_id', QuoteTypeId::Home);
        $amlStatusName = AMLStatusCode::getName($quote->aml_status);

        // fetch sub area for the quote
        $subArea = $this->getSubArea($quote->homeQuote->sub_area_id);
        $quote->homeQuote->subArea = $subArea ?? null;

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::HOME->id())->get();
        $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
            return ! in_array($value['id'], [QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::AMLScreeningFailed]);
        })->values();
        $quoteStatuses = app(CentralService::class)->lockTransactionStatus($quote, QuoteTypes::HOME->id(), $quoteStatuses);

        $planURL = $this->getEcomQuoteLink(QuoteTypes::HOME, $quote->uuid);
        $allowedDuplicateLOB = app(CRUDService::class)->getAllowedDuplicateLOB('home', $quote->code);
        // dd($subArea, $quote->homeQuote->sub_area_id, $quote);

        return [
            'documentTypes' => $documentTypes,
            'paymentDocument' => $paymentDocument,
            'storageUrl' => storageUrl(),
            'quoteType' => QuoteTypes::HOME,
            'quoteTypeId' => QuoteTypeId::Home,
            'quoteStatuses' => $quoteStatuses,
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
            'cdnPath' => config('constants.AZURE_IM_STORAGE_URL') . config('constants.AZURE_IM_STORAGE_CONTAINER') . '/',
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
        ];
    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        return Capi::request('/api/v1-save-' . strtolower(QuoteTypes::HOME->value) . '-quote', 'post', $dataArr);
    }

    public function fetchUpdate($uuid, $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->byQuoteTypeId(QuoteTypes::HOME->id())->where('uuid', $uuid)->firstOrFail();

            //check the columns to be updated in personal quotes.
            $quoteData = Arr::only($data, (new PersonalQuote)->allowedColumns());

            $quoteData['updated_by_id'] = auth()->user()->id;
            $quote->update($quoteData);

            // allowed columns for home quote
            $homeAllowedColumns = ['iam_possesion_type_id', 'ilivein_accommodation_type_id', 'address', 'has_contents', 'has_building', 'has_personal_belongings', 'contents_aed', 'building_aed', 'personal_belongings_aed', 'have_claimed_losses', 'is_property_rented_holiday_home', 'sub_area_id'];

            // check the columns to be updated in home quote request.
            if ($quote->homeQuote) {
                $quote->homeQuote()->update(Arr::only($data, $homeAllowedColumns));
            } else {
                $quote->homeQuote()->create(Arr::only($data, $homeAllowedColumns));
            }

            return $quote;
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
        return SubArea::select('id', 'text', 'description')->where('id', $subAreaId)->first();
    }
}
