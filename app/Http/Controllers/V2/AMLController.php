<?php

namespace App\Http\Controllers\V2;

use App\Enums\AMLDecisionStatusEnum;
use App\Enums\CustomerTypeEnum;
use App\Enums\LookupsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\AMLCheckRequest;
use App\Http\Requests\AMLRequest;
use App\Http\Requests\UpdateAMLCustomerDetailRequest;
use App\Http\Requests\UpdateAMLEntityDetailRequest;
use App\Jobs\BridgerAMLJob;
use App\Models\AML;
use App\Models\ApplicationStorage;
use App\Models\BusinessCoverType;
use App\Models\BusinessQuoteType;
use App\Models\CommunicationMode;
use App\Models\Customer;
use App\Models\Emirate;
use App\Models\Entity;
use App\Models\KycLog;
use App\Models\Lookup;
use App\Models\PersonalQuote;
use App\Models\QuoteRequestEntityMapping;
use App\Models\QuoteStatus;
use App\Models\QuoteType;
use App\Models\SanctionListDownloads;
use App\Models\UAEAMLListUploads;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\EntityRepository;
use App\Repositories\LookupRepository;
use App\Repositories\NationalityRepository;
use App\Repositories\QuoteTypeRepository;
use App\Services\AMLService;
use App\Services\BridgerInsightService;
use App\Services\CheckAmlService;
use App\Services\QuoteStatusService;
use App\Services\SanctionListService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;

