<?php

namespace App\Http\Controllers\V2;

use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Exports\AmtQuoteExport;
use App\Exports\BusinessQuoteExport;
use App\Exports\CarQuoteExport;
use App\Exports\CarQuoteExportWithEmailMobile;
use App\Exports\CarQuoteExportWithMakeModelTrims;
use App\Exports\CarQuoteExportWithPlans;
use App\Exports\HealthQuotesExport;
use App\Exports\HomeQuoteExport;
use App\Exports\LifeQuotesExport;
use App\Exports\PersonalQuotesExport;
use App\Exports\TravelQuoteExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerProfileRequest;
use App\Http\Requests\DragAndDropUpdateLeadStatusRequest;
use App\Http\Requests\DuplicateLobRequest;
use App\Http\Requests\LeadAssignRequest;
use App\Http\Requests\PlanDetailsRequest;
use App\Http\Requests\QuoteNotesRequest;
use App\Http\Requests\UpdateLastYearPolicyRequest;
use App\Models\Customer;
use App\Models\Entity;
use App\Models\HealthQuoteRequestDetail;
use App\Models\QuoteNote;
use App\Models\QuoteRequestEntityMapping;
use App\Services\CentralService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

    public function exportLeads(Request $request, $quoteType, $exportTye = null)
    {
        $diffInDays = 120;

        if (! $quoteType) {
            return abort(404);
        }

        if ($exportTye != GenericRequestEnum::EXPORT_MAKES_MODELS) {
            $request->validate([
                'created_at_start' => 'required',
                'created_at_end' => 'required',
            ]);
            if (request()->has('created_at')) {
                request()->merge(['created_at_start' => request()->get('created_at')]);
                request()->query->remove('created_at');
            }
            if (ucfirst($quoteType) == QuoteTypes::CAR->value) {
                $diffInDays = 31;
            }

            $created_at_start = Carbon::parse($request->created_at_start)->format('Y-m-d');
            $created_at_end = Carbon::parse($request->created_at_end)->format('Y-m-d');
            $diff = Carbon::parse($created_at_start)->diffInDays(Carbon::parse($created_at_end));
            if ($diff > $diffInDays) {
                return back()->with('error', 'Maximum of '.$diffInDays.' days (created date) are allowed to be exported.');
            }
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

        if (QuoteTypes::CAR->value == ucfirst($quoteType)) {
            if ($exportTye == GenericRequestEnum::EXPORT_PLAN_DETAIL) {
                return app(CarQuoteExportWithPlans::class)->download(ucfirst(GenericRequestEnum::EXPORT_PLAN_DETAIL));
            } elseif ($exportTye == GenericRequestEnum::EXPORT_LEADS_DETAIL_WITH_EMAIL_MOBILE) {
                return app(CarQuoteExportWithEmailMobile::class)->download(ucfirst(GenericRequestEnum::EXPORT_LEADS_DETAIL_WITH_EMAIL_MOBILE));
            } elseif ($exportTye == GenericRequestEnum::EXPORT_MAKES_MODELS) {
                return app(CarQuoteExportWithMakeModelTrims::class)->download(ucfirst(GenericRequestEnum::EXPORT_MAKES_MODELS));
            }
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
                return app(CarQuoteExport::class)->download('Car-List');

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

    public function savePlanDetails($quoteType, $code, PlanDetailsRequest $request)
    {
        $repository = getRepositoryObject($quoteType);

        $quote = $repository::where('code', $code)->firstOrFail();
        $quote->update($request->validated());

        return redirect()->back()->with('success', 'updated successfully');
    }

    public function updateSelectedPlan($quoteType, $uuid, $planId)
    {
        $repository = getRepositoryObject($quoteType);

        $quote = $repository::where('uuid', $uuid)->firstOrFail();

        $quote->update(['prefill_plan_id' => $planId]);

        return redirect()->back()->with('success', 'updated successfully');
    }

    public function saveQuoteNotes(QuoteNotesRequest $quoteNotesRequest)
    {
        $notes = new QuoteNote([
            'quote_status_id' => $quoteNotesRequest->quoteStatusId,
            'note' => $quoteNotesRequest->notes,
            'created_by' => auth()->id(),
        ]);

        $quote = $this->getQuoteObject($quoteNotesRequest->quoteType, $quoteNotesRequest->quoteRequestId);
        $quote->notes()->save($notes);

        return redirect()->back()->with('success', 'Note has been added successfully.');
    }
    
    public function updateLeadStatusDragDrop(DragAndDropUpdateLeadStatusRequest $dragAndDropUpdateLeadStatusRequest)
    {

        $responseMessage = ['Lead status has been updated'];
        $dataFrom = $dragAndDropUpdateLeadStatusRequest->get('data')['form'];
        $dataTo = $dragAndDropUpdateLeadStatusRequest->get('data')['to'];

        $modelObject = $this->getModelObject(QuoteTypes::getName($dataFrom['quoteTypeId'])->value);
        $repository = $modelObject::where('id', $dataFrom['id'])->firstOrFail();

        try {
            DB::beginTransaction();

            $repository->activities()->where('status', 0)->update(['status' => 1]);
            $repository->update(['quote_status_id' => $dataTo['quote_status_id'], 'quote_status_date' => now()]);

            if ($dataTo['quote_status_id'] == QuoteStatusEnum::Lost && $dataFrom['quoteTypeId'] == QuoteTypeId::Health) {
                HealthQuoteRequestDetail::updateOrCreate(['health_quote_request_id' => $repository->id], ['lost_reason_id' => $dragAndDropUpdateLeadStatusRequest->get('data')['to']['lost_reason']]);
            }

            $repository->refresh();

            $activity = (new CentralService())->saveAndAssignActivitesToAdvisor($repository, $dataFrom['quoteTypeId']);
            if ($activity) {
                $responseMessage[] = 'Activity has been created';
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => ['Something went wrong. Please try again later.']], 500);
        }

        return response()->json(['message' => $responseMessage]);

    }
}
