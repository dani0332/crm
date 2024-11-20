<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\BusinessInsuranceType;
use App\Models\PaymentStatus;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\QuoteTypeRepository;
use App\Services\CentralService;
use App\Services\LookupService;

class SearchController extends Controller
{
    public function index(): \Inertia\Response|\Inertia\ResponseFactory
    {
        $isEndorsementList = (request()->get('list') == 'endorsements');
        $getLeadsOrEndorsements = app(CentralService::class)->getSearchLeads($isEndorsementList);
        $quoteStatuses = app(LookupService::class)->getLeadStatuses([
            QuoteStatusEnum::SentForTransactionApproval,
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyDocumentsPending,
            QuoteStatusEnum::PolicySentToCustomer,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::CancellationPending,
            QuoteStatusEnum::PolicyCancelled,
        ]);
        $paymentStatuses = PaymentStatus::withActive()->get();
        $quoteTypes = QuoteTypeRepository::getList('code');
        $businessInsuranceTypes = BusinessInsuranceType::withActive()->orderBy('text')->get();
        $insuranceProviders = InsuranceProviderRepository::getList('text');

        return inertia('Search/Index', [
            'leadsOrEndorsementData' => $getLeadsOrEndorsements->simplePaginate(15)->withQueryString(),
            'quoteStatuses' => $quoteStatuses,
            'paymentStatuses' => $paymentStatuses,
            'quoteTypes' => $quoteTypes,
            'businessInsuranceTypes' => $businessInsuranceTypes,
            'insuranceProviders' => $insuranceProviders,
        ]);
    }
}
