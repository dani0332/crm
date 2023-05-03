<?php

namespace App\Http\Controllers;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
    }

    public function renderAdvisorConversionReport()
    {
        $data = CarQuote::query()
            ->select(
                'users.id as advisorId',
                DB::raw('count(car_quote_request.id) as total_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::NewLead.' THEN 1 ELSE 0 END) as new_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::PriceTooHigh.', '.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.', '.QuoteStatusEnum::NotInterested.', '.QuoteStatusEnum::NotEligibleForInsurance.', '.QuoteStatusEnum::NotLookingForMotorInsurance.', '.QuoteStatusEnum::NonGccSpec.','.QuoteStatusEnum::AMLScreeningFailed.') THEN 1 ELSE 0 END) as not_interested'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::NotContactablePe.', '.QuoteStatusEnum::FollowupCall.', '.QuoteStatusEnum::Interested.', '.QuoteStatusEnum::NoAnswer.', '.QuoteStatusEnum::Quoted.', '.QuoteStatusEnum::PaymentPending.','.QuoteStatusEnum::AMLScreeningCleared.','.QuoteStatusEnum::PendingQuote.') THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') THEN 1 ELSE 0 END) as bad_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id  in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.') THEN 1 ELSE 0 END) as sale_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" and car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.') THEN 1 ELSE 0 END) as created_sale_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::IMRenewal.' THEN 1 ELSE 0 END) as afia_renewals_count'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created_bad_leads'),
            )
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->groupBy('car_quote_request.advisor_id', 'car_quote_request.quote_batch_id')
            ->orderBy('car_quote_request.quote_batch_id')->orderBy('users.email');

        if (! auth()->user()->hasRole(RolesEnum::Admin)) {
            $userIds = $this->walkTree(auth()->user()->id);
            $data = $data->whereIn('car_quote_request.advisor_id', $userIds);
        }

        return inertia('Reports/AdvisorConversion', [
            'data' => $data->get(),
        ]);
    }

    public function renderLeadDistributionReport()
    {
        return view('reports.lead-distribution-report');
    }

    public function renderAdvisorDistributionReport()
    {
        return view('reports.advisor-distribution-report');
    }

    public function renderAdvisorPerformanceReport()
    {
        return view('reports.advisor-performance-report');
    }

    public function renderLeadListReport()
    {
        return view('reports.lead-list-report');
    }
}
