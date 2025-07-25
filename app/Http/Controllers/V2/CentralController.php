<?php

namespace App\Http\Controllers\V2;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\EmbeddedProductEnum;
use App\Enums\EpCategoryEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RetentionReportEnum;
use App\Enums\SendPolicyTypeEnum;
use App\Exports\BusinessQuoteExport;
use App\Exports\CarQuoteExport;
use App\Exports\CarQuoteExportWithEmailMobile;
use App\Exports\CarQuoteExportWithMakeModelTrims;
use App\Exports\CarQuoteExportWithPlans;
use App\Exports\GroupMedicalExport;
use App\Exports\HealthQuotesExport;
use App\Exports\LifeQuotesExport;
use App\Exports\NonPUAQuoteExport;
use App\Exports\PersonalQuotesExport;
use App\Exports\PUAQuoteExport;
use App\Exports\PUAUpdatesExport;
use App\Exports\RetentionReportExport;
use App\Exports\RMQuotesExport;
use App\Exports\TravelQuoteExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookPolicyRequest;
use App\Http\Requests\CustomerProfileRequest;
use App\Http\Requests\DeleteSplitPaymentRequest;
use App\Http\Requests\DragAndDropUpdateLeadStatusRequest;
use App\Http\Requests\DuplicateLobRequest;
use App\Http\Requests\ExportValidationRequest;
use App\Http\Requests\GeneratePaymentLinkRequest;
use App\Http\Requests\GetPlansPaymentGatewayRequest;
use App\Http\Requests\LeadAssignRequest;
use App\Http\Requests\MigratePaymentsRequest;
use App\Http\Requests\PaymentCaptureValidtionRequest;
use App\Http\Requests\PlanDetailsRequest;
use App\Http\Requests\PostPrepaymentToSageRequest;
use App\Http\Requests\QuoteNotesRequest;
use App\Http\Requests\RetrySplitPaymentRequest;
use App\Http\Requests\SendBookPolicyRequest;
use App\Http\Requests\SplitPaymentApproveRequest;
use App\Http\Requests\SplitPaymentUpdateRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdateLastYearPolicyRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Requests\UpdateSelectedPlanRequest;
use App\Http\Requests\UpdateTotalPriceRequest;
use App\Jobs\OCAHealthFollowupEmailJob;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Models\AML;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\CcPaymentProcess;
use App\Models\Customer;
use App\Models\CustomerInsured;
use App\Models\EmbeddedTransaction;
use App\Models\Entity;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\Insured;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\QuoteNote;
use App\Models\QuoteRequestEntityMapping;
use App\Models\SendUpdateLog;
use App\Repositories\CarQuoteRepository;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\PaymentRepository;
use App\Services\AMLService;
use App\Services\CentralService;
use App\Services\HealthQuoteService;
use App\Services\Logger\LoggerService;
use App\Services\NotificationService;
use App\Services\QuoteDocumentService;
use App\Services\SageApiService;
use App\Services\SendEmailCustomerService;
use App\Services\SplitPaymentService;
use App\Services\UserService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\BrokerCommissionService;

class CentralController extends Controller
{
    use GenericQueriesAllLobs;

    public function createDuplicate(DuplicateLobRequest $request)
    {
        $response = (new CentralService)->saveDuplicateLeads($request->validated());

        if (! empty($response['errors'])) {
            return redirect()->back()->withErrors($response['errors']);
        }

        return back()->with('message', 'Quote is created successfully.');
    }

    public function exportLeads(ExportValidationRequest $request, $quoteType, $exportTye = null)
    {
        $request->merge(['quoteType' => $quoteType]);

        // For Personal Quotes
        if (in_array(ucfirst($quoteType), [
            QuoteTypes::BIKE->value,
            QuoteTypes::YACHT->value,
            QuoteTypes::PET->value,
            QuoteTypes::CYCLE->value,
            QuoteTypes::JETSKI->value,
            QuoteTypes::SAVINGS->value,
            QuoteTypes::HOME->value,
        ])) {
            if ($request['exportType'] == 'email') {
                return app(PersonalQuotesExport::class, ['quoteType' => $quoteType])->emailCSV($quoteType.'-List', $request->all());
            }

            return app(PersonalQuotesExport::class, ['quoteType' => $quoteType])->download($quoteType.'_leads');
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

                if ($request['exportType'] == 'email') {
                    return app(LifeQuotesExport::class)->emailCSV('Life-List', $request->all());
                }

                return app(LifeQuotesExport::class)->download('life_leads');

            case QuoteTypes::AMT->value:
                if ($request['exportType'] == 'email') {
                    return app(GroupMedicalExport::class)->emailCSV('Group-Medical-List', $request->all());
                }

                return app(GroupMedicalExport::class)->download('group_medical_leads');

            case QuoteTypes::BUSINESS->value:
                if ($request['exportType'] == 'email') {
                    return app(BusinessQuoteExport::class)->emailCSV('Business-List', $request->all());
                }

                return app(BusinessQuoteExport::class)->download('business_leads');

            case QuoteTypes::TRAVEL->value:
                if ($request['exportType'] == 'email') {
                    return app(TravelQuoteExport::class)->emailCSV('Travel-List', $request->all());
                }

                return app(TravelQuoteExport::class)->download('travel_leads');

            case QuoteTypes::CAR->value:
                if ($request['exportType'] == 'email') {
                    return app(CarQuoteExport::class)->emailCSV('Car-List', $request->all());
                }

                return app(CarQuoteExport::class)->download('Car-List');

            case QuoteTypes::HEALTH->value:
                if ($request['exportType'] == 'email') {
                    return app(HealthQuotesExport::class)->emailCSV('Health-List', $request->all());
                }

                return app(HealthQuotesExport::class)->download('Health-List');

            case RetentionReportEnum::RETENTION:
                return app(RetentionReportExport::class)->download('Retention-Report-List');
            default:
                return false;
        }
    }

