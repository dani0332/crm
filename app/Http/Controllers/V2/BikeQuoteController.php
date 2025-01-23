<?php

namespace App\Http\Controllers\V2;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CarPlanAddonsCode;
use App\Enums\CarPlanExclusionsCode;
use App\Enums\CarPlanFeaturesCode;
use App\Enums\CarPlanType;
use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\TiersEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BikeQuoteRequest;
use App\Http\Requests\ChangeInsurerRequest;
use App\Models\ApplicationStorage;
use App\Models\Emirate;
use App\Models\Nationality;
use App\Models\PersonalQuote;
use App\Models\Tier;
use App\Repositories\ActivityRepository;
use App\Repositories\BikeQuoteRepository;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\PersonalPlanRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Repositories\UserRepository;
use App\Services\BikeEmailService;
use App\Services\BikeQuoteService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\EmailStatusService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\Reports\RenewalBatchReportService;
use App\Services\SendEmailCustomerService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Services\UserService;
use App\Traits\CentralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BikeQuoteController extends Controller
{
    use CentralTrait;

    private $bikeQuoteService;

    public function __construct(BikeQuoteService $bikeQuoteService)
    {
        $this->bikeQuoteService = $bikeQuoteService;
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $personalQuotes = BikeQuoteRepository::getData();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::BIKE->value);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::BIKE->id())->get();
        $authorizedDays = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
        $renewalBatches = app(RenewalBatchReportService::class)->getAllNonMotorBatches();

        return inertia('BikeQuote/Index', [
            'quotes' => $personalQuotes,
            'quoteStatuses' => $quoteStatuses,
            'renewalBatches' => $renewalBatches,
            'advisors' => $advisors,
            'authorizedDays' => intval($authorizedDays->value),
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        $data = BikeQuoteRepository::getFormOptions();

        return inertia('BikeQuote/Form', $data);
    }

    /**
     * @param  $quoteTypeCode
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(BikeQuoteRequest $request)
    {
        $response = BikeQuoteRepository::create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect('personal-quotes/bike/'.$response->quoteUID)->with('message', 'Quote created successfully');
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($uuid)
    {
        $data = BikeQuoteRepository::getFormOptions();

        $quote = BikeQuoteRepository::getBy('uuid', $uuid);
        $bikeQuoteRequestDetail = $quote->bikeQuote ?? null;

        return inertia(
            'BikeQuote/Form',
            array_merge($data, [
                'quote' => $quote,
                'bikeQuoteDetail' => $bikeQuoteRequestDetail,
            ])
        );
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($uuid)
    {
        /* Start - Temporarily adding for correcting historic data */
        $quote = BikeQuoteRepository::where('uuid', $uuid)->first();
        abort_if(! $quote, 404);
        (new PaymentRepository)->updatePriceVatApplicableAndVat($quote, QuoteTypes::BIKE->value);
        /* End - Temporarily adding for correcting historic data */

        $quote = BikeQuoteRepository::getBy('uuid', $uuid);
        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails(QuoteTypes::BIKE->value, $quote);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::BIKE->id())->get();
        $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
            return ! in_array($value['id'], [QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::AMLScreeningFailed]);
        })->values();
        $membersDetail = CustomerMembersRepository::getBy($quote->id, QuoteTypes::BIKE->name);
        @[$documentTypes, $paymentDocument] = app(QuoteDocumentService::class)->getDocumentTypes(QuoteTypeId::Bike);
        if (! auth()->user()->can(PermissionsEnum::UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return ! in_array($value['id'], [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
            })->values();
        }
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();
        $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::BIKE->id());
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::BIKE->value);

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::BIKE->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee', 'quoteStatus')->orderBy('created_at', 'desc')->get();

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::BIKE->id(), $quote->id);
        $uboDetails = CustomerMembersRepository::getBy($quote->id, QuoteTypes::BIKE->name, CustomerTypeEnum::Entity);
        $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();

        $quoteStatuses = app(CentralService::class)->lockTransactionStatus($quote, QuoteTypes::BIKE->id(), $quoteStatuses);

        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = (new LookupService)->getSendUpdateOptions(QuoteTypes::BIKE->id());
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }
        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;
        $isQuoteDocumentEnabled = app(QuoteDocumentService::class)->isEnabled(QuoteTypes::BIKE->value);
        $quoteDocuments = (new QuoteDocumentService)->getQuoteDocuments(QuoteTypes::BIKE->value, $quote->id);
        $bookPolicyDetails = $this->bookPolicyPayload($quote, QuoteTypes::BIKE->value, $quote->payments, $quoteDocuments);
        $yearsOfManufacture = app(LookupService::class)->getYearsOfManufacture();

        // We user personal quotes id in email status
        $emailStatuses = app(EmailStatusService::class)->getEmailStatus(QuoteTypeId::Bike, $quote->id);

        $bikeQuotePlanAddons = BikeQuoteRepository::bikeQuotePlanAddons($uuid);
        $carPlanTypeEnum = CarPlanType::asArray();
        $carPlanExclusionsCodeEnum = CarPlanExclusionsCode::asArray();
        $carPlanFeaturesCodeEnum = CarPlanFeaturesCode::asArray();
        $carPlanAddonsCodeEnum = CarPlanAddonsCode::asArray();
        $planURL = $this->getEcomQuoteLink(QuoteTypes::BIKE, $uuid);
        $websiteURL = config('constants.AFIA_WEBSITE_DOMAIN');
        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($quote);
        $amlStatusName = AMLStatusCode::getName($quote->aml_status);

        return inertia('BikeQuote/Show', [
            'quoteType' => QuoteTypes::BIKE,
            'quote' => $quote,
            'record' => $quote,
            'amlStatusName' => $amlStatusName,
            'activities' => $activities,
            'lostReasons' => $lostReasons,
            'quoteTypeId' => QuoteTypes::BIKE->id(),
            'advisors' => $advisors,
            'documentTypes' => $documentTypes,
            'quoteStatuses' => $quoteStatuses,
            'paymentMethods' => $paymentMethods,
            'insuranceProviders' => $insuranceProviders,
            'personalPlans' => $personalPlans,
            'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
            'storageUrl' => storageUrl(),
            'modelType' => QuoteTypes::BIKE,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::BikeManager),
            'embeddedProducts' => $embeddedProducts,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'membersDetails' => $membersDetail,
            'memberRelations' => $memberRelations,
            'nationalities' => $nationalities,
            'emirates' => $emirates,
            'UBOsDetails' => $uboDetails,
            'UBORelations' => $uboRelations,
            'vatPercentage' => $vatPercentage,
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'isNewPaymentStructure' => app(SplitPaymentService::class)->isNewPaymentStructure($quote->payments),
            'permissions' => [
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
            ],
            'bookPolicyDetails' => $bookPolicyDetails,
            'payments' => $quote->payments->toArray() ?? [],
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'sendUpdateEnum' => $sendUpdateEnum,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'linkedQuoteDetails' => $linkedQuoteDetails,
            'yearsOfManufacture' => $yearsOfManufacture,
            'emailStatuses' => $emailStatuses,
            'bikeQuotePlanAddons' => $bikeQuotePlanAddons,
            'carPlanExclusionsCodeEnum' => $carPlanExclusionsCodeEnum,
            'carPlanAddonsCodeEnum' => $carPlanAddonsCodeEnum,
            'carPlanFeaturesCodeEnum' => $carPlanFeaturesCodeEnum,
            'planURL' => $planURL,
            'carPlanTypeEnum' => $carPlanTypeEnum,
            'paymentStatusEnum' => PaymentStatusEnum::asArray(),
            'websiteURL' => $websiteURL,
            'lockLeadSectionsDetails' => $lockLeadSectionsDetails,
            'paymentDocument' => $paymentDocument,
        ]);
    }

    /**
     * @param  $quoteTypeCode
     * @param  $quoteId
     * @return void
     */
    public function update($uuid, BikeQuoteRequest $request)
    {
        BikeQuoteRepository::update($uuid, $request->validated());

        return redirect('personal-quotes/bike/'.$uuid)->with('message', 'Quote updated successfully');
    }

    public function bikeAssumptionsUpdate(Request $request)
    {
        $quoteID = BikeQuoteRepository::bikeAssumptionsUpdateProcess($request);

        if ($quoteID) {
            return redirect()->back()->with('success', 'Bike Assumptions has been updated');
        }
    }

    public function manualPlanToggle(Request $request)
    {
        $response = app(BikeQuoteService::class)->updateManualPlansBulk($request);

        if (gettype($response) == GenericRequestEnum::INTEGER && ($response == 200 || $response == 201)) {
            return redirect()->back()->with('success', 'Plan has been updated');
        } else {
            if (isset($response->message)) {
                $responseMessage = $response->message;
            } else {
                $responseMessage = $response;
            }

            return redirect()->back()->with('message', $responseMessage);
        }
    }

    public function sendEmailOneClickBuy(Request $request)
    {
        Log::info('sendEmailOneClickBuy OCB email sending started for quote uuid: '.$request->quote_uuid);

        // get Car quote by uuid using model
        $bikeQuote = PersonalQuote::where('uuid', $request->quote_uuid)->first();

        $previousAdvisor = null;
        if (! empty($bikeQuote->previous_advisor_id)) {
            $previousAdvisor = app(UserService::class)->getUserById($bikeQuote->previous_advisor_id);
        }
        // CHECK NUMBER OF PLAN AND SEND RESPECTIVE 'ONE CLICK BUY' EMAIL TO CUSTOMER
        $listQuotePlans = $this->bikeQuoteService->getPlans($request->quote_uuid, true, true);

        info('sendEmailOneClickBuy OCB email plans fetched for quote uuid: '.$request->quote_uuid);

        $quotePlansCount = is_countable($listQuotePlans) ? count($listQuotePlans) : 0;
        $emailTemplateId = (int) app(CRUDService::class)->getOcbCustomerEmailTemplate($quotePlansCount, quoteTypeCode::Bike);

        $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();

        $listQuotePlans = (is_string($listQuotePlans)) ? [] : $listQuotePlans;

        $emailData = (new BikeEmailService(app(SendEmailCustomerService::class), $bikeQuote))->buildEmailData($bikeQuote, $listQuotePlans, $previousAdvisor, $tierR->id);

        info('sendEmailOneClickBuy OCB email data built for quote uuid: '.$request->quote_uuid);

        $responseCode = app(SendEmailCustomerService::class)->sendRenewalsOcbEmail($emailTemplateId, $emailData, 'bike-quote-one-click-buy');

        if ($responseCode == 201) {
            info('sendEmailOneClickBuy OCB email sent to customer for quote uuid: '.$request->quote_uuid);

            return response()->json(['success' => 'OCB email sent to customer']);
        } else {
            Log::info('sendEmailOneClickBuy OCB email sending failed for quote uuid: '.$request->quote_uuid.' with error code: '.$responseCode);

            return response()->json(['error' => 'OCB email sending failed, please try again. Error Code: '.$responseCode], 500);
        }
    }

    public function bikePlansByInsuranceProvider(Request $request)
    {
        $insuranceProviderId = $request->insuranceProviderId;
        $quoteUuId = $request->quoteUuId;

        $quotePlans = $this->bikeQuoteService->getPlans($quoteUuId);

        $quotePlanId = [];

        foreach ($quotePlans as $key => $quotePlan) {
            if (! isset($quotePlan->id)) {
                continue;
            }

            $quotePlanId[] = $quotePlan->id;
        }

        $bikeNonQuotedPlans = $this->bikeQuoteService->getNonQuotedBikePlans($insuranceProviderId, $quotePlanId);

        return response()->json($bikeNonQuotedPlans);
    }

    public function bikePlanManualProcess(Request $request)
    {
        $response = $this->bikeQuoteService->bikePlanModify($request);

        if ($response == 200 || $response == 201) {
            return redirect()->back()->with('success', 'Bike Quote Plan created successfully');
        } else {
            return redirect()->back()->with('error', 'Plan Modification is not allowed');
        }
    }

    public function bikePlanUpdateManualProcess(Request $request)
    {
        $response = $this->bikeQuoteService->bikePlanModify($request);

        $message = '';
        if (gettype($response) == GenericRequestEnum::INTEGER && ($response == 200 || $response == 201)) {
            $message = 'Plan has been updated';

            return redirect()->back()->with('message', $message);
        } else {
            if (isset($response->message)) {
                $responseMessage = $response->message;
            } else {
                $responseMessage = $response;
            }
            $message = 'Bike Plan has not been updated '.$responseMessage;
        }

        return redirect()->back()->withErrors($message);
    }

    public function changeInsurer(ChangeInsurerRequest $request)
    {
        $response = BikeQuoteRepository::changeInsurer($request->validated());

        return response()->json($response);
    }

    public function getBikeQuote($uuid)
    {
        $quote = BikeQuoteRepository::getBy('uuid', $uuid);

        return response()->json($quote);
    }
}
