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
use App\Services\ManualCommissionUpdateService;
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
            QuoteTypes::LIFE->value,
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

                // for life only
                'commission_based_on_currency' => $bookPolicyRequest?->commission_based_on_currency ?? null,
                'exchange_rate' => $bookPolicyRequest?->exchange_rate ?? null,
                'currency' => $bookPolicyRequest?->currency ?? null,
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
                        $query->where('product_category', EpCategoryEnum::BOLT_ON);
                    })
                    ->with(['product.embeddedProduct:id,short_code'])
                    ->select('code', 'payment_status_id', 'policy_status', 'product_id')
                    ->get();

                if ($captureableEmbeddedTransactions->isNotEmpty()) {
                    try {
                        EmbeddedProductRepository::capturePayment($quote->id, strtolower($quoteType->value));

                        $sukoonMedexCodes = EmbeddedProductEnum::getSukoonMedexCodes();
                        $hasSukoonMedexProducts = $captureableEmbeddedTransactions
                            ->filter(function ($transaction) use ($sukoonMedexCodes) {
                                $epShortCode = $transaction?->product?->embeddedProduct?->short_code;

                                return $epShortCode && in_array($epShortCode, $sukoonMedexCodes);
                            })
                            ->isNotEmpty();

                        // Return response only if EP has any Sukoon MEDEX Product, otherwise proceed to Sage booking
                        if ($hasSukoonMedexProducts) {
                            LoggerService::info('Embedded Product payment is being captured, once done, booking process will begin',
                                extra: $captureableEmbeddedTransactions->toArray()
                            );

                            return response()->json(['message' => 'The embedded product payment is being captured, once done, booking process will begin.'], 200);
                        }

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

    public function checkInsurerReceiptNumber($quoteType, Request $request)
    {
        $validatedRequest = (object) $request->validate([
            'insurer_receipt_number' => 'required|string',
        ]);

        $response = app(CentralService::class)->checkInsurerReceiptNumber($quoteType, $validatedRequest->insurer_receipt_number);

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

    public function updateCommissionForLeads()
    {
        $response = app(ManualCommissionUpdateService::class)->updateCommissionForLeads();

        return $response;
    }
}
