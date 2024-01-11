<?php

namespace App\Http\Controllers\V2;

use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypes;
use App\Exports\AmtQuoteExport;
use App\Exports\BusinessQuoteExport;
use App\Exports\CarQuoteExport;
use App\Exports\HealthQuotesExport;
use App\Exports\HomeQuoteExport;
use App\Exports\LifeQuotesExport;
use App\Exports\PersonalQuotesExport;
use App\Exports\TravelQuoteExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerProfileRequest;
use App\Http\Requests\DuplicateLobRequest;
use App\Http\Requests\LeadAssignRequest;
use App\Http\Requests\PlanDetailsRequest;
use App\Http\Requests\UpdateLastYearPolicyRequest;
use App\Models\Customer;
use App\Models\Entity;
use App\Models\QuoteRequestEntityMapping;
use App\Services\CentralService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class CentralController extends Controller
{
    use GenericQueriesAllLobs;
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

        if (request()->has('created_at')) {
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
        if (in_array(ucfirst($quoteType), [
            QuoteTypes::BIKE->value,
            QuoteTypes::YACHT->value,
            QuoteTypes::PET->value,
            QuoteTypes::CYCLE->value,
            QuoteTypes::JETSKI->value,
        ])) {
            return Excel::download(new PersonalQuotesExport, $quoteType.'_leads.xlsx');
        }

        switch (ucfirst($quoteType)) {
            case QuoteTypes::LIFE->value:
                return Excel::download(new LifeQuotesExport, 'life_leads.xlsx');

            case QuoteTypes::HOME->value:
                return Excel::download(new HomeQuoteExport, 'home_leads.xlsx');

            case QuoteTypes::AMT->value:
                return Excel::download(new AmtQuoteExport, 'amt_leads.xlsx');

            case QuoteTypes::BUSINESS->value:
                return Excel::download(new BusinessQuoteExport, 'business_leads.xlsx');

            case QuoteTypes::TRAVEL->value:
                return Excel::download(new TravelQuoteExport, 'travel_leads.xlsx');

            case QuoteTypes::CAR->value:
                return Excel::download(new CarQuoteExport, 'Car-List.xlsx');

            case QuoteTypes::HEALTH->value:
                return Excel::download(new HealthQuotesExport, 'Health-List.xlsx');

            default:
                return false;
        }
    }

    public function manualLeadAssign(LeadAssignRequest $leadAssignRequest)
    {
        (new CentralService())->assignLeadToAdvisor($leadAssignRequest);

        return redirect()->back()->with('success', ucfirst($leadAssignRequest->modelType).' Leads has been Assigned');
    }

    public function updateCustomerProfileDetails(CustomerProfileRequest $customerProfileRequest)
    {
        if ($customerProfileRequest->customer_type == CustomerTypeEnum::Individual) {
            $customer = Customer::where('id', $customerProfileRequest->customer_id)->firstOrFail();

            $customer->update($customerProfileRequest->only([
                'insured_first_name', 'insured_last_name', 'emirates_id_number', 'emirates_id_expiry_date',
            ]));
        }

        if ($customerProfileRequest->customer_type == CustomerTypeEnum::Entity) {
            $entity = Entity::updateOrCreate(['trade_license_no' => $customerProfileRequest->trade_license_no], $customerProfileRequest->validated());
            $entity->update(['code' => CustomerTypeEnum::EntityShort.'-'.$entity->id]);

            QuoteRequestEntityMapping::updateOrCreate([
                'quote_type_id' => $customerProfileRequest->quote_type_id,
                'quote_request_id' => $customerProfileRequest->quote_request_id,
            ], ['entity_id' => $entity->id, 'entity_type_code' => $customerProfileRequest->entity_type_code]);

        }

        return redirect()->back();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateLastYearPolicy(UpdateLastYearPolicyRequest $request)
    {
        $quote = $this->getQuoteObject($request->model_type, $request->quote_id);

        if (! $quote) {
            return redirect()->back()->with('error', 'Error Updating Policy Details.');
        }

        $quote->update([
            'renewal_batch' => $request->renewal_batch,
        ]);

        return redirect()->back()->with('success', 'Last Year Policy Detail has been updated.');
    }

    public function loadAvailablePlans($type, $id)
    {
        return (new CentralService())->loadAvailablePlans($type, $id);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function savePlanDetails($quoteType, $code, PlanDetailsRequest $request)
    {
        $response = (new CentralService())->savePlanDetails($quoteType, $code, $request->safe());

        return redirect()->back();
    }

    public function updateSelectedPlan($quoteType, $uuid, $planId)
    {
        $response = (new CentralService())->updateSelectedPlan($quoteType, $uuid, $planId);

        if(!empty($response->message)) {
            return redirect()->back()->with('error', $response->message);
        }

        return redirect()->back()->with('success', 'updated successfully');
    }

}