    public function manualLeadAssign(LeadAssignRequest $leadAssignRequest)
    {
        (new CentralService)->assignLeadToAdvisor($leadAssignRequest);

        $quoteIds = explode(',', $leadAssignRequest->selectTmLeadId);
        foreach ($quoteIds as $id) {
            $quoteData = $this->getQuoteObject($leadAssignRequest->modelType, $id);
            if ($quoteData && $quoteData->payment_status_id === PaymentStatusEnum::AUTHORISED) {
                app(NotificationService::class)->paymentStatusUpdate($leadAssignRequest->modelType, $quoteData->uuid);
            }
        }

        return redirect()->back()->with('success', ucfirst($leadAssignRequest->modelType).' Leads has been Assigned');
    }

    public function updateCustomerProfileDetails(CustomerProfileRequest $customerProfileRequest)
    {
        if ($customerProfileRequest->customer_type == CustomerTypeEnum::Individual) {
            $emiratesDetails = [
                'emirates_id_number' => str_replace('-', '', $customerProfileRequest->emirates_id_number),
                'emirates_id_expiry_date' => $customerProfileRequest->emirates_id_expiry_date,
            ];
            $customer = Customer::where('id', $customerProfileRequest->customer_id)->firstOrFail();
            $customer->update($emiratesDetails);

            $insuredPersonDetails = Insured::updateOrCreate([
                'id_type' => 'emiratesId',
                'id_number' => $customerProfileRequest->emirates_id_number,
            ], [
                'first_name' => $customerProfileRequest->insured_first_name,
                'last_name' => $customerProfileRequest->insured_last_name,
                'dob' => $customer->dob,
                'nationality_id' => $customer->nationality_id,
                'gender' => $customer->screening_gender,
            ]);

            CustomerInsured::updateOrCreate([
                'quote_type_id' => $customerProfileRequest->quote_type_id,
                'quote_request_id' => $customerProfileRequest->quote_request_id,
            ], [
                'customer_id' => $customerProfileRequest->customer_id,
                'insured_id' => $insuredPersonDetails->id,
                'updated_at' => now(),
            ]);
        }

        if ($customerProfileRequest->customer_type == CustomerTypeEnum::Entity) {
            $entity = Entity::updateOrCreate(['trade_license_no' => $customerProfileRequest->trade_license_no], $customerProfileRequest->validated());
            $entity->update(['code' => CustomerTypeEnum::EntityShort.'-'.$entity->id]);

            QuoteRequestEntityMapping::updateOrCreate([
                'quote_type_id' => $customerProfileRequest->quote_type_id,
                'quote_request_id' => $customerProfileRequest->quote_request_id,
            ], ['entity_id' => $entity->id, 'entity_type_code' => $customerProfileRequest->entity_type_code]);

            if ($customerProfileRequest->quote_type_id === QuoteTypeId::Car) {
                CarQuoteRepository::where('id', $customerProfileRequest->quote_request_id)->update([
                    'company_name' => $customerProfileRequest->company_name,
                    'company_address' => $customerProfileRequest->company_address,
                ]);
            }
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

        try {
            LoggerService::info('Quote Code: '.$validatedData['payment_code'].' fn: updateBookingPolicy called');

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

            $quote = $this->getQuoteObject($validatedData['model_type'], $validatedData['quote_id']);

            $isDuplicateOrCIRLead = ! empty($quote->parent_duplicate_quote_id);
            $payment = Payment::where('code', $quote->code)->mainLeadPayment()->first();

            if ($isDuplicateOrCIRLead && empty($payment)) {
                $payment = Payment::where([
                    'paymentable_id' => $quote->id,
                    'paymentable_type' => $quote->getMorphClass(),
                ])->mainLeadPayment()->first();
            }

            $payment->update($paymentInformation);
            LoggerService::info('Quote Code: '.$validatedData['payment_code'].' Book policy details update successfully');

            $response = (new SplitPaymentService)->updateCommissionSchedule($payment);
            if (! $response['status']) {
                return back()->with('error', $response['message']);
            }
            LoggerService::info('Quote Code: '.$validatedData['payment_code'].' Commission Schedule updated successfully');

            return redirect()->back()->with('success', 'Booking details has been updated.');
        } catch (\Exception $e) {
            $paymentCode = $validatedData['payment_code'] ?? '';
            LoggerService::info('Quote Code: '.$paymentCode.' fn: updateBookingPolicy error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    public function sendBookingPolicy(SendBookPolicyRequest $sendBookPolicyRequest)
    {
        $request = (object) $sendBookPolicyRequest->validated();
        $quote = $this->getQuoteObject($request->model_type, $request->quote_id);

        LoggerService::info('Quote Code: '.$quote->code.' fn: sendBookingPolicy called policy type '.$request->send_policy_type);

        if ($request->send_policy_type == SendPolicyTypeEnum::CUSTOMER) {
            SendBookPolicyDocumentsJob::dispatch($request, $quote->code);

            $quoteData = [
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                'quote_status_date' => now(),
            ];
            $quote->update($quoteData);

            LoggerService::info('Quote Code: '.$quote->code.' Policy send to customer');

            return response()->json(['message' => 'Quote status updated to Policy Sent To Customer. Documents are being sent to the customer in background.'], 200);
        }
        if ($request->send_policy_type == SendPolicyTypeEnum::SAGE) {
            if (! auth()->user()->canany([PermissionsEnum::SEND_AND_BOOK_POLICY_BUTTON, PermissionsEnum::BOOK_POLICY_BUTTON])) {
                return response()->json(['errors' => [
                    'message' => 'You are not authorized to perform this action',
                ]], 403);
            }

            $quoteType = QuoteTypes::getNameShortCode($this->getQuoteCodeType($quote) ?? '');
            $quoteTypeId = $quoteType?->id();

            if (in_array($quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Home, QuoteTypeId::Travel])) {

                $captureableEmbeddedTransactions = EmbeddedTransaction::where([
                    ['quote_type_id', $quoteTypeId],
                    ['quote_request_id', $quote->id],
                    ['is_selected', 1],
                    ['payment_status_id', PaymentStatusEnum::AUTHORISED],
                ])
                    ->whereHas('product.embeddedProduct', function ($query) {
                        $query->where('product_category', EpCategoryEnum::BOLT_ON)
                            ->whereIn('short_code', EmbeddedProductEnum::getSukoonMedexCodes() ?? []);
                    })->select('code', 'payment_status_id', 'policy_status')->get();

                if ($captureableEmbeddedTransactions->isNotEmpty()) {
                    try {
                        EmbeddedProductRepository::capturePayment($quote->id, strtolower($quoteType->value));

                        LoggerService::info('Embedded Product payment is being captured, once done, booking process will begin',
                            extra: $captureableEmbeddedTransactions->toArray()
                        );

                        return response()->json(['message' => 'The embedded product payment is being captured, once done, the booking process will begin.'], 200);

                    } catch (Exception $e) {
                        LoggerService::error('Embedded Product payment capture failed', [
                            'error' => $e->getMessage(),
                            'uuid' => $quote->uuid,
                        ]);

                        return response()->json(['errors' => [
                            'message' => 'Embedded Product payment capture failed',
                        ]], 403);
                    }
                }
            }

            $response = (new SageApiService)->postBookPolicyToSage($request, $quote);

            return response()->json(['message' => $response['message']], 200);
        }
    }

    public function loadAvailablePlans($type, $id)
    {
        $getLatestRating = request()->input('getLatestRating', false);

        return (new CentralService)->loadAvailablePlans($type, $id, false, false, $getLatestRating);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function savePlanDetails($quoteType, $code, PlanDetailsRequest $request)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::SELECT_INSURANCE_PROVIDER);
        LoggerService::info("Select plan for Non ECOM lead Quote Type: {$quoteType}, Code: {$request->code}, with Insurance Provider: {$request->provider_code}");

        $response = (new CentralService)->savePlanDetails($quoteType, $code, $request->safe());

        app(AMLService::class)->clearAmlStatusForNonGIG($quoteType, $code, $request->provider_code);

        return redirect()->back();
    }

    public function updateSelectedPlan(UpdateSelectedPlanRequest $request, $quoteType, $uuid)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::SELECT_PLAN);
        LoggerService::info("Select plan for Ecom lead Quote Type: {$quoteType}, Code: {$request->code}, with Insurance Provider: {$request->provider_code}");

        $response = (new CentralService)->updateSelectedPlan($quoteType, $uuid, $request->safe());

        app(AMLService::class)->clearAmlStatusForNonGIG($quoteType, $request->code, $request->provider_code);

        return response()->json(['plan' => $response]);
    }

