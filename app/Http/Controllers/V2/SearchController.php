<?php

namespace App\Http\Controllers\V2;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Exports\SearchLeadsEndorsementsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportSearchLeadsOrEndorsementsRequest;
use App\Models\BusinessInsuranceType;
use App\Models\Department;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\PaymentStatusRepository;
use App\Repositories\QuoteTypeRepository;
use App\Services\CentralService;
use App\Services\LookupService;
use App\Services\SearchService;
use App\Services\SendUpdateLogService;
use AWS\CRT\HTTP\Request;

class SearchController extends Controller
{
    public function index(): \Inertia\Response|\Inertia\ResponseFactory
    {
        $isEndorsementList = (request()->get('list') == 'endorsements');
        $getLeadsOrEndorsements = app(SearchService::class)->getSearchLeads($isEndorsementList);
        $getAdvisorsList = app(SearchService::class)->getAdvisorsList();
        $quoteStatuses = app(LookupService::class)->getLeadStatuses([
            QuoteStatusEnum::SentForTransactionApproval,
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyDocumentsPending,
            QuoteStatusEnum::PolicySentToCustomer,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::CancellationPending,
            QuoteStatusEnum::PolicyCancelled,
        ]);
        $paymentStatuses = PaymentStatusRepository::getList([
            PaymentStatusEnum::CAPTURED,
            PaymentStatusEnum::STARTED,
            PaymentStatusEnum::FAILED,
            PaymentStatusEnum::DRAFT,
            PaymentStatusEnum::PARTIAL_CAPTURED,

        ]);
        $quoteTypes = QuoteTypeRepository::getList('code');
        $businessInsuranceTypes = BusinessInsuranceType::withActive()->orderBy('text')->get();
        $insuranceProviders = InsuranceProviderRepository::getList('text');
        $departments = Department::active()->orderBy('name')->get();
        $sendUpdateStatuses = app(SendUpdateLogService::class)->sendUpdateStatuses();
        $sendUpdateTypes = app(LookupService::class)->getSendUpdateCategories();
        $quoteTypeIdEnum = QuoteTypeId::asArray();

        return inertia('Search/Index', [
            'leadsOrEndorsementData' => $getLeadsOrEndorsements,
            'quoteStatuses' => $quoteStatuses,
            'paymentStatuses' => $paymentStatuses,
            'quoteTypes' => $quoteTypes,
            'businessInsuranceTypes' => $businessInsuranceTypes,
            'insuranceProviders' => $insuranceProviders,
            'departments' => $departments,
            'sendUpdateStatuses' => $sendUpdateStatuses,
            'sendUpdateTypes' => $sendUpdateTypes,
            'advisors' => $getAdvisorsList,
            'quoteTypeIdEnum' => $quoteTypeIdEnum,
        ]);
    }

    public function searchExport(ExportSearchLeadsOrEndorsementsRequest $exportSearchLeadsOrEndorsementsRequest): \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $isEndorsementList = $exportSearchLeadsOrEndorsementsRequest->list == 'endorsements';
        $getLeadsOrEndorsements = app(SearchService::class)->getSearchLeads($isEndorsementList, true);
        $exportFileName = 'InsuranceMarket.ae™ '.($isEndorsementList ? 'Send Update' : 'Lead').' List '.now()->format(config('constants.DATE_DISPLAY_FORMAT')).'.xlsx';

        return (new SearchLeadsEndorsementsExport($getLeadsOrEndorsements))->download($exportFileName);
    }
}
