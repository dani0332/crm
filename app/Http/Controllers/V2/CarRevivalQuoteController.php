<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\CarQuoteRequest;
use App\Repositories\AdvisorRepository;
use App\Repositories\CarQuoteRepository;
use App\Repositories\CarRevivalQuoteRepository;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\QuoteStatusRepository;

class CarRevivalQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $formOptionsData = CarQuoteRepository::getFormOptions();
        $carRevivalQuotes = CarRevivalQuoteRepository::getData();

        return inertia('CarRevivalQuote/Index', [
            'quotes' => $carRevivalQuotes,
            'leadStatuses' => $formOptionsData,
            'advisors' => []
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($uuid)
    {
        $formOptionsData = CarQuoteRepository::getFormOptions(false);
        $quote = CarQuoteRepository::getBy('uuid', $uuid);

        return inertia('CarRevivalQuote/Form',
            [
                'form_options' => $formOptionsData,
                'quote' => $quote
            ]
        );
    }

    /**
     * @param $quoteTypeCode
     * @param $quoteId
     * @return void
     */
    public function update($uuid, CarQuoteRequest $carQuoteRequest)
    {
        CarQuoteRepository::update(['uuid' => $uuid], $carQuoteRequest->validated());

        return back()->with('message', 'Quote updated successfully');
    }


    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($uuid)
    {
        $quote = CarQuoteRepository::getBy('uuid', $uuid);
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $advisors = AdvisorRepository::getList(quoteTypeCode::Car_Revival);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypeId::Car)->get();
        $documentTypes = DocumentTypeRepository::byQuoteTypeId(QuoteTypeId::Car)->get();

        return inertia('CarRevivalQuote/Show', [
            'quote' => $quote,
            'leadStatuses' => $quoteStatuses,
            'advisors' => $advisors,
            'ecomDetails' => [],
//            'membersDetail' => [],
//            'memberCategories' => [],
//            'salaryBands' => [],
//            'nationalities' => [],
//            'emirates' => [],
            'listQuotePlans' => [],
//            'quoteDocuments' => [],
            'documentTypes' => $documentTypes,
//            'cdnPath' => "",
//            'ecomHealthInsuranceQuoteUrl' => "",
//            'activities' => [],
//            'customerAdditionalContacts' => [],
            'lostReasons' => $lostReasons,
            'quoteStatusEnum' => QuoteStatusEnum::asArray(),
//            'modelType' => "",
            'quoteType' => quoteTypeCode::Car,
//            'notProductionApproval' => true,
            'allowedDuplicateLOB' => [],
//            'permissions' => [],
//            'genderOptions' => $genderOptions,
//            'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
//            'isBetaUser' => true,
//            'payments' => [],
//            'quoteRequest' => [],
            'can' => [
                'approve_payments' => auth()->user()->can(PermissionsEnum::ApprovePayments),
                'edit_payments' => auth()->user()->can(PermissionsEnum::PaymentsEdit),
//                'create_payments' => auth()->user()->can(PermissionsEnum::PaymentsCreate) && $paymentEntityModel->plan && ! auth()->user()->hasRole(RolesEnum::PA),
                'create_payments' => auth()->user()->can(PermissionsEnum::PaymentsCreate) && ! auth()->user()->hasRole(RolesEnum::PA),
                'isPA' => auth()->user()->hasRole(RolesEnum::PA),
                'isAdvisor' => auth()->user()->hasRole(RolesEnum::EBPAdvisor) || auth()->user()->hasRole(RolesEnum::HealthAdvisor) || auth()->user()->hasRole(RolesEnum::RMAdvisor),
            ],
//            'paymentMethods' => [],
//            'sendPolicy' => true,
        ]);
    }

}