class AMLController extends Controller
{
    use GenericQueriesAllLobs;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('permission:aml-list', ['only' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(AMLRequest $request)
    {
        $quoteTypes = QuoteTypeRepository::allowedQuoteForAml();
        $quoteStatuses = QuoteStatus::withActive()->orderBy('sort_order')->get();
        $quotes = [];

        if ($request->ajax()) {
            if (isset($request->quoteType) && ! empty($request->quoteType)) {
                $quoteTypeId = $quoteTypes->where('code', $request->quoteType)->first()?->id;
                $quoteRequestTable = strtolower($request->quoteType).'_quote_request';

                if (in_array($quoteTypeId, [
                    QuoteTypes::BIKE->id(),
                    QuoteTypes::YACHT->id(),
                    QuoteTypes::PET->id(),
                    QuoteTypes::CYCLE->id(),
                    QuoteTypes::JETSKI->id(),
                ])) {
                    if (isset($request->amlCreatedStartDate) && ! empty($request->amlCreatedStartDate)) {
                        $quoteRequestTable = AMLService::isDataMigrated($quoteTypeId, '', $request->amlCreatedStartDate) ? 'personal_quotes' : $quoteRequestTable;
                    } else {
                        if (isset($request->searchType) && in_array($request->searchType, ['cdbId', 'customerEmail', 'id'])) {
                            $searchType = match ($request->searchType) {
                                'cdbId' => 'code',
                                'customerEmail' => 'email',
                                'id' => 'id'
                            };

                            $createdDate =
                                $request->searchType == 'id' ? AML::where($searchType, $request->searchField)->firstOrFail()->created_at :
                                PersonalQuote::where($searchType, $request->searchField)->firstOrFail()->created_at;

                            $quoteRequestTable = AMLService::isDataMigrated($quoteTypeId, '', $createdDate) ? 'personal_quotes' : strtolower($request->quoteType).'_quote_request';
                        }
                    }
                }

                $dataAml = AML::select('kyc_logs.*', 'quote_type.text as quote_type_text', $quoteRequestTable.'.code as cdb_id')
                    ->leftjoin('quote_type', 'quote_type.id', 'kyc_logs.quote_type_id')
                    ->leftjoin($quoteRequestTable, $quoteRequestTable.'.id', 'kyc_logs.quote_request_id')
                    ->where('kyc_logs.quote_type_id', $quoteTypeId)
                    ->orderBy('kyc_logs.created_at', 'desc');

                if (
                    isset($request->searchType) && ! empty($request->searchType) &&
                    isset($request->searchField) && ! empty($request->searchField)
                ) {
                    if ($request->searchType == 'cdbId') {
                        $dataAml->where($quoteRequestTable.'.code', $request->searchField);
                    }
                    if ($request->searchType == 'id') {
                        $dataAml->where('kyc_logs.id', $request->searchField);
                    }
                    if ($request->searchType == 'customerEmail') {
                        $dataAml->where($quoteRequestTable.'.email', $request->searchField);
                    }
                }
                if (isset($request->matchFound)) {
                    if ($request->matchFound == 'False') {
                        $dataAml->where('kyc_logs.results_found', '=', '0');
                    }
                    if ($request->matchFound == 'True') {
                        $dataAml->where('kyc_logs.results_found', '>', '0');
                    }
                }
                if (
                    isset($request->amlCreatedStartDate) && ! empty($request->amlCreatedStartDate) &&
                    isset($request->amlCreatedEndDate) && ! empty($request->amlCreatedEndDate)
                ) {
                    $amlCreatedDate = date(config('constants.DATE_FORMAT_ONLY').' 00:00:00', strtotime($request->amlCreatedStartDate));
                    $amlEndDate = date(config('constants.DATE_FORMAT_ONLY').' 23:59:59', strtotime($request->amlCreatedEndDate));
                    $dataAml->whereBetween('kyc_logs.created_at', [$amlCreatedDate, $amlEndDate]);
                }

                $quotes = $dataAml->simplePaginate(10)->withQueryString();
            }
        }

        return inertia('Aml/Index', [
            'quoteTypes' => $quoteTypes,
            'quoteStatuses' => $quoteStatuses,
            'aml' => $quotes,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(AML $aml)
    {
        $responseFrom = 'RYU';
        $amlResults = json_decode($aml->results);
        $aml->quote_type_text = $aml->quotetype->text;
        $quoteStatusCodes = quoteStatusCode::asArray();
        $amlDecisionStatusCodes = AMLDecisionStatusEnum::asArray();

        if (isset($amlResults[0]->Watchlist)) {
            $responseFrom = 'Bridger';
            $amlResults = collect($amlResults[0]->Watchlist->Matches)->filter(function ($value) {
                $value->decision = (! $value->FalsePositive && ! $value->TrueMatch) ? AMLDecisionStatusEnum::UNKNOWN : AMLDecisionStatusEnum::TRUE_MATCH;

                return $value->FalsePositive == false;
            })->values();
        }

        return inertia('Aml/Show', [
            'aml' => $aml,
            'amlResults' => $amlResults,
            'responseFrom' => $responseFrom,
            'quoteStatusCode' => $quoteStatusCodes,
            'amlDecisionStatusCode' => $amlDecisionStatusCodes,
        ]);
    }

    public function amlQuoteDetails($quoteTypeId, $quoteRequestId)
    {
        $quoteStatusCode = '';
        $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();
        $isCompanySearchEnabled = ApplicationStorage::where('key_name', '=', 'IS_AML_ENTITY_SEARCH_ENABLED')->value('value');
        $amlRecordFetch = AML::with('quotetype')->where(['quote_request_id' => $quoteRequestId, 'quote_type_id' => $quoteTypeId])
            ->where(function ($aml) {
                $aml->whereNotIn('decision', [AMLDecisionStatusEnum::RYU]);
                $aml->orWhereNull('decision');
            });
        $kycLogs = $amlRecordFetch->orderBy('created_at', 'desc')->get();

        $quoteRequest = AMLService::getQuoteDetails($quoteTypeId, $quoteRequestId);
        $customerDetails = Customer::where('id', $quoteRequest->customer_id)->with('detail')->firstOrFail();
        $entityDetails = QuoteRequestEntityMapping::with('entity')->where(['quote_type_id' => $quoteTypeId, 'quote_request_id' => $quoteRequestId])->first() ?? [];
        $membersDetail = CustomerMembersRepository::getBy('quote_id', $quoteRequest->id, $quoteType->code);

        $uboDetails = CustomerMembersRepository::getBy('quote_id', $quoteRequest->id, $quoteType->code, CustomerTypeEnum::Entity);
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $nationalities = NationalityRepository::withActive()->get();
        $emirates = Emirate::where('is_active', 1)->orderBy('sort_order')->get();
        $industryType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
        $isCurrentUserFromCompliance = auth()->user()->hasRole(RolesEnum::COMPLIANCE) ? 1 : 0;
        $isCurrentUserFromPaAml = auth()->user()->hasAnyRole([RolesEnum::PA, RolesEnum::AML]) ? 1 : 0;
        $sanctionListService = app(SanctionListService::class);
        $nationalityList = $sanctionListService->fetchNationality();
        $yearsList = $sanctionListService->years();
        $firstAmlLogResults = $amlRecordFetch->first()->results_found ?? 0;
        $latestAmlLogResults = $amlRecordFetch->latest()->first()->results_found ?? 0;
        $getAMLNumRows = $amlRecordFetch->count();

        if ($quoteRequest && $quoteRequest->quote_status_id && $quoteRequest->quote_status_id != '') {
            $quoteStatus = QuoteStatus::where('id', '=', $quoteRequest->quote_status_id)->get(['code']);
            $quoteStatusCode = $quoteStatus[0]->code;
        }

        $lookups = Lookup::whereIn('key', [
            LookupsEnum::RESIDENT_STATUS,
            LookupsEnum::DOCUMENT_ID_TYPE,
            LookupsEnum::ENTITY_DOCUMENT_TYPE,
            LookupsEnum::MODE_OF_CONTACT,
            LookupsEnum::MODE_OF_DELIVERY,
            LookupsEnum::EMPLOYMENT_SECTOR,
            LookupsEnum::LEGAL_STRUCTURE,
            LookupsEnum::ISSUANCE_PLACE,
            LookupsEnum::ISSUING_AUTHORITY,
            LookupsEnum::COMPANY_POSITION,
            LookupsEnum::PROFESSIONAL_TITLE,
            LookupsEnum::UBO_RELATION,
            LookupsEnum::COMPANY_TYPE,
        ])->get()->groupBy('key');

        //lookups , loop through each key, replace - with _ and update key
        $lookups = $lookups->mapWithKeys(function ($item, $key) {
            return [str_replace('-', '_', $key) => $item];
        });
        $entities = Entity::all();
        $amlDecisionStatusEnum = AMLDecisionStatusEnum::asArray();

        $data = [
            'quoteType' => $quoteType,
            'quoteRequest' => $quoteRequest,
            'entityDetails' => $entityDetails,
            'membersDetails' => $membersDetail,
            'uboDetails' => $uboDetails,
            'memberRelations' => $memberRelations,
            'uboRelations' => $uboRelations,
            'nationalities' => $nationalities,
            'emirates' => $emirates,
            'industryType' => $industryType,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'kycLogs' => $kycLogs,
            'quoteStatusCode' => $quoteStatusCode,
            'isCurrentUserFromCompliance' => $isCurrentUserFromCompliance,
            'isCurrentUserFromPaAml' => $isCurrentUserFromPaAml,
            'firstAmlLogResults' => $firstAmlLogResults,
            'latestAmlLogResults' => $latestAmlLogResults,
            'getAMLNumRows' => $getAMLNumRows,
            'nationalityList' => $nationalityList,
            'yearsList' => $yearsList,
            'isCompanySearchEnabled' => $isCompanySearchEnabled,
            'customerDetails' => $customerDetails,
            'amlDecisionStatusEnum' => $amlDecisionStatusEnum,
            'lookups' => $lookups,
            'entities' => $entities,
            'quoteAmlStatus' => $this->checkAmlQuoteStatus($quoteRequest->quote_status_id),
        ];

        if ($quoteType->code == quoteTypeCode::Business) {
            $data['businessTypeCode'] = BusinessQuoteType::where('id', $quoteRequest->business_type_of_insurance_id)->value('code');
            $data['businessCoverTypeText'] = BusinessCoverType::where('id', $quoteRequest->business_cover_type_id)->value('text');
            $data['businessCommuModeText'] = CommunicationMode::where('id', $quoteRequest->business_communication_mode_id)->value('text');
        }

        return inertia('Aml/Details', $data);
    }

    public function quoteStatusUpdate($quoteTypeId, $quoteRequestId, $quoteStatusType)
    {
        $updateQuoteStatusResp = app(QuoteStatusService::class)->updateQuoteStatus($quoteTypeId, $quoteRequestId, $quoteStatusType, \request()->toArray());

        if ($updateQuoteStatusResp == 'false') {
            return redirect()->back()->with('message', 'Quote Status is not updated');
        } else {
            $quoteStatusText = $updateQuoteStatusResp[0];
            $quoteCdbId = $updateQuoteStatusResp[1];
            $quoteTypeText = $updateQuoteStatusResp[2];
            $quotePaID = $updateQuoteStatusResp[3];
            $clientFullName = $updateQuoteStatusResp[4];
            if (auth()->user()->hasRole(RolesEnum::COMPLIANCE)) {
                info('Bridger Insight - Decision update Email triggered to Compliance Team');
                app(CheckAmlService::class)->sendAMLQuoteStatusChangeNotification($quoteTypeId, $quoteRequestId, $quoteStatusText, $quoteCdbId, $quoteTypeText, $quotePaID, $clientFullName);
            }
            // Update Decision on Lexis Nexis Portal
            if (isset(request()->decisonsForUpdatePortal)) {
                AMLService::updateAMLDecisionLexisNexis(request());
            }

            return redirect()->back()->with('success', 'Quote Status is set to '.$quoteStatusText.'');
        }
    }

    public function updateCustomerDetails(UpdateAMLCustomerDetailRequest $request)
    {
        $customer = CustomerRepository::updateCustomerDetails($request->customer_id, $request->safe());

        return response()->json(['success' => true]);
    }

    public function updateEntityDetails(UpdateAMLEntityDetailRequest $request)
    {
        $entity = EntityRepository::updateEntityDetail($request->safe());

        return response()->json(['success' => true]);
    }

    public function quoteUpdate(AMLCheckRequest $AMLCheckRequest, $quoteTypeId, $quoteRequestId)
    {
        $quoteId = $quoteRequestId;
        $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();

        if (checkPersonalQuotes($quoteType->code) && (! AMLService::isDataMigrated($quoteTypeId, $quoteRequestId))) {
            $quoteId = AMLService::getPersonalQuoteId($quoteTypeId, $quoteId);
        }

        $updateQuote = $this->getQuoteObject($quoteType->code, $quoteId);
        $getMemberOrUBODetails = AMLService::getMemberOrUBODetails($AMLCheckRequest, $quoteType, $quoteId);

        if ($updateQuote) {
            info('Bridger Insight - Get Quote Successfully');
            if (auth()->user()->hasAnyRole([RolesEnum::AML, RolesEnum::PA])) {
                if (checkPersonalQuotes($quoteType->code)) {
                    AMLService::updatePaIdForPersonalQuotes($quoteTypeId, $quoteRequestId, AMLService::isDataMigrated($quoteTypeId, $quoteId));
                } else {
                    $updateQuote->pa_id = auth()->user()->id;
                    $updateQuote->save();
                }
            }

            session()->put('amlResponseCheck', []);
            $bridgerInsightService = new BridgerInsightService();
            $bridgerAPIToken = $bridgerInsightService->getJWTToken();

            $kycLogs = KycLog::where(['quote_request_id' => $quoteRequestId, 'quote_type_id' => $quoteTypeId])
                ->where(function ($aml) {
                    $aml->whereNotIn('decision', [AMLDecisionStatusEnum::RYU]);
                    $aml->orWhereNull('decision');
                })->withTrashed()->get()->pluck('decision')->toArray();

            if ($AMLCheckRequest->customer_type == CustomerTypeEnum::Individual) {
                info('Bridger Insight - Customer type : Individual');
                $customer = Customer::with('nationality')->findOrFail($AMLCheckRequest->customer_id);
                $customerUpdate = $AMLCheckRequest->validated();
                // Temporary comment this code, please don't remove it.
                //                if ( filter_var(\request()->withFullName, FILTER_VALIDATE_BOOLEAN)) {
                //                    $fullName = explode(' ', \request()->insured_fullname);
                //                    $insuredFirstName = $fullName[0] ?? '';
                //                    unset($fullName[0]);
                //                    $customerUpdate = [
                //                        'nationality_id' => $AMLCheckRequest->nationality_id,
                //                        'dob' => $AMLCheckRequest->dob,
                //                        'insured_first_name' => $insuredFirstName,
                //                        'insured_last_name' => implode(' ', $fullName)
                //                    ];
                //                }
                $customer->update($customerUpdate);
                $customer->refresh();
                info('Bridger Insight - Customer Updated Successfully');

                $getMemberOrUBODetails[] = [
                    'first_name' => $customer->insured_first_name,
                    'last_name' => $customer->insured_last_name,
                    'dob' => Carbon::parse($customer->dob)->format(config('constants.DATE_FORMAT_ONLY')),
                    'nationality' => $customer->nationality->toArray() ?? [],
                    'code' => CustomerTypeEnum::IndividualShort.'-'.$customer->id,
                    //                    'with_full_name' => \request()->withFullName
                ];

                foreach ($getMemberOrUBODetails as $memberDetail) {
                    BridgerAMLJob::dispatchSync(
                        $bridgerAPIToken,
                        $memberDetail,
                        $quoteRequestId,
                        $quoteTypeId,
                        CustomerTypeEnum::Individual,
                        auth()->user()->email
                    );
                }

                if (! in_array(true, session()->get('amlResponseCheck')) && ! in_array(AMLDecisionStatusEnum::TRUE_MATCH_REJECT_RISK, $kycLogs)) {
                    $updateQuote->quote_status_id = QuoteStatusEnum::AMLScreeningCleared;
                    $updateQuote->save();
                    info('Bridger Insight Service - Update Lead Quote Status to AML Screen Clear - ID:'.QuoteStatusEnum::AMLScreeningCleared);
                } else {
                    $updateQuote->quote_status_id = QuoteStatusEnum::AMLScreeningFailed;
                    $updateQuote->save();
                    info('Bridger Insight Service - Update Lead Quote Status to AML Screen Failed new Escalated case Found - ID:'.QuoteStatusEnum::AMLScreeningFailed);
                }
                session()->forget('amlResponseCheck');
            }

            if ($AMLCheckRequest->customer_type == CustomerTypeEnum::Entity) {
                info('Bridger Insight - Customer type : Entity');
                $entity = Entity::updateOrCreate(['trade_license_no' => $AMLCheckRequest->trade_license_no], [
                    'company_name' => $AMLCheckRequest->company_name,
                    'company_address' => $AMLCheckRequest->company_address,
                    'industry_type_code' => $AMLCheckRequest->industry_type_code,
                    'emirate_of_registration_id' => $AMLCheckRequest->emirate_of_registration_id,
                ]);
                $entityId = $entity->id;
                $entity->update(['code' => CustomerTypeEnum::EntityShort.'-'.$entityId]);
                info('Bridger Insight - Entity Updated Successfully');

                QuoteRequestEntityMapping::updateOrCreate([
                    'quote_type_id' => $quoteType->id,
                    'quote_request_id' => $quoteRequestId,
                ], ['entity_id' => $entityId, 'entity_type_code' => $AMLCheckRequest->entity_type_code]);

                // Bridger Insight API Call for Entity
                $entityDetailsForApi = ['company_name' => $entity->company_name, 'code' => CustomerTypeEnum::EntityShort.'-'.$entity->id];
                BridgerAMLJob::dispatchSync($bridgerAPIToken, $entityDetailsForApi, $quoteRequestId, $quoteTypeId, CustomerTypeEnum::Entity, auth()->user()->email);

                foreach ($getMemberOrUBODetails as $memberDetail) {
                    BridgerAMLJob::dispatchSync($bridgerAPIToken, $memberDetail, $quoteRequestId, $quoteTypeId, CustomerTypeEnum::Individual, auth()->user()->email);
                }

                if (! in_array(true, session()->get('amlResponseCheck')) && ! in_array(AMLDecisionStatusEnum::TRUE_MATCH_REJECT_RISK, $kycLogs)) {
                    $updateQuote->quote_status_id = QuoteStatusEnum::AMLScreeningCleared;
                    $updateQuote->save();
                    info('Bridger Insight Service - Update Lead Quote Status to AML Screen Clear - ID:'.QuoteStatusEnum::AMLScreeningCleared);
                } else {
                    $updateQuote->quote_status_id = QuoteStatusEnum::AMLScreeningFailed;
                    $updateQuote->save();
                    info('Bridger Insight Service - Update Lead Quote Status to AML Screen Failed new Escalated case Found - ID:'.QuoteStatusEnum::AMLScreeningFailed);
                }
                session()->forget('amlResponseCheck');
            }

            return redirect()->back();
        }

        return redirect()->back()->with('error', 'Something went wrong');
    }

    public function insuredPayerDetailsUpdate(AMLCheckRequest $AMLCheckRequest)
    {
        /*
         * ======== PLEASE DON'T REMOVE THIS COMMENTED CODE YET ========
         * ======== THIS CODE IS FOR FUTURE REFERENCE ========
         */

        // dd($AMLCheckRequest->toArray());

        // if (isset($AMLCheckRequest->payment_Details) && count($AMLCheckRequest->payment_Details) > 0) {
        //     foreach ($AMLCheckRequest->payment_Details as $paymentDetail) {
        //         $payment = Payment::where('code', $paymentDetail['paymentCode'])->first();
        //         if ($payment) {
        //             $customerInstrument = CustomerPaymentInstrument::find($payment->customer_payment_instrument_id)
        //                 ->first();

        //             if ($customerInstrument && $paymentDetail['paymentMethod'] == 'Credit Card'
        //                 && ($customerInstrument->card_holder_name == null || $customerInstrument->card_holder_name == '')) {
        //                 $customerInstrument->update([
        //                     'card_holder_name' => $paymentDetail['payerName'],
        //                 ]);
        //             } elseif (! $customerInstrument) {
        //                 return response()->json([
        //                     'status' => false,
        //                     'message' => 'Customer Instrument record not found',
        //                 ]);
        //             } else {
        //                 $payment->update([
        //                     'payer_name' => $paymentDetail['payerName'],
        //                 ]);
        //             }

        //             $payment->update([
        //                 'paid_by' => $paymentDetail['paidBy'],
        //             ]);

        //             return response()->json(['status' => true, 'message' => 'Updated']);
        //         } else {
        //             return response()->json(['status' => false, 'message' => 'Payment not found']);
        //         }
        //     }
        // } else {
        //     return response()->json(['status' => false, 'message' => 'Payments data missing']);
        // }

        // return response()->json(['status' => false, 'message' => 'Whoops! Something went wrong']);
    }

    public function sanctionListHistory(Request $request, SanctionListDownloads $sanctionListDownloads, Datatables $datatables)
    {
        $url = env('AZURE_RYU_STORAGE_URL').env('AZURE_AML_HISTORY');

        $sanctionListDownloads = $sanctionListDownloads->newQuery();

        if ($request->file_name != '') {
            $sanctionListDownloads = $sanctionListDownloads->where('file_name', 'like', '%'.$request->file_name.'%');
        }
        if ($request->is_processed == '0' || $request->is_processed == '1') {
            $value = $request->is_processed == '1';
            $sanctionListDownloads = $sanctionListDownloads->where('is_processed', $value);
        }
        $orderBy = $request->sortBy == '' ? 'created_at' : $request->sortBy;
        $sortType = $request->sortType == '' ? 'DESC' : $request->sortType;

        $sanctionListDownloads = $sanctionListDownloads->orderBy($orderBy, $sortType)->paginate(10);

        return inertia('Aml/History', [
            'sanctionListDownloads' => $sanctionListDownloads,
            'url' => $url,
        ]);

        // return view('aml.history', compact('url'));
    }

    public function uaeSanctionListUpload(Request $request)
    {
        $this->validate($request, [
            'file_name' => 'required|mimetypes:application/vnd.ms-excel,text/anytext,application/octet-stream,application/txt,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet|max:2048',
        ]);

        $getUAEUploadRecord = UAEAMLListUploads::where('id', '=', 1)->get()->first();

        if ($getUAEUploadRecord == null) {
            $newUAEUploadRecord = new UAEAMLListUploads([
                'id' => 1,
                'file_name' => '16-11-2021_UAESanctionlist.xls',
                'is_updated' => false,
            ]);
            $newUAEUploadRecord->save();
        }

        $fileNameOriginal = $request->file_name->getClientOriginalName();
        $fileNameAzure = date('d-m-Y').'_'.$fileNameOriginal;
        $request->file('file_name')->storeAs('/', $fileNameAzure, 'azureForRyu');

        $newUpload = UAEAMLListUploads::where('id', '=', 1)->get()->first();
        $newUpload->file_name = $fileNameAzure;
        $newUpload->is_updated = true;
        $newUpload->save();

        return redirect('/kyc/aml/upload/uae')->with('success', 'UAE Sanction list uploaded successfully');
    }

    public function uploadUaeSanctionList()
    {
        return inertia('Aml/UploadUae');
        // return view('aml.upload');
    }

    public function fetchEntity(Request $request)
    {
        $entity = Entity::where('trade_license_no', $request->trade_license)->first();

        if ($entity) {
            return response()->json(['status' => true, 'response' => $entity, 'message' => 'Entity found with the entered Trade License number']);
        }

        return response()->json(['status' => false, 'message' => 'No Entity found with the entered Trade License number']);
    }

    public function linkEntityDetails(Request $request)
    {
        $updateFields = ['entity_id' => $request->entity_id, 'entity_type_code' => LookupsEnum::PARENT_ENTITY];
        if ($request->triggeredFrom) {
            $updateFields['entity_type_code'] = LookupsEnum::SUB_ENTITY;
        }

        QuoteRequestEntityMapping::updateOrCreate(['quote_type_id' => $request->quote_type_id, 'quote_request_id' => $request->quote_request_id], $updateFields);
        $entity = Entity::with(
            [
                'quoteRequestEntityMapping' => function ($mappedEntity) use ($request) {
                    $mappedEntity->where(['quote_type_id' => $request->quote_type_id, 'quote_request_id' => $request->quote_request_id]);
                },
            ]
        )->where('id', $request->entity_id)->first();

        return response()->json(['status' => true, 'response' => $entity, 'message' => 'Entity Linked Successfully']);
    }

    public function sendBridgerResponse(Request $request)
    {
        info('Bridger Insight : Email Triggered to Compliance Super User - Ref ID: '.$request['quote_ref_id'].' - Email triggered by: '.auth()->user()->email);
        AMLService::sendAMLMatchedEmailtoComplianceTeam(
            config('constants.APP_URL').$request['aml_quote_url'],
            $request['quote_ref_id'],
            $request['bridger_response'],
            $request['customer_entity_name'],
            $request['quote_type_text'],
            auth()->user()->email,
            true
        );

        return response()->json(['message' => 'Email Triggered to Compliance Super User']);
    }

    private function checkAmlQuoteStatus($statusId)
    {
        if ($statusId == QuoteStatusEnum::AMLScreeningCleared) {
            return 2;
        } elseif ($statusId == QuoteStatusEnum::AMLScreeningFailed) {
            return 1;
        }

        return null;
    }
}
