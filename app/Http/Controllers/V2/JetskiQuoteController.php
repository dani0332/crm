<?php

namespace App\Http\Controllers\V2;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
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
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;

class JetskiQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $quotes = JetskiQuoteRepository::getData();
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::JETSKI->id())->get();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::JETSKI->value);

        return inertia('JetskiQuote/Index', [
            'quotes' => $quotes,
            'quoteStatuses' => $quoteStatuses,
            'advisors' => $advisors,
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        $data = JetskiQuoteRepository::getFormOptions();

        return inertia('JetskiQuote/Form', $data);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
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
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($uuid)
    {
        $data = JetskiQuoteRepository::getFormOptions();

        $quote = JetskiQuoteRepository::getBy('uuid', $uuid);

        return inertia(
            'JetskiQuote/Form',
            array_merge($data, [
                'quote' => $quote,
            ])
        );
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($uuid)
    {

        /* Start - Temporarily adding for correcting historic data  */
        $quote = JetskiQuoteRepository::where('uuid', $uuid)->first();
        abort_if(! $quote, 404);
        (new PaymentRepository)->updatePriceVatApplicableAndVat($quote, QuoteTypes::JETSKI->value);
        /* End - Temporarily adding for correcting historic data  */

        $quote = JetskiQuoteRepository::getBy('uuid', $uuid);

        $quote->payments->each->setAppends(['allow', 'copy_link_button', 'edit_button', 'approve_button', 'approved_button']);

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::JETSKI->id())->get();

        $quote->load('documents.createdBy');

        @[$documentTypes, $paymentDocument] = app(QuoteDocumentService::class)->getDocumentTypes(QuoteTypes::JETSKI->id());

        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();

        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::JETSKI->id());
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::JETSKI->value);

        $isAmlClearedForPayment = app(CentralService::class)->amlClearedFromLog($quote->id, QuoteTypes::JETSKI->name);
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

        return inertia('JetskiQuote/Show', [
            'quoteType' => QuoteTypes::JETSKI,
            'quote' => $quote,
            'activities' => $activities,
            'lostReasons' => $lostReasons,
            'advisors' => $advisors,
            'documentTypes' => $documentTypes,
            'quoteStatuses' => $quoteStatuses,
            'paymentMethods' => $paymentMethods,
            'insuranceProviders' => $insuranceProviders,
            'personalPlans' => $personalPlans,
            'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
            'storageUrl' => storageUrl(),
            'embeddedProducts' => $embeddedProducts,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'modelType' => QuoteTypes::JETSKI,
            'quoteTypeId' => QuoteTypes::JETSKI->id(),
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::JetskiManager),
            'vatPercentage' => $vatPercentage,
            'isAmlClearedForPayment' => $isAmlClearedForPayment,
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'sendUpdateEnum' => $sendUpdateEnum,
            'lockLeadSectionsDetails' => $lockLeadSectionsDetails,
            'paymentDocument' => $paymentDocument,
        ]);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($uuid, JetskiQuoteRequest $request)
    {
        JetskiQuoteRepository::update($uuid, $request->validated());

        return redirect('personal-quotes/jetski/'.$uuid)->with('message', 'Quote updated successfully');
    }
}
