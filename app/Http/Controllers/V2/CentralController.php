<?php

namespace App\Http\Controllers\V2;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RetentionReportEnum;
use App\Enums\SendPolicyTypeEnum;
use App\Exports\AmtQuoteExport;
use App\Exports\BusinessQuoteExport;
use App\Exports\CarQuoteExport;
use App\Exports\CarQuoteExportWithEmailMobile;
use App\Exports\CarQuoteExportWithMakeModelTrims;
use App\Exports\CarQuoteExportWithPlans;
use App\Exports\HealthQuotesExport;
use App\Exports\HomeQuoteExport;
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
use App\Http\Requests\LeadAssignRequest;
use App\Http\Requests\MigratePaymentsRequest;
use App\Http\Requests\PlanDetailsRequest;
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
use App\Models\ApplicationStorage;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\CcPaymentProcess;
use App\Models\Customer;
use App\Models\Entity;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\HomeQuote;
use App\Models\Payment;
use App\Models\QuoteNote;
use App\Models\QuoteRequestEntityMapping;
use App\Repositories\PaymentRepository;
use App\Services\ActivitiesService;
use App\Services\CentralService;
use App\Services\HealthQuoteService;
use App\Services\NotificationService;
use App\Services\QuoteDocumentService;
use App\Services\SageApiService;
use App\Services\SendEmailCustomerService;
use App\Services\SplitPaymentService;
use App\Services\UserService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
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
        try {
            $validatedData = $bookPolicyRequest->validated();
            info('Quote Code: '.$validatedData['payment_code'].' fn: updateBookingPolicy called');

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
            info('Quote Code: '.$validatedData['payment_code'].' Book policy details update successfully');

            $response = (new SplitPaymentService)->updateCommissionSchedule($payment);
            if (! $response['status']) {
                return back()->with('error', $response['message']);
            }
            info('Quote Code: '.$validatedData['payment_code'].' Commission Schedule updated successfully');

            return redirect()->back()->with('success', 'Booking details has been updated.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

    }

    public function sendBookingPolicy(SendBookPolicyRequest $sendBookPolicyRequest)
    {
        $request = (object) $sendBookPolicyRequest->validated();
        $quote = $this->getQuoteObject($request->model_type, $request->quote_id);
        $quoteTypeId = app(ActivitiesService::class)->getQuoteTypeId(strtolower($request->model_type));

        info('Quote Code: '.$quote->code.' fn: sendBookingPolicy called policy type '.$request->send_policy_type);

        if ($request->send_policy_type == SendPolicyTypeEnum::CUSTOMER) {
            SendBookPolicyDocumentsJob::dispatch($request, $quote->code);

            $quoteData = [
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                'quote_status_date' => now(),
            ];
            $quote->update($quoteData);

            info('Quote Code: '.$quote->code.' Policy send to customer');

            return response()->json(['message' => 'Quote status updated to Policy Sent To Customer. Documents are being sent to the customer in background.'], 200);
        }
        if ($request->send_policy_type == SendPolicyTypeEnum::SAGE) {
            if (! auth()->user()->canany([PermissionsEnum::SEND_AND_BOOK_POLICY_BUTTON, PermissionsEnum::BOOK_POLICY_BUTTON])) {
                return response()->json(['errors' => [
                    'message' => 'You are not authorized to perform this action',
                ]], 403);
            }

            $response = (new SageApiService)->postBookPolicyToSage($request, $quote);

            return response()->json(['message' => $response['message']], 200);
        }
    }

    public function loadAvailablePlans($type, $id)
    {
        return (new CentralService)->loadAvailablePlans($type, $id);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function savePlanDetails($quoteType, $code, PlanDetailsRequest $request)
    {
        $response = (new CentralService)->savePlanDetails($quoteType, $code, $request->safe());

        return redirect()->back();
    }

    public function updateSelectedPlan(UpdateSelectedPlanRequest $request, $quoteType, $uuid)
    {
        $response = (new CentralService)->updateSelectedPlan($quoteType, $uuid, $request->safe());

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
        $paymentProcessJob = CcPaymentProcess::find($request->payment_process_job_id);
        info('Manual CC Payments Job Started For Payment Split ID: '.$paymentProcessJob->payment_splits_id);

        $successMessage = app(SplitPaymentService::class)->processSplitPaymentApprove($paymentProcessJob->quote_type, $paymentProcessJob->quoteable_id, $paymentProcessJob->payment_splits_id, $paymentProcessJob->amount_captured, true);

        return $successMessage;
    }

    // Delete split payment
    public function deleteSplitPayment(DeleteSplitPaymentRequest $request)
    {
        return app(SplitPaymentService::class)->deleteSplitPayment($request->payment_split_id);

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
        return (new SplitPaymentService)->generateSplitPaymentLink($request);
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

        info('sendHealthEmailOneClickBuy OCB email plans fetched for quote uuid: '.$request->quote_uuid);

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
                    info('OCAHealthFollowupEmailJob dispatched for HEA-'.$healthQuote->uuid.' - Time: '.now());
                }

            }
            info('sendHealthEmailOneClickBuy - OCB Email Sent & Quote Status Changed to "QUOTED" for quote uuid: '.$request->quote_uuid);

            return response()->json(['success' => 'OCB email sent to customer']);
        } else {
            info('sendHealthEmailOneClickBuy OCB email sending failed for quote uuid: '.$request->quote_uuid.' with error code: '.$responseCode);

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
                    info("File does not exist: {$file['path']}");
                }
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error processing exports: '.$e->getMessage()], 500);
        }

        $zip->close();

        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }

    public function manualReceiptGeneration(Request $request)
    {
        info('Manual Receipt Generation Started for Car Leads');
        $carLeadsCodes = [
            'CAR-UUERY39K',
            'CAR-8D2Z3LGD',
            'CAR-RJ7M4LWY',
            'Car-ELPMZM6L',
            'CAR-X929XQK8',
            'CAR-FDXNL6NL',
            'CAR-GUBKU2YJ',
            'CAR-NE4ATGHJ',
            'CAR-P7EM3LC8',
            'CAR-NXFPCDJV',
            'CAR-27XARWA5',
            'CAR-EHBY8WMU',
            'CAR-65QW3N85',
            'CAR-A9DK9U8A',
            'CAR-96UFTDQT',
            'Car-BFMREYVQ',
            'Car-K7PDQM2C',
            'CAR-SSSMJNHX',
            'Car-86MDQN6G',
            'CAR-D5F5LP4S',
            'CAR-NV7X2EF5',
            'Car-6UVF37RQ',
            'CAR-YYWB84WK',
            'CAR-TZWKBFEY',
            'CAR-3EQ7L7L2',
            'Car-DYUS8X49',
            'Car-2JRLZSFK',
            'CAR-4LLF5R4H',
            'CAR-WNF42JWK',
            'CAR-56JB2HU8',
            'CAR-5Z5799HG',
            'CAR-4XSGTVBL',
            'CAR-LRK35NFC',
            'Car-WFZJ75A7',
            'CAR-2JPYZUB8',
            'CAR-DTX42TWH',
            'CAR-BZX5U2W3',
            'Car-FH4EBXE9',
            'CAR-4VFF9MCA',
            'CAR-M9UXXZTQ',
            'CAR-G78X3FKG',
            'Car-H7PP4YXN',
            'CAR-CEQF6QCD',
            'Car-PR5MTJ3X',
            'Car-AKUHLBCM',
            'CAR-FRRTV889',
            'CAR-RXJ4XKC4',
            'Car-BHQXBB3B',
            'CAR-WPDPNPHL',
            'CAR-2B5YGR5R',
            'CAR-TWFRQUF2',
            'CAR-W3BUENNC',
            'CAR-E3XAT4RN',
            'Car-DQP5AB8B',
            'CAR-5CXWFQCZ',
            'CAR-TCS9KL28',
            'CAR-ADXYTDWJ',
            'CAR-THM3D3AS',
            'Car-WBEHJ7WZ',
            'CAR-NMZNFK22',
            'CAR-5SJHDAYA',
            'CAR-HWDM5WAY',
            'CAR-Z22TSD8C',
            'CAR-VCXGWKAJ',
            'CAR-PSHEN55Y',
            'CAR-XB79SPC7',
            'CAR-THBA5MAZ',
            'CAR-PCEF7EX7',
            'CAR-2ACERA47',
            'CAR-8LA8K8BL',
            'CAR-RS4ALLSB',
            'CAR-SGX5ES4T',
            'CAR-UG3T3XCM',
            'CAR-B42GCGWS',
            'CAR-WRTNLCD3',
            'CAR-LHCXGD5J',
            'CAR-SZPP3ST2',
            'CAR-FTR8FXPM',
            'CAR-W5C4QPRF',
            'CAR-G3VS3E62',
            'CAR-NGKJMJ22',
            'CAR-HVS937SY',
            'CAR-CSJ2AJWW',
            'CAR-ZPJE9AZT',
            'CAR-CJ7XDTS2',
            'Car-Q2VML99M',
            'Car-33H58KCJ',
            'CAR-2WC4N6PW',
            'CAR-DFSZ4KCV',
            'CAR-ZJSELQMP',
            'CAR-J6J6RKHB',
            'CAR-EACW3LTX',
            'CAR-F2GEUQGH',
            'CAR-27D82VW3',
            'CAR-2Y2PFTE3',
            'CAR-S8U7X9NZ',
            'CAR-7TGYCW5F',
            'CAR-QGZG3QCH',
            'CAR-CF5U3VUQ',
            'CAR-QD5V7YPZ',
            'CAR-JYWN834R',
            'CAR-LGXA4G7U',
            'CAR-YUS3NNYP',
            'CAR-SGRZBFTK',
            'CAR-CG73ZU8D',
            'CAR-N7MSVWVY',
            'CAR-DEV92F32',
            'CAR-TR5R6EDJ',
            'CAR-CYS9RENJ',
            'CAR-E3TNBHLM',
            'CAR-RNVBLEN5',
            'CAR-2EJMDJF5',
            'CAR-9L9GFCLA',
            'CAR-6FAEVSH9',
            'CAR-8D9NXWMZ',
            'CAR-F5RUWW3G',
            'CAR-ATC8UGMU',
            'CAR-JTZRYNJF',
            'CAR-JFYRRJDL',
            'CAR-X8WFNDS4',
            'CAR-7TGMWWF7',
            'CAR-35CQBCW2',
            'CAR-LAWTDPYA',
            'CAR-QAKWUNY8',
            'CAR-QY4K8KA9',
            'CAR-22ADWBVJ',
            'CAR-8EY8QUTL',
            'CAR-84QXYVVX',
            'CAR-AUQE7WPG',
            'CAR-GLLP7BBY',
            'CAR-FWWLA9SS',
            'CAR-Q7WQQLWW',
            'CAR-HJVXX54H',
            'CAR-XJ52KNRA',
            'CAR-3Q9C8BUA',
            'CAR-R6M7GDRW',
            'CAR-YWUSA4K7',
            'CAR-WRSN5DB7',
            'CAR-ZCSQYRTE',
            'CAR-HN2CYGBX',
            'CAR-ET73AGWJ',
            'CAR-CKV4GPN2',
            'CAR-65RQ7BNS',
            'CAR-RS56ZM9D',
            'CAR-ZHVMALLS',
            'CAR-83XWYNPF',
            'CAR-VYVSNLT8',
            'CAR-4N7MXXRZ',
            'CAR-86RMB938',
            'CAR-73FRATK5',
            'CAR-6FRSPHNS',
            'CAR-V4MYELF7',
            'CAR-X2D5CDGJ',
            'CAR-UHBMDHM4',
            'CAR-X3YN7DB3',
            'CAR-9K2NH6JH',
            'CAR-C7DTAZU7',
            'CAR-RV5AZBGW',
            'CAR-7TDWWSKH',
            'CAR-WZP4Q692',
            'CAR-K98DJPK7',
            'CAR-JPGUGG6B',
            'CAR-V94VG992',
            'CAR-F897ABVA',
            'CAR-LZVBER87',
            'CAR-DSGV6FHX',
            'CAR-S2PFEFCF',
            'CAR-4YJBCUNM',
            'CAR-HJS5RA6T',
            'CAR-F3EPSEPP',
            'CAR-PW36NPW4',
            'CAR-ZWM65NYG',
            'CAR-AT7X6LDX',
            'CAR-RG8GDT9V',
            'CAR-X6U5LNHW',
            'CAR-2X5W6WSB',
            'CAR-AURZM842',
            'CAR-5LEF2HQW',
            'CAR-H6TC7H75',
            'CAR-2VUFZ2SJ',
            'CAR-VMG3XGPU',
            'CAR-NVWC6MMP',
            'CAR-C4525JX7',
            'CAR-DWBQCEX2',
            'CAR-S5TFF4WB',
            'CAR-CQNUC4L4',
            'CAR-36QGJL6H',
            'CAR-F7FX2BV5',
            'CAR-QSM8KA5D',
            'CAR-J2J8YG9X',
            'CAR-UDHHC7HY',
            'CAR-LVJFSTBT',
            'CAR-X47Q9Y26',
            'CAR-TQT5UK9K',
            'CAR-4KUFA9F2',
            'CAR-FMDBZZAW',
            'CAR-349NYNDE',
            'CAR-5ZTYJND2',
            'CAR-5UCJ8YXB',
            'CAR-VS97EFUS',
            'CAR-JT4JM2JK',
            'CAR-SQHZ86WL',
            'CAR-Q9HMZWB5',
            'CAR-VASD54W7',
            'CAR-R8WL5ZH6',
            'CAR-PDX73UKU',
            'CAR-9ZPDK9MS',
            'CAR-35VUR3RV',
            'CAR-UA49S9HG',
            'CAR-QSHUPWF3',
            'CAR-9SS6YKPQ',
            'CAR-3VVWDEV8',
            'CAR-2UWSLE9Y',
            'CAR-3VPFMZK5',
            'CAR-R9GE254P',
            'CAR-93EX2JV3',
            'CAR-PA2ZMVRC',
            'CAR-BHPBEEDA',
            'CAR-RR3W73W5',
            'CAR-BXFQHMG6',
            'CAR-TXM467CU',
            'CAR-4L5WAF6K',
            'CAR-95AFY95E',
            'CAR-K47XXQSF',
            'CAR-CUEPPFLH',
            'CAR-PMKM2DZG',
            'CAR-NGX2CMKX',
            'CAR-E92QLUPH',
            'CAR-SHDAZBRB',
            'CAR-SERCTAGA',
            'CAR-YWM22FYT',
            'CAR-MGLV75AQ',
            'CAR-HLFMNYBH',
            'CAR-HLFMNYBH'
        ];

        $counter = 0;
        foreach ($carLeadsCodes as $carLeadsCode) {
            $carLeads = CarQuote::where('code', $carLeadsCode)->with('payments.paymentSplits.documents')->first();
            $this->processLeads($carLeads);
            $counter++;
            info("Manual Receipt Generation Processed {$counter} Car Leads");
        }

        $counter = 0;
        info('Manual Receipt Generation Started for Home Leads');
        $homeLeadsCodes = [
            'HOM-FXHZYECK',
            'HOM-8QYZP37G',
            'HOM-SXU7D6ZK',
            'HOM-KF3H6YQB',
            'HOM-36QMJ2GA',
        ];

        foreach ($homeLeadsCodes as $homeLeadsCode) {
            $homeLeads = HomeQuote::where('code', $homeLeadsCode)->with('payments.paymentSplits.documents')->first();
            $this->processLeads($homeLeads);
            $counter++;
            info("Manual Receipt Generation Processed {$counter} Home Leads");
        }

        info('Manual Receipt Generation Started for Business Leads');
        $businessLeadsCodes = [
            'BUS-HASZRWP5',
        ];

        foreach ($businessLeadsCodes as $businessLeadsCode) {
            $businessLead = BusinessQuote::where('code', $businessLeadsCode)->with('payments.paymentSplits.documents')->first();
            $this->processLeads($businessLead);
        }

        info('Manual Receipt Generation for Car Leads Completed');
    }

    private function processLeads($quote)
    {
        if ($quote) {
            info("Processing Quote Code: {$quote->code}");
            $paymentSplits = $quote->payments->first()->paymentSplits;
            foreach ($paymentSplits as $paymentSplit) {
                if (in_array($paymentSplit->payment_status_id, [PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED])) {
                    if (count($paymentSplit->documents) > 0) {
                        info("Document exists for Payment code {$paymentSplit->code}");

                        continue;
                    }
                    app(SplitPaymentService::class)->createReceipt(quoteTypeCode::Car, $quote->id, $paymentSplit);
                    info("Document generated for Payment Code {$paymentSplit->code}");
                }
            }
        }
    }
}
