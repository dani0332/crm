<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteTypes;
use App\Exports\AmtQuoteExport;
use App\Exports\BusinessQuoteExport;
use App\Exports\HomeQuoteExport;
use App\Exports\LifeQuotesExport;
use App\Exports\PersonalQuotesExport;
use App\Exports\TravelQuoteExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\DuplicateLobRequest;
use App\Http\Requests\LeadAssignRequest;
use App\Services\CentralService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class CentralController extends Controller
{
    public function createDuplicate(DuplicateLobRequest $request)
    {
        $response = (new CentralService())->saveDuplicateLeads($request->validated());

        if (! empty($response['errors'])) {
            return redirect()->back()->withErrors($response['errors']);
        }

        return back()->with('message', 'Quote is created successfully.');
    }

    public function exportLeads(Request $request, $quoteType)
    {
        if (! $quoteType) {
            return abort(404);
        }

        if(request()->has('created_at')){
            request()->merge(['created_at_start' => request()->get('created_at')]);
            request()->query->remove('created_at');
        }

        $request->validate([
            'created_at_start' => 'required',
            'created_at_end' => 'required',
        ]);

        $created_at_start = Carbon::parse($request->created_at_start)->format('Y-m-d');
        $created_at_end = Carbon::parse($request->created_at_end)->format('Y-m-d');

        $diff = Carbon::parse($created_at_start)->diffInDays(Carbon::parse($created_at_end));

        if ($diff > 120) {
            return back()->with('error', 'Maximum of 120 days (created date) are allowed to be exported.');
        }

        // For Personal Quotes
        if(in_array(ucfirst($quoteType), [
            QuoteTypes::BIKE->value,
            QuoteTypes::YACHT->value,
            QuoteTypes::PET->value,
            QuoteTypes::CYCLE->value,
            QuoteTypes::JETSKI->value
        ])){
            return Excel::download(new PersonalQuotesExport, $quoteType . '_leads.xlsx');
        }

        switch (ucfirst($quoteType)){
            case QuoteTypes::LIFE->value:
                return Excel::download(new LifeQuotesExport, 'life_leads.xlsx');

            case QuoteTypes::HOME->value:
                return Excel::download(new HomeQuoteExport, 'home_leads.xlsx');

            case QuoteTypes::AMT->value;
                return Excel::download(new AmtQuoteExport, 'amt_leads.xlsx');

            case QuoteTypes::BUSINESS->value;
                return Excel::download(new BusinessQuoteExport, 'business_leads.xlsx');

            case QuoteTypes::TRAVEL->value;
                return Excel::download(new TravelQuoteExport, 'travel_leads.xlsx');

            default:
                return false;

        }
    }

    public function manualLeadAssign(LeadAssignRequest $leadAssignRequest)
    {
        (new CentralService())->assignLeadToAdvisor($leadAssignRequest);

        return redirect()->back()->with('success', ucfirst($leadAssignRequest->modelType).' Leads has been Assigned');
    }
}
