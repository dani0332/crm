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
use App\Models\Activities;
use App\Models\Customer;
use App\Models\Entity;
use App\Models\HealthQuoteRequestDetail;
use App\Models\QuoteNote;
use App\Models\QuoteRequestEntityMapping;
use App\Services\CentralService;
use App\Services\QuoteDocumentService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
            if ($exportTye == GenericRequestEnum::EXPORT_PLAN_DETAIL) {
                $error_fields = 'paid at';

                $request->validate([
                    'paid_at_start' => 'required',
                    'paid_at_end' => 'required',
                ]);
                $created_at_start = Carbon::parse($request->paid_at_start)->format('Y-m-d');
                $created_at_end = Carbon::parse($request->paid_at_end)->format('Y-m-d');
            } else {
                $error_fields = 'created date';

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
            }

            if (ucfirst($quoteType) == QuoteTypes::CAR->value) {
                $diffInDays = 31;
            }

            $diff = Carbon::parse($created_at_start)->diffInDays(Carbon::parse($created_at_end));

            if ($diff > $diffInDays) {
                return back()->with('error', 'Maximum of '.$diffInDays.' days ('.$error_fields.') are allowed to be exported.');
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
            return app(PersonalQuotesExport::class)->download($quoteType.'_leads');
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
                return app(LifeQuotesExport::class)->download('life_leads');

            case QuoteTypes::HOME->value:
                return app(HomeQuoteExport::class)->download('home_leads');

            case QuoteTypes::AMT->value:
                return app(AmtQuoteExport::class)->download('amt_leads');

            case QuoteTypes::BUSINESS->value:
                return app(BusinessQuoteExport::class)->download('business_leads');

            case QuoteTypes::TRAVEL->value:
                return app(TravelQuoteExport::class)->download('travel_leads');

            case QuoteTypes::CAR->value:
                return app(CarQuoteExport::class)->download('Car-List');

            case QuoteTypes::HEALTH->value:
                return app(HealthQuotesExport::class)->download('Health-List');

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

        if ($quoteNotesRequest->hasFile('files')) {
            $quoteDocumentService = new QuoteDocumentService();

            foreach ($quoteNotesRequest->file('files') as $file) {
                $quoteDoument = $quoteDocumentService->uploadQuoteDocument($file, $quoteNotesRequest->all(), $quote);
                $documentIDs[] = $quoteDoument->id;
            }
            $notes->documents()->sync($documentIDs);
        }

        $notes = $quote->notes()->with('createdBy:id,name', 'quoteStatus:id,text', 'documents:doc_name,doc_url,original_name')->where('id', $notes->id)->firstOrFail();

        return response()->json(['response' => $notes]);
    }

    public function updateQuoteNotes(QuoteNotesRequest $quoteNotesRequest)
    {
        $documentIDs = !empty($quoteNotesRequest->get('old_documents')) ? $quoteNotesRequest->get('old_documents') : [];
        $quote = $this->getQuoteObject($quoteNotesRequest->quoteType, $quoteNotesRequest->quoteRequestId);
        $quote->notes()->where('id', $quoteNotesRequest->id)->update(['note' => $quoteNotesRequest->notes, 'updated_by' => auth()->id()]);

        if ($quoteNotesRequest->hasFile('files')) {
            $quoteDocumentService = new QuoteDocumentService();

            foreach ($quoteNotesRequest->file('files') as $file) {
                $quoteDoument = $quoteDocumentService->uploadQuoteDocument($file, $quoteNotesRequest->all(), $quote);
                $documentIDs[] = $quoteDoument->id;
            }
        }

        $note = $quote->notes()->where('id', $quoteNotesRequest->id)->firstOrFail();
        $note->documents()->sync($documentIDs);

        $notes = $quote->notes()->with('createdBy:id,name', 'quoteStatus:id,text', 'documents:doc_name,doc_url,original_name')->where('id', $quoteNotesRequest->id)->firstOrFail();

        return response()->json(['response' => $notes]);
    }

    public function deleteQuoteNotes($id)
    {
        $quoteNote = QuoteNote::where('id', $id)->firstOrFail();
        $quoteNote->documents()->detach();
        $quoteNote->delete();

        return response()->json(['response' => 'Note has been deleted']);
    }

    public function updateLeadStatusDragDrop(DragAndDropUpdateLeadStatusRequest $dragAndDropUpdateLeadStatusRequest)
    {
        try {
            DB::transaction(function () use ($dragAndDropUpdateLeadStatusRequest) {
                $dataFrom = $dragAndDropUpdateLeadStatusRequest->get('data')['form'];
                $dataTo = $dragAndDropUpdateLeadStatusRequest->get('data')['to'];

                $modelObject = $this->getModelObject(QuoteTypes::getName($dataFrom['quoteTypeId'])->value);
                $repository = $modelObject::where('id', $dataFrom['id'])->firstOrFail();

                // Incomplete Activities from current status marked as Done.
                Activities::where([
                    'quote_type_id' => $dataFrom['quoteTypeId'],
                    'quote_request_id' => $dataFrom['id'],
                    'status' => 0, // Incomplete Activities
                ])->update(['status' => 1]);

                if ($dataFrom['quoteTypeId'] == QuoteTypeId::Health && $dataTo['quote_status_id'] == QuoteStatusEnum::Lost) {
                    HealthQuoteRequestDetail::where('health_quote_request_id', $dataFrom['id'])->update([
                        'lost_reason_id' => $dragAndDropUpdateLeadStatusRequest->get('data')['to']['lost_reason'],
                    ]);
                }

                $repository->update([
                    'quote_status_id' => $dataTo['quote_status_id'],
                    'quote_status_date' => now(),
                ]);

            });

            return response()->json(['message' => 'Lead status has been updated']);

        } catch (\Exception $e) {
            info('Update Lead Status Failed. Error: '.$e->getMessage());

            return response()->json(['message' => 'Something went wrong. Please try again later.'], 500);
        }

    }
}