    // Migrate payments from old system to new system
    public function migratePayment(MigratePaymentsRequest $request)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::MIGRATE_PAYMENT);
        $successMessage = PaymentRepository::migratePayments($request);

        return $successMessage;
    }

    // This method is called when capture/approve split payment
    public function splitPaymentApproveDecline(SplitPaymentUpdateRequest $request)
    {
        $successMessage = PaymentRepository::splitPaymentApproveDecline($request);

        return back()->with('success', $successMessage);
    }

    // This method is called when capture/approve/decline master payment
    public function masterPaymentApproveCapture(SplitPaymentApproveRequest $request)
    {
        LoggerService::info("Master payment approve/capture/decline called for payment code : {$request->payment_code}");

        $successMessage = PaymentRepository::masterPaymentApproveCapture($request);
        if (! $successMessage) {
            return back()->with('error', 'Error in approving payment');
        }

        return back()->with('success', $successMessage);
    }

    public function getQuoteWisePlans($quoteType, $providerId, $plandId = null): object
    {
        return response()->json((new CentralService)->getQuoteWiseProviderPlans($quoteType, $providerId, $plandId));
    }

    // Update total price
    public function updateTotalPrice(UpdateTotalPriceRequest $request)
    {
        $successMessage = PaymentRepository::updateTotalPrice($request);

        return $successMessage;
    }

    // Retry CC split payment
    public function retrySplitPayment(RetrySplitPaymentRequest $request)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::RETRY_SPLIT_PAYMENT);
        $paymentProcessJob = CcPaymentProcess::find($request->payment_process_job_id);
        $splitPayment = $paymentProcessJob->splitPayment;
        LoggerService::info("Retry split payment called & Manual CC Payments Job Started For Payment Split code : {$splitPayment->code} & sr no : {$splitPayment->sr_no}");

        $successMessage = app(SplitPaymentService::class)->processSplitPaymentApprove($paymentProcessJob->quote_type, $paymentProcessJob->quoteable_id, $paymentProcessJob->payment_splits_id, $paymentProcessJob->amount_captured, true);

        if ($successMessage) {
            return redirect()->back()->with('success', 'Payment has been retried');
        } else {
            return redirect()->back()->with('error', 'Payment retry failed');
        }
    }

    // Delete split payment
    public function deleteSplitPayment(DeleteSplitPaymentRequest $request)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::DELETE_SPLIT_PAYMENT);
        LoggerService::info("Delete split payment called Payment Split code : {$request->code}");

        return app(SplitPaymentService::class)->deleteSplitPayment($request->payment_split_id, $request->code);
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
        LoggerService::startFeatureLogging(LoggerFeatureEnum::UPDATE_PAYMENT);
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
        return (new SplitPaymentService)->generateSplitPaymentLink($request);
    }

    public function generateInsurerPaymentLink(GeneratePaymentLinkRequest $request)
    {
        return (new SplitPaymentService)->generateInsurerPaymentLink($request);
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
            $quoteDocumentService = new QuoteDocumentService;

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
            $quoteDocumentService = new QuoteDocumentService;

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

            $repository->update(['quote_status_id' => $dataTo['quote_status_id'], 'quote_status_date' => now()]);

            if ($dataTo['quote_status_id'] == QuoteStatusEnum::Lost && $dataFrom['quoteTypeId'] == QuoteTypeId::Health) {
                HealthQuoteRequestDetail::updateOrCreate(['health_quote_request_id' => $repository->id], ['lost_reason_id' => $dragAndDropUpdateLeadStatusRequest->get('data')['to']['lost_reason']]);
            }

            $repository->refresh();

            $activity = (new CentralService)->saveAndAssignActivitesToAdvisor($repository, $dataFrom['quoteTypeId'], $previousStatusIdChanged);

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

    public function sendOCBEmail(Request $request)
    {
        $healthQuote = HealthQuote::where('uuid', $request->quote_uuid)->first();

        $previousAdvisor = null;
        if (isset($healthQuote) && ! empty($healthQuote->previous_advisor_id)) {
            $previousAdvisor = app(UserService::class)->getUserById($healthQuote->previous_advisor_id);
        }

        // CHECK NUMBER OF PLAN AND SEND RESPECTIVE 'ONE CLICK BUY' EMAIL TO CUSTOMER
        // Fetch all quote plans
        $listQuotePlans = app(HealthQuoteService::class)->getQuotePlans($request->quote_uuid);
        if (! isset($listQuotePlans)) {
            return response()->json(['error' => 'OCB Health Plan Not Found'], 404);
        }
        if (! empty($request->selected_plans) && is_array($request->selected_plans)) {
            if (! isset($listQuotePlans->quote->plans)) {
                $listQuotePlans = 'Plans not available!';
            } else {
                $allPlans = $listQuotePlans->quote->plans;
                if (isset($request->selected_plans) && is_array($request->selected_plans)) {
                    $selectedPlanIds = array_map(function ($plan) {
                        return $plan['id'];
                    }, $request->selected_plans);
                    $filteredQuotePlans = array_filter($allPlans, function ($plan) use ($selectedPlanIds) {
                        return in_array($plan->id, $selectedPlanIds);
                    });

                    $listQuotePlans = array_values($filteredQuotePlans);
                } else {
                    $listQuotePlans = [];
                }
            }
        } else {
            $visiblePlans = array_filter($listQuotePlans->quote->plans, function ($plan) {
                return ! $plan->isHidden;
            });
            shuffle($visiblePlans);
            $randomPlans = array_slice($visiblePlans, 0, 6);
            $listQuotePlans = $randomPlans;
        }

        LoggerService::info('sendHealthEmailOneClickBuy OCB email plans fetched for quote uuid: '.$request->quote_uuid);

        $emailTemplateId = (int) ApplicationStorage::where('key_name', ApplicationStorageEnums::HEALTH_OCB_EMAIL_TEMPLATE)->value('value');

        if (! isset($emailTemplateId)) {
            return response()->json(['error' => 'Invalid email template ID'], 400);
        }
        $listQuotePlans = (is_string($listQuotePlans)) ? [] : $listQuotePlans;

        $emailData = app(SendEmailCustomerService::class)->buildEmailData($healthQuote, $listQuotePlans, $previousAdvisor, $request, $emailTemplateId);

        $responseCode = app(SendEmailCustomerService::class)->sendRenewalsOcbEmail($emailTemplateId, $emailData, 'health-quote-one-click-buy');
        if ($responseCode == 201) {
            if (isset($healthQuote)) {
                $healthQuote->quote_status_id = QuoteStatusEnum::Quoted;
                $healthQuote->quote_status_date = now();
                $healthQuote->save();
                $healthAutoFollowupSwitch = ApplicationStorage::where('key_name', ApplicationStorageEnums::HEALTH_AUTOMATED_FOLLOWUPS_SWITCH)->first();
                // Send Automated Followup Email Job if Health Auto-Followups is enabled.
                if ($healthAutoFollowupSwitch && $healthAutoFollowupSwitch->value == 1) {
                    $delayDays = isLeadSic($healthQuote->uuid) ? 3 : 2;
                    OCAHealthFollowupEmailJob::dispatch($healthQuote->uuid)->delay(Carbon::now()->addDays($delayDays));
                    LoggerService::info('OCAHealthFollowupEmailJob dispatched for HEA-'.$healthQuote->uuid.' - Time: '.now());
                }
            }
            LoggerService::info('sendHealthEmailOneClickBuy - OCB Email Sent & Quote Status Changed to "QUOTED" for quote uuid: '.$request->quote_uuid);

            return response()->json(['success' => 'OCB email sent to customer']);
        } else {
            LoggerService::info('sendHealthEmailOneClickBuy OCB email sending failed for quote uuid: '.$request->quote_uuid.' with error code: '.$responseCode);

            return response()->json(['error' => 'OCB email sending failed, please try again. Error Code: '.$responseCode], 500);
        }
    }
    public function exportRmLeads()
    {
        if (! auth()->user()->can(PermissionsEnum::EXPORT_RM_LEADS)) {
            return response()->json(['message' => 'User Has No Permission to Download RM Leads.'], 403);
        }

        return app(RMQuotesExport::class)->download('RM-Leads-List');
    }
    public function exportPUAUpdates(Request $request)
    {
        if (! auth()->user()->can(PermissionsEnum::EXPORT_CAR_PUA_UPDATES)) {
            return response()->json(['message' => 'User Has No Permission to Download PUA Updates.'], 403);
        }

        $zipFileName = 'PUA-UPDATES.zip';
        $zipFilePath = storage_path('temp/'.$zipFileName);
        $zip = new \ZipArchive;

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->json(['message' => 'Could not create ZIP file.'], 500);
        }

        try {
            $puaUpdateExport = app(PUAQuoteExport::class)->download('PUA-AUTHORIZED.xlsx');
            $nonPuaUpdateExport = app(NonPUAQuoteExport::class)->download('NON-PUA-AUTHORIZED.xlsx');
            $puaUpdatesExport = app(PUAUpdatesExport::class)->download('PUA-UPDATES.xlsx');

            $files = [
                ['path' => $puaUpdateExport->getFile()->getRealPath(), 'name' => 'PUA-AUTHORIZED.xlsx'],
                ['path' => $nonPuaUpdateExport->getFile()->getRealPath(), 'name' => 'NON-PUA-AUTHORIZED.xlsx'],
                ['path' => $puaUpdatesExport->getFile()->getRealPath(), 'name' => 'PUA-UPDATES.xlsx'],
            ];

            foreach ($files as $file) {
                if (file_exists($file['path'])) {
                    $zip->addFile($file['path'], $file['name']);
                } else {
                    LoggerService::info("File does not exist: {$file['path']}");
                }
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error processing exports: '.$e->getMessage()], 500);
        }

        $zip->close();

        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }

    public function voidPayment(Request $request): \Illuminate\Http\JsonResponse
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::VOID_PAYMENT);
        $response = app(CentralService::class)->voidPayment($request);

        return response()->json(['status' => $response['status'], 'message' => $response['message']]);
    }

    public function getInsurerAMLResponse(Request $request)
    {
        $insurerAMLFailureStatus = [
            AMLStatusCode::InsurerAMLScreeningPending,
            AMLStatusCode::InsurerAMLScreeningFailed,
        ];

        $response = ['status' => false, 'message' => ''];
        if (in_array($request->insurerAMLStatus, $insurerAMLFailureStatus)) {
            $responseMessage = 'GIG server connection issue. Please check API logs for details of the error';

            if ($request->insurerAMLStatus == AMLStatusCode::InsurerAMLScreeningFailed) {
                $insurerAMLScreeningResponse = AML::where([
                    'quote_type_id' => $request->quoteType,
                    'quote_request_id' => $request->quoteRequestId,
                    'screening_type' => 'INSURER_'.InsuranceProvidersEnum::AXA,
                ])->latest()->first();

                $amlResponse = ! empty($insurerAMLScreeningResponse) ? json_decode($insurerAMLScreeningResponse->results) : [];

                return ['status' => true, 'message' => $amlResponse?->message ?? $responseMessage];
            }
            $response = ['status' => true, 'message' => $responseMessage];
        }

        return $response;
    }

    public function removeInsurerPaymentLink(Request $request)
    {
        $response = app(CentralService::class)->removeInsurerPaymentLink($request);
        if ($response['status']) {
            return redirect()->back()->with('success', $response['message']);
        }

        return redirect()->back()->with('error', $response['message']);
    }

    public function paymentsCaptureValidtion(PaymentCaptureValidtionRequest $request)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::CAPTURE_PAYMENT_VALIDATION);
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search($request->modelType);
        $response = (new CentralService)->capturePaymentValidation($request->uuid, $quoteTypeId, $request->captureAmount, $request->quoteCode);

        $logContext = [
            'ref_id' => $request->quoteCode,
        ];

        $logExtra = [
            'paymentCode' => $request->paymentCode,
            'quoteTypeId' => $quoteTypeId,
            'responseStatus' => isset($response['status']) ? $response['status'] : null,
            'responseMessage' => isset($response['message']) ? $response['message'] : null,
            'responsePremiumAmount' => isset($response['premiumAmount']) ? $response['premiumAmount'] : null,
        ];

        LoggerService::info('paymentsCaptureValidation', context: $logContext, extra: $logExtra);

        return response()->json(['response' => $response]);
    }

    public function postPrepaymentToSage(PostPrepaymentToSageRequest $postPrepaymentToSageRequest)
    {
        try {
            $request = $postPrepaymentToSageRequest->safe();
            $quote = $this->getQuoteObject($request->quoteType, $request->quoteRequestId);
            $paymentSplit = PaymentSplits::whereId($request->paymentSplitId)->first();
            $sendUpdateLog = null;
            if ($request->sendUpdateId) {
                $sendUpdateLog = SendUpdateLog::whereId(request()->sendUpdateId)->first();
            }

            LoggerService::info(self::class.' fn: '.__FUNCTION__.' Payment Split ID : '.$paymentSplit->id.' - Start Prepayment Posting of Payment split.');

            $schedulePostPrepayment = (new SageApiService)->schedulePostPrepaymentToSageProcess([$quote, $request->quoteType, $paymentSplit, $sendUpdateLog]);

            if (! $schedulePostPrepayment['status']) {
                $errors = count($schedulePostPrepayment['errors']) > 0 ? $schedulePostPrepayment['errors'] : ['message' => $schedulePostPrepayment['message']];
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' Payment Split ID : '.$paymentSplit->id.' -  Start Prepayment Posting of Payment split -  Error : ', extra: ['errors' => $errors]);

                return response()->json(['errors' => $errors], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Prepayment posting to Sage has been scheduled and will be processed in the background.',
            ], 200);
        } catch (Exception $exception) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' Payment Split ID : '.$paymentSplit->id.' - Prepayment Posting of Payment split -  Exception : ', extra: ['Exception' => $exception->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Prepayment posting is failed, please try again later.',
            ], 500);
        }
    }

    public function deletePayment(Request $request): \Illuminate\Http\JsonResponse
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::DELETE_PARENT_PAYMENT);
        $validatedRequest = (object) $request->validate([
            'payment_id' => 'required',
            'payment_code' => 'required',
        ]);
        LoggerService::info("Delete parent payment called for payment code : {$request->payment_code}");

        $response = app(CentralService::class)->deletePayment($validatedRequest);

        return response()->json($response);
    }

    public function getPlansPaymentGateway(GetPlansPaymentGatewayRequest $request, $quoteType, $quoteCcode)
    {
        LoggerService::info('getPlansPaymentGateway called: ', extra: $request->plan_ids, context: ['ref_id' => $quoteCcode]);
        try {
            $result = app(CentralService::class)->getPlansPaymentGateway($request, $quoteType);

            LoggerService::info('getPlansPaymentGateway response: ', extra: $result, context: ['ref_id' => $quoteCcode]);

            return response()->json(['plans' => $result]);
        } catch (\Throwable $th) {
            LoggerService::error('getPlansPaymentGateway error: ', exception: $th, context: ['ref_id' => $quoteCcode]);

            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    private function calculateVatOnCommission($commissionVatApplicable, $vatRate)
    {
        if ($commissionVatApplicable > 0) {
            return roundNumber($commissionVatApplicable * $vatRate);
        }
        return 0;
    }

    private function calculateCommissionPercentage($totalCommissionWithoutVat, $totalPriceWithoutVat, $brokerCommission = null)
    {
        if ($totalCommissionWithoutVat <= 0) {
            return 0;
        }

        $totalCommissionInPercentage = ($totalCommissionWithoutVat / $totalPriceWithoutVat) * 100;
        
        $commissionPercentageMin = 0;
        $commissionPercentageMax = 0;
        $commissionPercentageExceedsLimit = false;
        
        if ($brokerCommission && $brokerCommission->fixed_commission) {
            $commissionPercentageMin = max(($brokerCommission->fixed_commission - 2.5), 0);
            $commissionPercentageMax = $brokerCommission->fixed_commission + 2.5;

            if ($totalCommissionInPercentage < $commissionPercentageMin || 
                $totalCommissionInPercentage > $commissionPercentageMax) {
                $commissionPercentageExceedsLimit = true;
            }
        }

        return [
            'min_percentage' => $commissionPercentageMin,
            'max_percentage' => $commissionPercentageMax,
            'percentage' => roundNumber($totalCommissionInPercentage),
            'exceeds_limit' => $commissionPercentageExceedsLimit
        ];
    }

    private function calculateCommissionDetails($carQuoteDetails, $payment, $brokerCommission)
    {
        $vatRate = ApplicationStorageEnums::VAT;
        $totalPriceWithoutVat = 
            ($carQuoteDetails->price_vat_applicable ?? 0) + 
            ($carQuoteDetails->price_vat_not_applicable ?? 0);

        $totalCommissionWithoutVat = 
            ($payment->commission_vat_not_applicable ?? 0) + 
            ($payment->commission_vat_applicable ?? 0);

        $result = [
            'total_price_without_vat' => $totalPriceWithoutVat,
            'total_commission_without_vat' => $totalCommissionWithoutVat,
            'vat_on_commission' => 0,
            'total_commission' => 0,
            'min_percentage' => 0,
            'max_percentage' => 0,
            'commission_percentage' => 0,
            'commission_percentage_exceeds_limit' => false,
            'error' => null
        ];

        if ($totalCommissionWithoutVat > 0) {
            $result['vat_on_commission'] = $this->calculateVatOnCommission($payment->commission_vat_applicable ?? 0, $vatRate);
            $result['total_commission'] = $totalCommissionWithoutVat + $result['vat_on_commission'];

            if ($totalPriceWithoutVat > 0) {
                $percentageResult = $this->calculateCommissionPercentage(
                    $totalCommissionWithoutVat, 
                    $totalPriceWithoutVat, 
                    $brokerCommission
                );
                $result['min_percentage'] = $percentageResult['min_percentage'] ?? 0;
                $result['max_percentage'] = $percentageResult['max_percentage'] ?? 0;
                $result['commission_percentage'] = $percentageResult['percentage'] ?? 0;
                $result['commission_percentage_exceeds_limit'] = $percentageResult['exceeds_limit'] ?? false;
            } else {
                $result['error'] = 'Total price is zero for this policy';
            }
        }

        return $result;
    }

    public function updateCommissionForLeads()
    {
        $results = [];
        $carQuoteRefIds = [
            "CAR-2FQ3U3GN",
            "CAR-2GFXHRXR",
            "CAR-2GZTJW7H",
            "CAR-2H7JFU3N",
            "CAR-2MM353XV",
            "CAR-2Q4RU3T8",
            "CAR-2QJ2HC7T",
            "CAR-2QPRZHGB",
            "CAR-2T5SREKN",
            "CAR-2TPFH78U",
            "CAR-2YT4T4D8",
            "CAR-38SN44FE",
            "CAR-3G68VKQH",
            "CAR-3RBFJMUW",
            "CAR-3SWUPSYC",
            "CAR-46FWXABF",
            "CAR-49HGBRWJ",
            "CAR-4E3K2KEQ",
            "CAR-4HU6RDTF",
            "CAR-4JGSE9NB",
            "CAR-4N7ACMEB",
            "CAR-4Q67JHQN",
            "CAR-54UC7BAV",
            "CAR-5B3WJRTS",
            "CAR-5HBZMPS6",
            "CAR-5L9DEBMU",
            "CAR-5LC5ZWF8",
            "CAR-5RBLYZYJ",
            "CAR-6EQ56WH9",
            "CAR-6K3YNDBY",
            "CAR-6KLPWUQ2",
            "CAR-6ZSPBKZC",
            "CAR-7KBKSQUX",
            "CAR-7LCYLGCA",
            "CAR-7MXWXFN2",
            "CAR-7WAQ6HJB",
            "CAR-7ZGZXYPQ",
            "CAR-88PXEUHG",
            "CAR-8E2ZQ53U",
            "CAR-8EWPDM5Z",
            "CAR-8F64BBHL",
            "CAR-8JPGDV4W",
            "CAR-8K9ZUDTX",
            "CAR-8NY5NXQL",
            "CAR-8QHR3ZL4",
            "CAR-8Z5A6SJJ",
            "CAR-9C858TXK",
            "CAR-9PTAXUWU",
            "CAR-ABE83Y5A",
            "CAR-ADKYBS5B",
            "CAR-AF6JQE3M",
            "CAR-B5AX5LNP",
            "CAR-BEDNX36X",
            "CAR-BH5SJBFS",
            "CAR-BHDB2D4Q",
            "CAR-BT68HZGE",
            "CAR-BZ5Y5GWB",
            "CAR-CGPX4C5Z",
            "CAR-CHGXFD7R",
            "CAR-CHL4ZM3Q",
            "CAR-CP582T3N",
            "CAR-CWSSWL7Y",
            "CAR-D3AREJFH",
            "CAR-D432DYKG",
            "CAR-DC3KPXXE",
            "CAR-DKQQZQ6X",
            "CAR-DP8YJZPG",
            "CAR-DTLTBUR3",
            "CAR-E3HHFL37",
            "CAR-E6Y9PTE4",
            "CAR-E7Z2VHT5",
            "CAR-E8AX6AP6",
            "CAR-EJACL6EB",
            "CAR-EKMSJ6TV",
            "CAR-ENM86AQ7",
            "CAR-ERJDZMJN",
            "CAR-F5QTHVB5",
            "CAR-FFV4QFJP",
            "CAR-FJBKX7LG",
            "CAR-FYDFR7JH",
            "CAR-G8HHCNV3",
            "CAR-G9UR4ZMC",
            "CAR-GF4WBM6H",
            "CAR-GS4YWG7N",
            "CAR-GS7CHQ5T",
            "CAR-GUKC7FTN",
            "CAR-HCKYAR2Z",
            "CAR-HDGKZ49C",
            "CAR-HMRQC3VU",
            "CAR-HWX28CMM",
            "CAR-J324E5KK",
            "CAR-J4YNT4LC",
            "CAR-J7WTRDFS",
            "CAR-J8SKPNVY",
            "CAR-J9EV4DSB",
            "CAR-JKPATDDH",
            "CAR-JP3B7XNL",
            "CAR-JPHXAQ8U",
            "CAR-JQ7VWA24",
            "CAR-JX8P4G8U",
            "CAR-K5WLYK9B",
            "CAR-K6WLE6W9",
            "CAR-K79EULWJ",
            "CAR-KG52R52B",
            "CAR-KGYZZP69",
            "CAR-KHUCQQU8",
            "CAR-KMJWL8ED",
            "CAR-L3HNBTK8",
            "CAR-L8GT5DPF",
            "CAR-L8KMPRQT",
            "CAR-LDJC9JWJ",
            "CAR-LHX6LJ6K",
            "CAR-LJA4C63R",
            "CAR-LR2RDPBE",
            "CAR-LR84K3UD",
            "CAR-MB5X6ZNZ",
            "CAR-MDWV3FQ4",
            "CAR-MLB83A9U",
            "CAR-N422QH76",
            "CAR-N5M763GR",
            "CAR-NCVRAYZU",
            "CAR-NN9ZYGFG",
            "CAR-NRKN2VSA",
            "CAR-NVEVDKZ2",
            "CAR-NY9GGPJC",
            "CAR-P2CXBS4Z",
            "CAR-P5JUNL2G",
            "CAR-P6M4G3VQ",
            "CAR-PFWRFGPA",
            "CAR-PMAJG2MQ",
            "CAR-PMZDZ2T5",
            "CAR-Q4LBDTQF",
            "CAR-QBJ64NSD",
            "CAR-QE2QENPF",
            "CAR-QL3ZQXCL",
            "CAR-QMEGPUZV",
            "CAR-QPMTX76L",
            "CAR-QRVKEFPP",
            "CAR-QTCAFT9Y",
            "CAR-QYUJEREF",
            "CAR-RB42N4TH",
            "CAR-RBAQMTJ8",
            "CAR-RNWD3942",
            "CAR-RU522N2P",
            "CAR-RUSPYFEZ",
            "CAR-RVH88XSP",
            "CAR-RZK5KMA7",
            "CAR-S3SVPX6E",
            "CAR-S8VZNNSV",
            "CAR-SP5DELZ4",
            "CAR-TH5B3EDK",
            "CAR-TVNXM7TS",
            "CAR-U9D8PG2D",
            "CAR-UAJX2DKP",
            "CAR-UJHKCDTS",
            "CAR-UQEXWSTA",
            "CAR-UWERDGGZ",
            "CAR-V943NKJF",
            "CAR-VEH53THT",
            "CAR-VRRDSDDJ",
            "CAR-VRYWVWFQ",
            "CAR-VSLS7U8C",
            "CAR-VTH68HFR",
            "CAR-W6ZJ4287",
            "CAR-WXZEJSLD",
            "CAR-X7GEWKJL",
            "CAR-XANYV7UJ",
            "CAR-XBXTRZTN",
            "CAR-XCNRZJ98",
            "CAR-XEGLL934",
            "CAR-XENNWVTU",
            "CAR-XJZV4R9Q",
            "CAR-XLQ36AWA",
            "CAR-XLYKSZPG",
            "CAR-XRA52CZH",
            "CAR-XT2R9T2Q",
            "CAR-XVE3N6E8",
            "CAR-YJY27ARF",
            "CAR-YMQ6WR84",
            "CAR-Z4RCPFHK",
            "CAR-Z7P7TTRM",
            "CAR-ZFC538Z9",
            "CAR-ZK6EYHZE",
            "CAR-ZNXQY35P",
            "CAR-ZSYR37KC"
        ];

        foreach ($carQuoteRefIds as $refId) {
            try {
                $carQuoteDetails = CarQuote::where('code', $refId)->first();
                $payment = $carQuoteDetails->payment;
                $insuranceProvider = getInsuranceProvider($payment, QuoteTypes::CAR->value, $carQuoteDetails);
                $insuranceProviderId = $insuranceProvider ? $insuranceProvider->id : null;

                [$isCreditCardEnabled, $brokerCommission, $commissionInPayments] = app(BrokerCommissionService::class)
                    ->fetchBrokerCommission(QuoteTypes::CAR->id(), $insuranceProviderId, null, $carQuoteDetails->plan_id ?? null, $carQuoteDetails);
                
                if ($payment) {
                    $invoiceDescription = app(PaymentRepository::class)->generateInvoiceDescription($payment, QuoteTypes::CAR->value, $carQuoteDetails);
                    app(PaymentRepository::class)->generateAndStoreBrokerInvoiceNumber($carQuoteDetails,$payment, QuoteTypes::CAR->value);
                    $commissionDetails = $this->calculateCommissionDetails($carQuoteDetails, $payment, $brokerCommission);
                    
                    if($commissionDetails['commission_percentage_exceeds_limit']) {
                        $results[$refId] = [
                            'success' => false,
                            'error' => 'Commission percentage exceeds limit',
                            'commission_details' => $commissionDetails
                        ];
                        continue;
                    }

                    $payment->update([
                        'invoice_description' => $invoiceDescription,
                        'commmission_percentage' => $commissionDetails['commission_percentage'],
                        'commission_vat' => $commissionDetails['vat_on_commission'],
                        'commission' => $commissionDetails['total_commission'],
                    ]);

                    $results[$refId] = ['success' => true, 'error' => null, 'commission_details' => $commissionDetails];

                }
            } catch (\Exception $e) {
                LoggerService::info('__class: ' . self::class . ' fn: ' . __FUNCTION__ . ' Error while processing commission for ref_id: ' . $refId, extra: ['error' => $e->getMessage()]);
                $results[$refId] = ['success' => false, 'error' => 'Error processing commission: ' . $e->getMessage(), 'commission_details' => null];
            }
        }

        LoggerService::info('__class: ' . self::class . ' fn: ' . __FUNCTION__ . ' Commission processing completed', context: [
            'total_processed' => count($carQuoteRefIds),
            'successful' => count(array_filter($results, fn($r) => $r['success'])),
            'failed' => count(array_filter($results, fn($r) => !$r['success']))
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Commission processing completed',
            'total_processed' => count($carQuoteRefIds),
            'results' => $results
        ]);
    }
}
