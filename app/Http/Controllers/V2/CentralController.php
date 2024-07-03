<?php

namespace App\Http\Controllers\V2;

use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
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
use App\Http\Requests\BookPolicyRequest;
use App\Http\Requests\CustomerProfileRequest;
use App\Http\Requests\DragAndDropUpdateLeadStatusRequest;
use App\Http\Requests\DuplicateLobRequest;
use App\Http\Requests\GeneratePaymentLinkRequest;
use App\Http\Requests\LeadAssignRequest;
use App\Http\Requests\MigratePaymentsRequest;
use App\Http\Requests\PlanDetailsRequest;
use App\Http\Requests\QuoteNotesRequest;
use App\Http\Requests\SendBookPolicyRequest;
use App\Http\Requests\SplitPaymentApproveRequest;
use App\Http\Requests\SplitPaymentUpdateRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdateLastYearPolicyRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Requests\UpdateSelectedPlanRequest;
use App\Http\Requests\UpdateTotalPriceRequest;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Models\Customer;
use App\Models\Entity;
use App\Models\HealthQuoteRequestDetail;
use App\Models\Payment;
use App\Models\QuoteNote;
use App\Models\QuoteRequestEntityMapping;
use App\Repositories\PaymentRepository;
use App\Services\ActivitiesService;
use App\Services\CentralService;
use App\Services\NotificationService;
use App\Services\QuoteDocumentService;
use App\Services\SageApiService;
use App\Services\SplitPaymentService;
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

        $quoteIds = explode(',', $leadAssignRequest->selectTmLeadId);
        foreach ($quoteIds as $ids) {
            $quoteData = $this->getQuoteObject($leadAssignRequest->modelType, $ids);
            if ($quoteData) {
                $authorizedPayment = Payment::where('code', '=', $quoteData->code)->first();
                if ($authorizedPayment && $authorizedPayment->payment_status_id === PaymentStatusEnum::AUTHORISED) {
                    $modifiedRequest = new Request([
                        'quoteType' => $leadAssignRequest->modelType,
                        'quoteId' => $ids,
                    ]);
                    app(NotificationService::class)->paymentStatusUpdate($modifiedRequest);
                }
            }

        }

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

    public function updateBookingPolicy(BookPolicyRequest $bookPolicyRequest)
    {
        $validatedData = $bookPolicyRequest->validated();

        $paymentInformation = [
            'insurer_tax_number' => $validatedData['insurer_tax_invoice_number'],
            'transaction_payment_status' => $validatedData['transaction_payment_status'],
            'insurer_commmission_invoice_number' => $validatedData['insurer_commmission_invoice_number'],
            'broker_invoice_number' => $validatedData['broker_invoice_number'],
            'insurer_invoice_date' => $validatedData['invoice_date'],
            'commission_vat_not_applicable' => $validatedData['commission_vat_not_applicable'],
            'commission_vat_applicable' => $validatedData['commission_vat_applicable'],
            'commmission_percentage' => $validatedData['commission_percentage'],
            'commission_vat' => $validatedData['vat_on_commission'],
            'commission' => $validatedData['total_commission'],
            'invoice_description' => $validatedData['invoice_description'],
        ];
        $payment = Payment::where('code', $validatedData['payment_code'])->first();
        if (! $payment) {
            return back()->with('message', 'Payment record not found');
        }
        $payment->update($paymentInformation);
        $quote = $this->getQuoteObject($validatedData['model_type'], $validatedData['quote_id']);
        $quote->update(['policy_booking_date' => Carbon::parse($validatedData['booking_date'])]);

        return redirect()->back()->with('success', 'Booking details has been updated.');
    }

    public function sendBookingPolicy(SendBookPolicyRequest $sendBookPolicyRequest)
    {
        $request = (object) $sendBookPolicyRequest->validated();
        $quote = $this->getQuoteObject($request->model_type, $request->quote_id);

        if ($request->send_policy_type == 'customer') {
            // dispath job to send email
            dispatch(new SendBookPolicyDocumentsJob($request));

            $quote->update([
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
            ]);

            return response()->json(['message' => 'Policy sent to customer'], 200);
        }
        if ($request->send_policy_type == 'sage') {
            if (! auth()->user()->canany([PermissionsEnum::SEND_AND_BOOK_POLICY_BUTTON, PermissionsEnum::BOOK_POLICY_BUTTON])) {
                return response()->json(['errors' => [
                    'message' => 'You are not authorized to perform this action',
                ]], 403);
            }
            $quoteTypeId = app(ActivitiesService::class)->getQuoteTypeId(strtolower($request->model_type));
            $payment = Payment::where('code', $quote['code'])->mainLeadPayment()->with('paymentSplits')->first();
            $paymentSplits = $payment->paymentSplits;
            $data['quoteTypeId'] = $quoteTypeId;
            $data['id'] = $quote->id;

            $sageService = new SageApiService();
            $response = $sageService->postBookPolicyToSage($request, $payment, $quote, $paymentSplits, $data, true, false);

            if ($response['status'] === false) {
                return response()->json(['errors' => [
                    'message' => $response['message'],
                    'sageError' => $response['error'] ? 'SAGE API : '.$response['error'] : null,
                ]], 500);
            }

            if ($quote->quote_status_id != QuoteStatusEnum::PolicySentToCustomer) {
                // dispath job to send email
                dispatch(new SendBookPolicyDocumentsJob($request));
            }

            $quote->update([
                'quote_status_id' => QuoteStatusEnum::PolicyBooked,
            ]);

            (new CentralService())->straightforwardPayments($payment, $paymentSplits, $quote);

            $this->updatePaymentAllocationStatus($quote);

            return response()->json(['message' => $response['message']], 200);
        }
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

    public function updateSelectedPlan(UpdateSelectedPlanRequest $request, $quoteType, $uuid)
    {
        $response = (new CentralService())->updateSelectedPlan($quoteType, $uuid, $request->safe());

        return response()->json(['plan' => $response]);
    }

    // Migrate payments from old system to new system
    public function migratePayment(MigratePaymentsRequest $request)
    {
        $successMessage = PaymentRepository::migratePayments($request);

        return $successMessage;
    }
    // Update split payment status
    public function splitPaymentUpdate(SplitPaymentUpdateRequest $request)
    {
        $successMessage = PaymentRepository::updatePaymentStatus($request);

        return back()->with('success', $successMessage);
    }
    // Approve split payments
    public function splitPaymentsApprove(SplitPaymentApproveRequest $request)
    {
        $successMessage = PaymentRepository::updateSplitPaymentsApprove($request);

        return back()->with('success', $successMessage);
    }

    public function getQuoteWisePlans($quoteType, $providerId, $plandId = null): object
    {
        return response()->json((new CentralService())->getQuoteWiseProviderPlans($quoteType, $providerId, $plandId));
    }

    // Update total price
    public function updateTotalPrice(UpdateTotalPriceRequest $request)
    {
        $successMessage = PaymentRepository::updateTotalPrice($request);

        return $successMessage;
    }

    // Store new payment
    public function storeNewPayment(StorePaymentRequest $request)
    {
        $response = PaymentRepository::createNewPayment($request);
        if ($response['status'] == 'success') {
            return redirect()->back()->with('success', $response['message']);
        } else {
            return redirect()->back()->with('error', $response['message']);
        }
    }

    // Update payment
    public function updateNewPayment(UpdatePaymentRequest $request)
    {
        $response = PaymentRepository::updateNewPayment($request);
        if ($response['status'] == 'success') {
            return redirect()->back()->with('success', $response['message']);
        } else {
            return redirect()->back()->with('error', $response['message']);
        }
    }

    // Generate payment link for split payment
    public function generatePaymentLink(GeneratePaymentLinkRequest $request)
    {
        return (new SplitPaymentService())->generateSplitPaymentLink($request);
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
        $documentIDs = ! empty($quoteNotesRequest->get('old_documents')) ? $quoteNotesRequest->get('old_documents') : [];
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

        $responseMessage = ['Lead status has been updated'];
        $dataFrom = $dragAndDropUpdateLeadStatusRequest->get('data')['form'];
        $dataTo = $dragAndDropUpdateLeadStatusRequest->get('data')['to'];

        $modelObject = $this->getModelObject(QuoteTypes::getName($dataFrom['quoteTypeId'])->value);
        $repository = $modelObject::where('id', $dataFrom['id'])->firstOrFail();

        try {
            DB::beginTransaction();

            if (! $repository->advisor_id) {
                return response()->json(['message' => 'Current Lead has no advisor. Please assign advisor to this Lead'], 200);
            }

            $previousStatusIdChanged = false;
            if ($repository->quote_status_id != (int) $dataTo['quote_status_id']) {
                $previousStatusIdChanged = true;
            }

            $repository->update(['quote_status_id' => $dataTo['quote_status_id'], 'quote_status_date' => now(), 'stale_at' => null]);

            if ($dataTo['quote_status_id'] == QuoteStatusEnum::Lost && $dataFrom['quoteTypeId'] == QuoteTypeId::Health) {
                HealthQuoteRequestDetail::updateOrCreate(['health_quote_request_id' => $repository->id], ['lost_reason_id' => $dragAndDropUpdateLeadStatusRequest->get('data')['to']['lost_reason']]);
            }

            $repository->refresh();

            $activity = (new CentralService())->saveAndAssignActivitesToAdvisor($repository, $dataFrom['quoteTypeId'], $previousStatusIdChanged);

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
