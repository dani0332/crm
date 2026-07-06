<?php

namespace App\Http\Controllers\V2;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\PaymentGatewayIdEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\JetskiQuoteRequest;
use App\Models\ApplicationStorage;
use App\Repositories\ActivityRepository;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\JetskiQuoteRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\PersonalPlanRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Repositories\UserRepository;
use App\Services\AMLService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\Logger\LoggerService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\Reports\RenewalBatchReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;
use Inertia\ResponseFactory;

class JetskiQuoteController extends Controller
{
    /**
     * @return Response|ResponseFactory
     */
    public function index()
    {
        $quotes = JetskiQuoteRepository::getData();
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::JETSKI->id())->get();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::JETSKI->value);
        $authorizedDays = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
        $renewalBatches = app(RenewalBatchReportService::class)->getAllNonMotorBatches();
        $subSources = app(LookupService::class)->getSubSource();

        return inertia('JetskiQuote/Index', [
            'quotes' => $quotes->simplePaginate(10)->withQueryString(),
            'renewalBatches' => $renewalBatches,
            'quoteStatuses' => $quoteStatuses,
            'advisors' => $advisors,
            'authorizedDays' => intval($authorizedDays->value),
            'insurerAMLStatus' => AMLService::getInsurerAMLStatuses(),
            'subSources' => $subSources,
        ]);
    }

    /**
     * @return Response|ResponseFactory
     */
    public function create(Request $request)
    {
        // Log parameters from CreateLeadModal
        LoggerService::info('Jetski create method called with parameters', [
            'type' => $request->input('type'),
            'subSourceId' => $request->input('subSourceId'),
            'subSourceOptionsId' => $request->input('subSourceOptionsId'),
        ]);

        $data = JetskiQuoteRepository::getFormOptions();
        $subSources = app(LookupService::class)->getSubSource();

        $data['subSources'] = $subSources;
        $data['leadSourceParams'] = [
            'type' => $request->input('type'),
            'subSource' => $request->input('subSourceId'),
            'subSourceOption' => $request->input('subSourceOptionsId'),
        ];

        return inertia('JetskiQuote/Form', $data);
    }

    /**
     * @return RedirectResponse
     *
     * @throws ValidationException
     */
    public function store(JetskiQuoteRequest $request)
    {
        $response = JetskiQuoteRepository::create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect('personal-quotes/jetski/'.$response->quoteUID)->with('message', 'Quote created successfully');
    }

    /**
     * @return Response|ResponseFactory
     */
    public function edit($uuid)
    {
        $data = JetskiQuoteRepository::getFormOptions();
        $quote = JetskiQuoteRepository::getBy('uuid', $uuid);
        $subSources = app(LookupService::class)->getSubSource();

        return inertia(
            'JetskiQuote/Form',
            array_merge($data, [
                'quote' => $quote,
                'subSources' => $subSources,
                'leadSourceParams' => [],
            ])
        );
    }

    /**
     * @return Response|ResponseFactory
     */
    public function show($uuid)
    {

        /* Start - Temporarily adding for correcting historic data */
        $quote = JetskiQuoteRepository::where('uuid', $uuid)->first();
        abort_if(! $quote, 404);
        (new PaymentRepository)->updatePriceVatApplicableAndVat($quote, QuoteTypes::JETSKI->value);
        /* End - Temporarily adding for correcting historic data */

        $quote = JetskiQuoteRepository::getBy('uuid', $uuid);
        $quote->load('subSource', 'subSourceOption', 'leadGenerator', 'expertAdvisor');
        $quote->payments->each->setAppends(['allow', 'copy_link_button', 'edit_button', 'approve_button', 'approved_button']);

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::JETSKI->id())->get();
        $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
            return ! in_array($value['id'], [QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::AMLScreeningFailed]);
        })->values();
        if (! auth()->user()->can(PermissionsEnum::UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return ! in_array($value['id'], [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
            })->values();
        }
        $quote->load('documents.createdBy');

        @[$documentTypes, $paymentDocument] = app(QuoteDocumentService::class)->getDocumentTypes(QuoteTypes::JETSKI->id());

        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();

        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::JETSKI->id());
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::JETSKI->value);

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::JETSKI->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee', 'quoteStatus')->orderBy('created_at', 'desc')->get();

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::JETSKI->id(), $quote->id);
        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;

        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = (new LookupService)->getSendUpdateOptions(QuoteTypes::JETSKI->id());
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }
        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($quote);
        $amlStatusName = AMLStatusCode::getName($quote->aml_status);

        return inertia('JetskiQuote/Show', [
            'quoteType' => QuoteTypes::JETSKI,
            'quote' => $quote,
            'amlStatusName' => $amlStatusName,
            'activities' => $activities,
            'lostReasons' => $lostReasons,
            'advisors' => $advisors,
            'documentTypes' => $documentTypes,
            'quoteStatuses' => $quoteStatuses,
            'paymentMethods' => $paymentMethods,
            'insuranceProviders' => $insuranceProviders,
            'personalPlans' => $personalPlans,
            'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
            'embeddedProducts' => $embeddedProducts,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'modelType' => QuoteTypes::JETSKI,
            'quoteTypeId' => QuoteTypes::JETSKI->id(),
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::JetskiManager),
            'vatPercentage' => $vatPercentage,
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'sendUpdateEnum' => $sendUpdateEnum,
            'lockLeadSectionsDetails' => $lockLeadSectionsDetails,
            'paymentDocument' => $paymentDocument,
            'paymentGatewayEnum' => PaymentGatewayIdEnum::asArray(),
            'isFuncsEnabled' => ['tapIntegration' => isTapEnabled()],
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function update($uuid, JetskiQuoteRequest $request)
    {
        JetskiQuoteRepository::update($uuid, $request->validated());

        return redirect('personal-quotes/jetski/'.$uuid)->with('message', 'Quote updated successfully');
    }
}
