<?php

namespace App\Http\Controllers\V2;

use App\Enums\CarPlanAddonsCode;
use App\Enums\CarPlanExclusionsCode;
use App\Enums\CarPlanFeaturesCode;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\CarRevivalQuoteRequest;
use App\Models\User;
use App\Repositories\CarRevivalQuoteRepository;
use App\Services\ActivitiesService;
use App\Services\AMLService;
use App\Services\CarQuoteService;
use App\Services\CRUDService;
use App\Services\CustomerService;
use App\Services\DropdownSourceService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;

class CarRevivalQuoteController extends Controller
{
    use GenericQueriesAllLobs;

    public function __construct() {}

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $formOptionsData = CarRevivalQuoteRepository::getFormOptions();
        $carRevivalQuotes = CarRevivalQuoteRepository::getData();

        return inertia('CarRevivalQuote/Index', [
            'quotes' => $carRevivalQuotes,
            'leadStatuses' => $formOptionsData,
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($uuid)
    {
        $formOptionsData = CarRevivalQuoteRepository::getFormOptions(false);
        $quote = CarRevivalQuoteRepository::getBy('uuid', $uuid);

        return inertia(
            'CarRevivalQuote/Form',
            [
                'form_options' => $formOptionsData,
                'quote' => $quote,
            ]
        );
    }

    public function show($id, Request $request)
    {
        $quoteType = quoteTypeCode::Car;

        $quoteTypeId = app(ActivitiesService::class)->getQuoteTypeId($quoteType);
        $paymentTooltipEnum = PaymentTooltip::asArray();

        $record = app(CRUDService::class)->getEntity($quoteType, $id);
        abort_if(! $record, 404);
        $leadStatuses = app(DropdownSourceService::class)->getDropdownSource('quote_status_id', $quoteTypeId);

        if (AMLService::checkAMLStatusFailed($quoteTypeId, $record->id)) {
            $leadStatuses = collect($leadStatuses)->filter(function ($value) {
                return $value['id'] != QuoteStatusEnum::TransactionApproved;
            })->values();
        }
        $advisors = [];
        $advisors = app(CRUDService::class)->getAdvisorsByModelType($quoteType);

        $documentTypes = app(QuoteDocumentService::class)->getQuoteDocumentsForUpload(QuoteTypeId::Car);

        $lostReasons = app(LookupService::class)->getLostReasons();

        $paymentEntityModel = app(CarQuoteService::class)->getEntityPlain($record->id);
        $payments = $paymentEntityModel->payments;

        $paymentMethods = app(LookupService::class)->getPaymentMethods();

        $listQuotePlans = app(CarQuoteService::class)->getPlans($id);
        $quoteDocuments = app(QuoteDocumentService::class)->getQuoteDocuments($quoteType, $record->id);

        $displaySendPolicyButton = (bool) app(QuoteDocumentService::class)->showSendPolicyButton($record, $quoteDocuments, $quoteTypeId);

        $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
        $ecomCarInsuranceQuoteUrl = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL');

        $activitiesData = app(ActivitiesService::class)->getActivityByLeadId($record->id, strtolower($quoteType));
        $activities = [];
        foreach ($activitiesData as $activity) {
            $updatedActivity = [
                'id' => $activity->id,
                'uuid' => $activity->uuid,
                'title' => $activity->title,
                'description' => $activity->description,
                'quote_request_id' => $activity->quote_request_id,
                'quote_type_id' => $activity->quote_type_id,
                'quote_uuid' => $activity->quote_uuid,
                'client_name' => $activity->client_name,
                'due_date' => $activity->due_date,
                'assignee' => User::where('id', $activity->assignee_id)->first()->name,
                'assignee_id' => $activity->assignee_id,
                'status' => $activity->status,
                'quote_status_id' => $activity->quote_status_id,
                'quote_status' => $activity?->quoteStatus,
            ];
            array_push($activities, $updatedActivity);
        }

        foreach ($payments as $payment) {
            $payment->payment_status_text = $payment->paymentStatus->text;
            $payment->last_payment_status_created_at = $payment->paymentStatusLogs->last() != null ? $payment->paymentStatusLogs->last()->created_at : '';
            $payment->payment_method_name = $payment->paymentMethod->name;
            $payment->insurance_provider_id_text = $payment->insuranceProvider->text;
        }

        $customerAdditionalContacts = app(CustomerService::class)->getAdditionalContacts($record->customer_id, $record->mobile_no);
        $storageUrl = storageUrl();
        $paymentStatusEnum = PaymentStatusEnum::asArray();

        return inertia('CarRevivalQuote/Show', [
            'quote' => $record,
            'leadStatuses' => array_values($leadStatuses->toArray()),
            'advisors' => $advisors,
            'documentTypes' => $documentTypes,
            'lostReasons' => $lostReasons,
            'quoteStatusEnum' => QuoteStatusEnum::asArray(),
            'carPlanFeaturesCode' => CarPlanFeaturesCode::asArray(),
            'carPlanExclusionsCode' => CarPlanExclusionsCode::asArray(),
            'carPlanAddonsCode' => CarPlanAddonsCode::asArray(),
            'quoteType' => quoteTypeCode::Car,
            'can' => [
                'create_payments' => auth()->user()->can(PermissionsEnum::PaymentsCreate) && $paymentEntityModel->plan && ! auth()->user()->hasRole(RolesEnum::PA),
            ],
            'payments' => $payments,
            'quoteRequest' => $paymentEntityModel,
            'paymentMethods' => $paymentMethods,
            'listQuotePlans' => $listQuotePlans,
            'quoteDocuments' => $quoteDocuments,
            'sendPolicy' => (bool) $displaySendPolicyButton,
            'cdnPath' => $cdnPath,
            'activities' => $activities,
            'customerAdditionalContacts' => $customerAdditionalContacts,
            'ecomCarInsuranceQuoteUrl' => $ecomCarInsuranceQuoteUrl,
            'storageUrl' => $storageUrl,
            'paymentStatusEnum' => $paymentStatusEnum,
            'paymentTooltipEnum' => $paymentTooltipEnum,

        ]);
    }

    /**
     * @param  $quoteTypeCode
     * @param  $quoteId
     * @return void
     */
    public function update($uuid, CarRevivalQuoteRequest $carRevivalQuoteRequest)
    {
        CarRevivalQuoteRepository::update($uuid, $carRevivalQuoteRequest->validated());

        return redirect('quotes/revival/'.$uuid)->with('success', 'Quote updated successfully');
    }
}
