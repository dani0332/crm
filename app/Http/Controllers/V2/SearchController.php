<?php

namespace App\Http\Controllers\V2;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Exports\SearchLeadsEndorsementsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportSearchLeadsOrEndorsementsRequest;
use App\Models\BusinessInsuranceType;
use App\Models\Department;
use App\Models\PaymentStatus;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\QuoteTypeRepository;
use App\Repositories\UserRepository;
use App\Services\CentralService;
use App\Services\LookupService;
use App\Services\SearchService;

class SearchController extends Controller
{
    public function index(): \Inertia\Response|\Inertia\ResponseFactory
    {
        $isEndorsementList = (request()->get('list') == 'endorsements');
        $sendUpdateTypes = $sendUpdateStatuses = [];
        $getLeadsOrEndorsements = app(SearchService::class)->getSearchLeads($isEndorsementList);
        $getAdvisorsList = UserRepository::advisorsList();
        $quoteStatuses = app(LookupService::class)->getLeadStatuses([QuoteStatusEnum::SentForTransactionApproval], [QuoteStatusEnum::TransactionDeclined, QuoteStatusEnum::PolicyIssued]);
        $paymentStatuses = PaymentStatus::withActive()->whereNotIn('id', [
            PaymentStatusEnum::CAPTURED,
            PaymentStatusEnum::STARTED,
            PaymentStatusEnum::FAILED,
            PaymentStatusEnum::DRAFT,
            PaymentStatusEnum::PARTIAL_CAPTURED,

        ])->orderBy('sort_order')->get();
        $quoteTypes = QuoteTypeRepository::getList('code');
        $businessInsuranceTypes = BusinessInsuranceType::where('is_active', 1)->orderBy('text')->get();
        $insuranceProviders = InsuranceProviderRepository::getList('text');
        $departments = Department::active()->orderBy('name')->get();
        $quoteTypeIdEnum = QuoteTypeId::asArray();

        if ($isEndorsementList) {
            $sendUpdateTypes = app(LookupService::class)->getSendUpdateCategories();
            $sendUpdateStatuses = SendUpdateLogStatusEnum::sendUpdateStatuses();
        }

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
        app(CentralService::class)->generateExportLogs();
        $exportFileName = 'InsuranceMarket.ae™ '.($isEndorsementList ? 'Send Update' : 'Lead').' List '.now()->format(config('constants.DATE_DISPLAY_FORMAT')).'.xlsx';

        return (new SearchLeadsEndorsementsExport($getLeadsOrEndorsements))->download($exportFileName);
    }
}
