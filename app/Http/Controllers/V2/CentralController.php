<?php

namespace App\Http\Controllers\V2;

use App\Exports\LifeQuotesExport;
use App\Exports\PersonalQuotesExport;
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

        if($quoteType == 'life'):
            return Excel::download(new LifeQuotesExport, 'life_leads.xlsx');

        elseif(in_array($quoteType,['bike', 'home', 'pet', 'cycle', 'jetski', 'yacht', 'travel', 'amt', 'business'])):
            return Excel::download(new PersonalQuotesExport, ucfirst($quoteType) . '-Leads.xlsx');

        else:
            return false;
        endif;

    }

    public function manualLeadAssign(LeadAssignRequest $leadAssignRequest)
    {
        (new CentralService())->assignLeadToAdvisor($leadAssignRequest);

        return redirect()->back()->with('success', ucfirst($leadAssignRequest->modelType).' Leads has been Assigned');
    }
}
