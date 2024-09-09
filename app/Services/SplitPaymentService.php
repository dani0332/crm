<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CollectionTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\DocumentTypeEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentProcessJobEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentStatusTextEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SageEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Factories\SagePayloadFactory;
use App\Models\CarQuote;
use App\Models\CcPaymentProcess;
use App\Models\HealthQuote;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\PersonalQuote;
use App\Models\QuoteDocument;
use App\Models\QuoteStatusLog;
use App\Models\SendUpdateLog;
use App\Models\TravelQuote;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\LookupRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\HandlesDeadlockRetries;
use App\Traits\SageLoggable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;

class SplitPaymentService
{
    use GenericQueriesAllLobs;
    use HandlesDeadlockRetries;
    use SageLoggable;

    public function calculateDiscount($totalSplitPayments, $discountValue)
    {
        $discount = 0;
        if ($totalSplitPayments > 0) {
            $discount = $discountValue / $totalSplitPayments;
        }

        return $discount;
    }

    public function uploadDiscountDocuments($discountDocuments, $code)
    {
        if (isset($discountDocuments) && count($discountDocuments)) {
            $paymentSplitRecord = PaymentSplits::where(['code' => $code])->first();
            foreach ($discountDocuments[0] as $document) {
                $quoteDocumentRec = QuoteDocument::find($document['id']);
                if ($quoteDocumentRec) {
                    $quoteDocumentRec->payment_split_id = $paymentSplitRecord->id;
                    $quoteDocumentRec->save();
                }
            }
        }
    }
    // function to get the payment status of the child payment
    public function getChildPaymentStatus($splitPayment)
    {
        $paymentType = $splitPayment->payment_method;
        $childPaymentStatus = PaymentStatusEnum::NEW;
        if ($paymentType == PaymentMethodsEnum::InsurerPayment) {
            if ($splitPayment->documents()->count() > 0) {
                $childPaymentStatus = PaymentStatusEnum::PENDING;
            }
        } elseif ($paymentType == PaymentMethodsEnum::BankTransfer ||
            $paymentType == PaymentMethodsEnum::Cheque ||
            $paymentType == PaymentMethodsEnum::PostDatedCheque
        ) {
            $childPaymentStatus = PaymentStatusEnum::PENDING;
        } elseif ($paymentType == PaymentMethodsEnum::CreditApproval) {
            $childPaymentStatus = PaymentStatusEnum::CREDIT_APPROVED;
        }

        return $childPaymentStatus;
    }

    public function createSageRecipt($request, $splitPayment, $splitAmount = null)
    {
        if ($splitAmount != null) {
            $request->collection_amount = $splitAmount;
        }

        $returnMessage = ['status' => 'error', 'response' => ''];
        $quote = $this->getQuoteObject($request->modelType, $request->quote_id);
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($request->modelType));
        $customerData = ['quoteTypeId' => $quoteTypeId, 'id' => $quote->id];
        $isAlreadyPosted = false;
        $sageLogArray = $splitPayment->sageApiLogs->keyBy('step')->toArray();

        $sageApiService = new SageApiService;
        $sageCustomerNumber = $sageApiService->verifySageCustomer($request->customer_id, $customerData, $splitPayment, 4, $request->advisor_id);
        if ($sageCustomerNumber == '') {
            info('SAGE API Payments Error: Customer not found in Sage for Payment Code: '.$splitPayment->code);
            $returnMessage['response'] = 'Customer not found in sage';

            return $returnMessage;
        }

        info('SAGE API Payments: Verified Sage customer number: '.$sageCustomerNumber.' for Payment Code: '.$splitPayment->code);
        $request->merge(['sage_customer_number' => $sageCustomerNumber]);

        if ($splitPayment->sr_no == 1) {
            $request->merge(['discount' => $splitPayment->payment->discount_value]);
        }

        $isLiveApiCallStep2 = true;
        if (isset($sageLogArray[2]) && $sageLogArray[2]['status'] == 'success') {
            $isLiveApiCallStep2 = false;
            $sageResponse = json_decode($sageLogArray[2]['response'], true);
        } else {
            $request->merge(['sage_payment_code' => $splitPayment->payment_method]);
            $payLoadOptions = SagePayloadFactory::createPrepaymentPayload($request);
            $message = $sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($message, true);
        }

        if (isset($sageResponse['ReceiptsAdjustments'][0]['DocumentNumber'])) {
            info('SAGE API Payments: Created AR Prepayment Receipts batch '.$sageResponse['BatchNumber'].' for Payment Code: '.$splitPayment->code);
            if ($isLiveApiCallStep2) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $splitPayment, 2, 4, SageEnum::STATUS_SUCCESS, $request->advisor_id);
            }

            $isLiveApiCallStep3 = true;
            $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptArPayment($sageResponse['BatchNumber']);
            $readyToPostResponse = $sageApiService->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');

            if ($readyToPostResponse !== '') {
                $readyToPostArray = json_decode($readyToPostResponse, true);

                if (isset($readyToPostArray['error']['message']['value'])) {
                    info('SAGE API Payments Error: Failed to post AR Prepayment Receipts batch '.$sageResponse['BatchNumber'].' for Payment Code: '.$splitPayment->code.' Error: '.$readyToPostArray['error']['message']['value']);

                    $aRReceiptBatch = $sageApiService->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$sageResponse['BatchNumber'].')', [], 'GET');
                    info('SAGE API Payments: Status of AR Prepayment Receipts batch: '.$aRReceiptBatch);
                    $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                    if ($aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $splitPayment, 3, 4, SageEnum::STATUS_SUCCESS, $request->advisor_id);
                        $isAlreadyPosted = true;
                    } else {
                        info('SAGE API Payments Error: Failed to post AR Prepayment Receipts batch '.$sageResponse['BatchNumber'].' for Payment Code: '.$splitPayment->code);
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $splitPayment, 3, 4, SageEnum::STATUS_FAIL, $request->advisor_id);
                        $returnMessage['response'] = 'Error while making ready to post to sage';

                        return $returnMessage;
                    }
                } else {
                    $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $splitPayment, 3, 4, SageEnum::STATUS_SUCCESS, $request->advisor_id);
                }
            } else {
                if ($isLiveApiCallStep3) {
                    $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $splitPayment, 3, 4, SageEnum::STATUS_SUCCESS, $request->advisor_id);
                }
            }

            $isLiveApiCallStep4 = true;
            $aRPostReceipts = SagePayloadFactory::aRPostReceiptsPayment($sageResponse['BatchNumber']);
            if (isset($sageLogArray[4]) && $sageLogArray[4]['status'] == 'success') {
                $isLiveApiCallStep4 = false;
                $postedResponse = json_decode($sageLogArray[4]['response'], true);
            } else {
                $postedResponse = $sageApiService->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($postedResponse, true);
            }

            if ($isAlreadyPosted && isset($aRPostReceipts)) {
                $this->logSageApiCall($aRPostReceipts, $postedResponse, $splitPayment, 4, 4, SageEnum::STATUS_SUCCESS, $request->advisor_id);
            } else {
                if (isset($postedResponse['error'])) {
                    info('SAGE API Payments Error: Failed to post AR Receipts for batch '.$sageResponse['BatchNumber'].' for Payment Code: '.$splitPayment->payment->code);
                    $returnMessage['response'] = 'Error while posting to sage';
                    $this->logSageApiCall($aRPostReceipts, $postedResponse, $splitPayment, 4, 4, SageEnum::STATUS_FAIL, $request->advisor_id);

                    return $returnMessage;
                } else {
                    if ($isLiveApiCallStep4) {
                        $this->logSageApiCall($aRPostReceipts, $postedResponse, $splitPayment, 4, 4, SageEnum::STATUS_SUCCESS, $request->advisor_id);
                    }
                }
            }

            $documentNumberForReciept = $sageResponse['ReceiptsAdjustments'][0]['DocumentNumber'];
            info('SAGE API Payments: Successfully created and posted receipt for Payment Code: '.$splitPayment->code);
            $returnMessage = ['status' => 'success', 'response' => $documentNumberForReciept];
        } else {
            info('SAGE API Payments Error: Document number not generated from Sage for Payment Code: '.$splitPayment->code);
            $this->logSageApiCall($payLoadOptions, $sageResponse, $splitPayment, 2, 4, SageEnum::STATUS_FAIL, $request->advisor_id);
            $returnMessage['response'] = 'Document number not generated from sage';
        }

        return $returnMessage;
    }

    // functon to check if the payment structure is new
    public function isNewPaymentStructure($payments)
    {
        if ($payments->count() == 0 || $payments[0]->total_payments > 0) {
            return true;
        }

        return false;
    }

    // Migrate payments from old system to new system ,will be called from command/seeder and lead page
    public function migratePayments($payment, $modelType)
    {
        if ($payment) {
            return DB::transaction(function () use ($payment, $modelType) {
                $ecomModels = [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel];
                // Extract the code and check if it has child payments
                $code = $payment->code;
                $quoteModelObject = $this->getModelObject(strtolower($modelType));
                $modelObject = $quoteModelObject::where('code', $code)->first();
                if (! $modelObject) {
                    Log::info('MigratePayment::LOB does not exists for Payment Code: '.$payment->code);

                    return false;
                }
                $premium = 0;
                if ($modelType == quoteTypeCode::Health) {
                    //Get Ecommerce Health Premium
                    $ecomDetail = app(HealthQuoteService::class)->getEcomDetails($modelObject);
                    if (isset($ecomDetail['priceWithVAT'])) {
                        $premium = $ecomDetail['priceWithVAT'];
                    }
                } elseif (isset($modelObject->premium)) {
                    $premium = $modelObject->premium;
                }

                $splitPaymentExists = PaymentSplits::where('code', $code)->count();
                if ($splitPaymentExists > 0) {
                    Log::info('MigratePayment::Split Payment already exists for Payment Code: '.$payment->code);

                    return false;
                }

                // verify master payment exists or not
                $masterPaymentExists = Payment::where('code', $code)->count();
                if (! ($masterPaymentExists > 0)) {
                    Log::info('MigratePayment::Master Payment does not exists for Payment Code: '.$payment->code);

                    return false;
                }

                /*
                $childPayments = Payment::where('code', 'like', "$code%")
                    ->whereNotIn('payment_status_id', [PaymentStatusEnum::DRAFT, PaymentStatusEnum::CANCELLED])
                    ->get();*/
                $childPayments = Payment::where('code', 'like', "$code%")->get();

                if ($childPayments->count() == 0) {
                    Log::info('MigratePayment::All payments are drafted or cancelled for Payment Code: '.$payment->code);

                    return false;
                }
                if ($childPayments->count() > 5) {
                    Log::info('MigratePayment::Child Payments are greater than 5 for Payment Code: '.$payment->code);

                    return false;
                }

                Log::info('MigratePayment::Total Child Payments for Payment Code: '.$payment->code.' are: '.$childPayments->count());

                $parentCollectionAmount = 0;
                $grandTotal = $childPayments->sum('captured_amount');
                $payment->total_payments = $childPayments->count();

                // Create plan detail for non ecommerce lobs
                if ((! in_array(ucfirst($modelType), $ecomModels)) && $childPayments->count() == 1) {
                    Log::info('MigratePayment::Plan Detail migration for Payment Code: '.$payment->code.' Model Type: '.ucfirst($modelType));
                    if (isset($payment->insurance_provider_id) && $payment->insurance_provider_id > 0) {
                        //get vat from settings
                        $vat = 0;
                        $priceVatApplicable = $grandTotal;
                        $vatValue = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::VAT_VALUE);
                        if ($vatValue) {
                            $priceVatApplicable = $priceVatApplicable / (1 + ($vatValue / 100));
                        }

                        if ($modelObject) {
                            $modelObject->price_with_vat = $grandTotal;
                            $modelObject->insurance_provider_id = $payment->insurance_provider_id;
                            $modelObject->price_vat_applicable = $priceVatApplicable;
                            $modelObject->save();
                            Log::info('MigratePayment::Plan Detail updated for Payment Code: '.$payment->code);
                        } else {
                            Log::info('MigratePayment::Plan Detail not found for Payment Code: '.$payment->code);
                        }
                    } else {
                        Log::info('MigratePayment::Insurance Provider not found for Payment Code: '.$payment->code);
                    }
                }
                // Create a new SplitPayment record
                if ($childPayments->count() === 1) {
                    $payment->frequency = 'upfront';
                } else {
                    $payment->frequency = 'split_payments';
                }

                if (in_array(ucfirst($modelType), $ecomModels)) {
                    $payment->total_price = $premium;
                } else {
                    $payment->total_price = $grandTotal;
                }

                $payment->total_amount = $grandTotal;
                $payment->collection_type = 'broker';

                if ($payment->payment_status_id == PaymentStatusEnum::DRAFT) { //draft
                    $payment->payment_status_id = PaymentStatusEnum::NEW; //new
                } elseif (
                    ($payment->payment_status_id == PaymentStatusEnum::CAPTURED || $payment->payment_status_id == PaymentStatusEnum::PARTIAL_CAPTURED)
                    && $premium > 0
                ) {
                    $capturedAmount = 0;
                    foreach ($childPayments as $childPayment) {
                        if ($childPayment->payment_status_id == PaymentStatusEnum::CAPTURED
                        || $childPayment->payment_status_id == PaymentStatusEnum::PARTIAL_CAPTURED
                        ) {
                            $capturedAmount += $childPayment->captured_amount;
                        }
                    }
                    if ($capturedAmount > 0 && $premium > $capturedAmount) {
                        $payment->payment_status_id = PaymentStatusEnum::PARTIALLY_PAID; //partially paid
                    }
                }

                $payment->collection_date = $payment->updated_at;
                $payment->save();

                if ($childPayments->isNotEmpty()) {
                    $payment_sr_no = 1;
                    foreach ($childPayments as $childPayment) {

                        if ($childPayment->payment_status_id == PaymentStatusEnum::DRAFT) { //draft
                            $childPayment->payment_status_id = PaymentStatusEnum::NEW; //new
                        }
                        // Create a new SplitPayment record
                        $collectionAmount = 0;
                        if ($childPayment->payment_status_id == PaymentStatusEnum::PAID || $childPayment->payment_status_id == PaymentStatusEnum::CAPTURED //if paid or captured
                        || $childPayment->payment_status_id == PaymentStatusEnum::PARTIAL_CAPTURED || $childPayment->payment_status_id == PaymentStatusEnum::PARTIALLY_PAID //if partial paid or captured
                        ) {
                            $collectionAmount = $childPayment->captured_amount;
                            $parentCollectionAmount += $childPayment->captured_amount;
                        }

                        PaymentSplits::create([
                            'sr_no' => $payment_sr_no,
                            'code' => $code,
                            'payment_method' => $childPayment->payment_methods_code,
                            'payment_amount' => $childPayment->captured_amount,
                            'due_date' => $childPayment->updated_at,
                            'payment_status_id' => $childPayment->payment_status_id,
                            'collection_amount' => $collectionAmount,
                            'cc_payment_id' => $childPayment->amount,
                            'cc_payment_gateway' => $childPayment->amount,
                            'payment_link' => $childPayment->payment_link,
                            'payment_link_created_at' => $childPayment->payment_link_created_at,
                            'reference' => $childPayment->reference,
                            'authorized_at' => $childPayment->authorized_at,
                            'captured_at' => $childPayment->captured_at,
                            'premium_authorized' => $childPayment->premium_authorized,
                            'premium_captured' => $childPayment->premium_captured,
                            'payment_status_message' => $childPayment->payment_status_message,
                            'payment_gateway_id' => $childPayment->payment_gateway_id,
                            'customer_payment_instrument_id' => $childPayment->customer_payment_instrument_id,
                            'created_at' => $childPayment->created_at,
                            'updated_at' => $childPayment->updated_at,
                        ]);

                        $payment_sr_no++;
                        // Delete the child payment from the old table
                        ////$childPayment->delete();
                    }
                    if ($payment->code == $code) {

                        if ($premium > 0 && $parentCollectionAmount > 0 && $premium > $parentCollectionAmount) {
                            $payment->payment_status_id = PaymentStatusEnum::PARTIALLY_PAID; //partially paid
                        } elseif ($payment->frequency == 'upfront') {
                            $payment->payment_status_id = $childPayment->payment_status_id;
                        }

                        $payment->captured_amount = $parentCollectionAmount;
                        $payment->save();
                    }
                    Log::info('MigratePayment::Payment migrated for Payment Code: '.$payment->code);
                }

                return true;
            });
        } else {
            Log::info('MigratePayment::Payment does not exists for Payment Code: '.$payment->code);

            return false;
        }
    }

    public function createReceipt($modelType, $quoteId, $splitPayment, $send_update_id = null)
    {
        try {
            $quote = $this->getQuoteObject($modelType, $quoteId);
            $quote->load(['customer']);
            $quote->load(['advisor']);

            $data = [];
            $data['order_amount'] = number_format($splitPayment->collection_amount, 2, '.', ',');
            $data['payment_split_id'] = $splitPayment->id;

            if (! empty($quote->first_name)) {
                $data['customer_name'] = $quote->first_name.' '.$quote->last_name;
            } else {
                $data['customer_name'] = $quote->customer->first_name.' '.$quote->customer->last_name;
            }

            // get the advisor details
            $data['advisor_name'] = $quote->advisor->name ?? '';
            $data['advisor_email'] = $quote->advisor->email ?? '';
            $data['advisor_mobile_no'] = $quote->advisor->mobile_no ?? '';
            $data['advisor_landline_no'] = $quote->advisor->landline_no ?? '';
            $data['profile_photo_path'] = $quote->advisor->profile_photo_path ?? '';

            $data['receipt_number'] = $splitPayment->code;
            $data['order_number'] = $splitPayment->code.'-'.$splitPayment->sr_no;
            $data['pdf_filename'] = $splitPayment->code.'-'.$splitPayment->sr_no;
            // get verified at date
            $data['order_at'] = date(config('constants.RECEIPT_ORDER_DATE'), strtotime($splitPayment->verified_at));

            $orderDateFormat = config('constants.DATE_DISPLAY_FORMAT');
            $data['captured_at'] = date($orderDateFormat, strtotime($splitPayment->verified_at));
            if ($splitPayment->captured_at != null) {
                $data['captured_at'] = date($orderDateFormat, strtotime($splitPayment->captured_at));
            }

            if ($modelType == QuoteTypes::BUSINESS->value || $modelType == QuoteTypes::GROUP_MEDICAL->value
            || $modelType == QuoteTypes::HOME->value) {
                $quote->load(['insuranceProviderDetails']);
                $data['insurance_company'] = $quote->insuranceProviderDetails->text;
            } elseif ($modelType == QuoteTypes::CAR->value || $modelType == QuoteTypes::HEALTH->value
            || $modelType == QuoteTypes::TRAVEL->value) {
                $quote->load(['plan']);
                $data['insurance_company'] = $quote->plan->text;
            } else {
                $quote->load(['insuranceProvider']);
                $data['insurance_company'] = $quote->insuranceProvider->text;
            }

            $splitPayment->load(['payment', 'paymentMethod']);
            $data['payment_method'] = $splitPayment->paymentMethod->name;
            $data['remarks'] = $splitPayment->payment->notes;
            $data['vat'] = number_format(0, 2, '.', ',');
            $data['discount'] = number_format(0, 2, '.', ',');

            if ($modelType == QuoteTypes::BUSINESS->value) {
                $quote->load(['businessTypeOfInsurance']);
                $data['type_of_insurance'] = $quote->businessTypeOfInsurance->text;
            } else {
                $data['type_of_insurance'] = $modelType.' Insurance';
            }

            $documentType = DocumentTypeCode::CPD_RECEIPT; // default car
            if ($modelType == QuoteTypes::HOME->value) {
                $documentType = DocumentTypeCode::HOMPD_RECEIPT;
            } elseif ($modelType == QuoteTypes::HEALTH->value) {
                $documentType = DocumentTypeCode::HPD_RECEIPT;
            } elseif ($modelType == QuoteTypes::LIFE->value) {
                $documentType = DocumentTypeCode::LPD_RECEIPT;
            } elseif ($modelType == QuoteTypes::BUSINESS->value) {
                $documentType = DocumentTypeCode::CLPD_RECEIPT;
            } elseif ($modelType == QuoteTypes::BIKE->value) {
                $documentType = DocumentTypeCode::BPD_RECEIPT;
            } elseif ($modelType == QuoteTypes::YACHT->value) {
                $documentType = DocumentTypeCode::YPD_RECEIPT;
            } elseif ($modelType == QuoteTypes::TRAVEL->value) {
                $documentType = DocumentTypeCode::TPD_RECEIPT;
            } elseif ($modelType == QuoteTypes::PET->value) {
                $documentType = DocumentTypeCode::PPD_RECEIPT;
            } elseif ($modelType == QuoteTypes::CYCLE->value) {
                $documentType = DocumentTypeCode::CYCPD_RECEIPT;
            } elseif ($modelType == QuoteTypes::GROUP_MEDICAL->value) {
                $documentType = DocumentTypeCode::GMQPD_RECEIPT;
            }

            $data['document_type_code'] = $documentType;
            $data['quote_uuid'] = $quote->uuid;
            if ($send_update_id) {
                $quote = SendUpdateLog::find($send_update_id);
            }

            $pdf = PDF::loadView('pdf.payment_receipt', compact('data'))->setOptions(['defaultFont' => 'DejaVu Sans']);
            $pdf->setPaper('A4');
            $pdfFile = $pdf->output();
            $document = app(QuoteDocumentService::class)->uploadQuoteDocument($pdfFile, $data, $quote, false, true);
        } catch (\Exception $ex) {
            info('Payment Reciept - ERROR:'.$ex->getMessage());
        }

    }

    public function generateSplitPaymentLink($request)
    {
        $splitPayment = PaymentSplits::where(['code' => $request->paymentCode, 'sr_no' => $request->splitPaymentId])->first();
        $payment = $splitPayment->payment;
        $modelType = $request->modelType;
        $quoteId = $request->quoteId;

        if (! $payment) {
            return response()->json(['success' => false]);
        }

        if ($splitPayment->payment_link != null && now() < Carbon::parse($splitPayment->payment_link_created_at)->addDays(3)) {
            return response()->json(['success' => true, 'payment_link' => $splitPayment->payment_link]);
        } else {

            $quoteModel = $this->getQuoteObject($modelType, $quoteId);
            $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));

            $description = (get_class($quoteModel) == PersonalQuote::class) ? ($payment->personalPlan->text ?? '') : ($quoteModel->plan->text ?? '');

            $paymentLink = config('constants.PAYMENT_REDIRECT_LINK');

            $paymentLink = $splitPayment->payment_method == PaymentMethodsEnum::InsureNowPayLater ? $paymentLink.'tabby' : $paymentLink.'checkout';

            $paymentParams = [
                'code' => $payment->code.'-'.$splitPayment->sr_no,
                'quoteTypeId' => $quoteTypeId,
            ];
            $paymentLinkURL = $paymentLink.'?'.http_build_query($paymentParams);

            $invoiceRequestData = [
                'firstName' => $quoteModel->first_name,
                'lastName' => $quoteModel->last_name,
                'email' => $quoteModel->email,
                'emailSubject' => 'Payment Request',
                'items' => [
                    [
                        'description' => $description,
                        'totalPrice' => [
                            'currencyCode' => 'AED',
                            'value' => ceil($splitPayment->payment_amount * 100),
                        ],
                        'quantity' => 1,
                    ],
                ],
                'total' => [
                    'currencyCode' => 'AED',
                    'value' => ceil($splitPayment->payment_amount * 100),
                ],
                'merchantOrderReference' => strtoupper($payment->code.'-'.$splitPayment->sr_no),
            ];

            info('Request object for '.$quoteModel->uuid.' is '.json_encode($invoiceRequestData));

            return response()->json(['success' => true, 'payment_link' => $paymentLinkURL]);

        }
    }
    // function to get the payment lookups
    public function getPaymentLookups()
    {
        $paymentLookups = [
            'paymentCollectionTypes' => LookupRepository::where('key', LookupsEnum::PAYMENT_COLLECTION_TYPE)->get(),
            'paymentFrequencyTypes' => LookupRepository::where('key', LookupsEnum::PAYMENT_FREQUENCY_TYPE)->get(),
            'paymentDeclineReasons' => LookupRepository::where('key', LookupsEnum::PAYMENT_DECLINE_REASON)->get(),
            'paymentCreditApprovalReasons' => LookupRepository::where('key', LookupsEnum::PAYMENT_CREDIT_APPROVAL_REASON)->get(),
            'paymentDispountTypes' => LookupRepository::where('key', LookupsEnum::PAYMENT_DISCOUNT_TYPE)->get(),
            'paymentDiscountReasons' => LookupRepository::where('key', LookupsEnum::PAYMENT_DISCOUNT_REASON)->get(),
        ];

        return $paymentLookups;
    }

    // function to map payment status text to quote payment status text
    public function mapQuotePaymentStatus($quotePaymentStatusId, $quotePaymentStatusText)
    {
        $paymentStatusText = $quotePaymentStatusText;
        if ($quotePaymentStatusId == PaymentStatusEnum::CAPTURED) {
            $paymentStatusText = PaymentStatusTextEnum::PAID_TEXT;
        } elseif ($quotePaymentStatusId == PaymentStatusEnum::DRAFT) {
            $paymentStatusText = PaymentStatusTextEnum::NEW_TEXT;
        } elseif ($quotePaymentStatusId == PaymentStatusEnum::PARTIAL_CAPTURED) {
            $paymentStatusText = PaymentStatusTextEnum::PARTIALLY_PAID_TEXT;
        }

        return $paymentStatusText;
    }

    // function to process the split payment approve
    public function processSplitPaymentApprove($modelType, $quoteId, $splitPaymentId, $amountCollected, $isFromJob = false)
    {
        $paymentSplit = PaymentSplits::find($splitPaymentId);
        info('Processing split payment approval for Payment Split ID: '.$splitPaymentId.' and Code: '.$paymentSplit->code);

        $sendUpdateId = $paymentSplit->payment->send_update_log_id;
        $mainLeadObject = $this->getQuoteObject($modelType, $quoteId);
        $maxRetries = 2;

        if (! empty($sendUpdateId) && $sendUpdateId > 0) {
            $quoteModel = SendUpdateLogRepository::getLogById($sendUpdateId);
            $quoteModel->fill([
                'customer_id' => $mainLeadObject->customer_id,
                'advisor_id' => $mainLeadObject->advisor_id,
            ]);
        } else {
            $quoteModel = $mainLeadObject;
        }

        if ($isFromJob && ! $quoteModel) {
            CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::FAILED, 'message' => PaymentProcessJobEnum::QUOTE_NOTFOUND_MESSAGE]);

            return;
        }

        if ($paymentSplit->payment_method == PaymentMethodsEnum::CreditCard) {
            // Log message for creating Sage receipt
            info('Creating Sage receipt for Payment Split ID: '.$splitPaymentId.' and Code: '.$paymentSplit->code);

            $isSageEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::SAGE_ENABLED);
            if ($isSageEnabled && ($paymentSplit->sage_reciept_id == null || $paymentSplit->sage_reciept_id == '')) {
                // Create an empty Request object
                $request = Request::createFromGlobals();
                $request->merge([
                    'modelType' => $modelType,
                    'quote_id' => $quoteId,
                    'customer_id' => $quoteModel->customer_id,
                    'advisor_id' => $quoteModel->advisor_id,
                ]);

                $sageResponse = $this->createSageRecipt($request, $paymentSplit, $amountCollected);
                if ($sageResponse['status'] == 'success') {
                    info('Sage receipt created successfully for Payment Split ID: '.$splitPaymentId.' with Document Number: '.$sageResponse['response']);

                    $this->handleWithDeadlockRetries(function () use ($paymentSplit, $sageResponse) {
                        $paymentSplit->sage_reciept_id = $sageResponse['response'];
                        $paymentSplit->save();
                    }, $maxRetries);

                } else {
                    $sageMessage = $sageResponse['response'];
                    info('Sage receipt creation failed for Payment Split ID: '.$splitPaymentId.' with error: '.$sageMessage);

                    if ($isFromJob) {
                        CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::FAILED, 'message' => $sageMessage]);

                        return;
                    } else {
                        vAbort($sageMessage);
                    }
                }
            }
            // Log message for capturing split payment
            info('Capturing payment for Payment Split ID: '.$splitPaymentId.' and Code: '.$paymentSplit->code);

            if (! in_array($paymentSplit->payment_status_id, [PaymentStatusEnum::PAID, PaymentStatusEnum::PARTIALLY_PAID])) {
                $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
                $response = app(CRUDService::class)->capturePayment($quoteModel, $paymentSplit, $quoteTypeId, $amountCollected);
                //$paymentSplit->payment_status_id = PaymentStatusEnum::CAPTURED; //Temporarily commented on API request
            }

            $existingReceipts = QuoteDocument::where(['payment_split_id' => $splitPaymentId, 'document_type_text' => DocumentTypeEnum::RECEIPT])->get();
            if ($existingReceipts->count() === 0 && $isFromJob) {
                $this->createReceipt($modelType, $quoteId, $paymentSplit);
            }
        }

        if (! $paymentSplit->payment->is_approved && ! $isFromJob) {

            $retryResponse = $this->handleWithDeadlockRetries(function () use ($paymentSplit, $amountCollected, $modelType, $quoteId, $isFromJob) {
                if (empty($paymentSplit->verified_at)) {
                    $paymentSplit->verified_at = now();
                    $paymentSplit->verified_by = Auth::user()->id;
                }
                $paymentSplit->collection_amount = $amountCollected;
                $paymentSplit->save();
                $parentPayment = $paymentSplit->payment;
                info('Payment Split verified and collection amount updated for Payment Split ID: '.$paymentSplit->id.' and Code: '.$paymentSplit->code);

                /* Create payment receipt for broker */
                if ($parentPayment->collection_type == CollectionTypeEnum::BROKER &&
                    ! in_array($paymentSplit->payment_method, [PaymentMethodsEnum::CreditCard, PaymentMethodsEnum::CreditApproval]) &&
                    in_array($paymentSplit->payment_status_id, [PaymentStatusEnum::PAID, PaymentStatusEnum::PARTIALLY_PAID])
                ) {
                    $this->createReceipt($modelType, $quoteId, $paymentSplit);
                }
                $parentPayment->captured_amount = ($parentPayment->captured_amount + $amountCollected);
                $parentPayment->save();
                info('Parent payment captured amount updated for Payment Split ID: '.$paymentSplit->id.' and Code: '.$paymentSplit->code);

                if ($parentPayment->send_update_log_id) {
                    SendUpdateLog::where('id', $parentPayment->send_update_log_id)->update([
                        'status' => SendUpdateLogStatusEnum::TRANSACTION_APPROVED,
                    ]);
                }
                if ($isFromJob) {
                    $this->processMasterPaymentApprove($modelType, $quoteId, $parentPayment->send_update_log_id, true);
                }
            }, $maxRetries);

            if (isset($retryResponse['status']) && $retryResponse['status'] == PaymentProcessJobEnum::FAILED) {
                info('Failed to approve split payment for Payment Split ID: '.$splitPaymentId.' with error: '.$retryResponse['message']);
                if ($isFromJob) {
                    CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::FAILED, 'message' => $retryResponse['message']]);
                } else {
                    Log::error('Error in processSplitPaymentApprove '.$quoteModel->code.': '.$retryResponse['message']);
                }
            }

        } elseif ($isFromJob) {
            CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::SUCCESS, 'message' => PaymentProcessJobEnum::SUCCESS_MESSAGE]);
        }
    }

    // function to process the master payment approve
    public function processMasterPaymentApprove($modelType, $quoteId, $sendUpdateId, $isFromJob = false, $splitPaymentId = 0, $paymentCode = '')
    {
        $canCaptureEp = false;
        DB::beginTransaction();
        try {
            if ($sendUpdateId > 0) {
                $quoteModel = SendUpdateLogRepository::getLogById($sendUpdateId);
            } else {
                $quoteModel = $this->getQuoteObject($modelType, $quoteId);
                $oldQuoteStatus = $quoteModel->quote_status_id;
            }

            info('Processing master payment approval for Quote Code: '.$quoteModel->code);

            if ($paymentCode != '') {
                $masterPayment = $quoteModel->payments()->where('code', $paymentCode)->first();
            } else {
                $masterPayment = ($sendUpdateId > 0) ? $quoteModel->payments()->where('send_update_log_id', $sendUpdateId)->first() : $quoteModel->payments()->where('code', $quoteModel->code)->first();
            }

            $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
            $masterPaymentStatus = $masterPayment->payment_status_id;
            $totalPaidPayments = PaymentSplits::whereIn('payment_status_id', [
                PaymentStatusEnum::PAID,
                PaymentStatusEnum::CAPTURED,
            ])->where('code', $masterPayment->code)->count();

            $totalPartialPaidPayments = PaymentSplits::whereIn('payment_status_id', [
                PaymentStatusEnum::PARTIAL_CAPTURED,
                PaymentStatusEnum::PARTIALLY_PAID,
            ])->where('code', $masterPayment->code)->count();

            if ($totalPaidPayments == $masterPayment->total_payments) {
                $masterPaymentStatus = PaymentStatusEnum::CAPTURED;
            } elseif ($totalPartialPaidPayments > 0) {
                $masterPaymentStatus = PaymentStatusEnum::PARTIAL_CAPTURED;
            }

            $masterPayment->update([
                'is_approved' => 1,
                'payment_status_id' => $masterPaymentStatus,
                'updated_by' => Auth::user()->id,
            ]);

            info('Master payment approved for Quote Code: '.$quoteModel->code.' with Payment Status: '.$masterPaymentStatus);

            $successMessage = 'Transaction approved';
            $totalApproved = $quoteModel->payments()->where('is_approved', 1)->count();
            if ($totalApproved == $quoteModel->payments()->count()) {
                if ($sendUpdateId) {
                    $quoteModel->status = SendUpdateLogStatusEnum::TRANSACTION_APPROVED;
                } else {
                    $quoteModel->quote_status_id = QuoteStatusEnum::TransactionApproved;
                    app(CRUDService::class)->calculateScore($quoteModel, $modelType);
                    info('Transaction Score Calculated: '.$quoteModel->code);
                }
                $quoteModel->save();
                if (! $sendUpdateId) {
                    QuoteStatusLog::create([
                        'quote_type_id' => $quoteTypeId,
                        'quote_request_id' => $quoteModel->id,
                        'current_quote_status_id' => QuoteStatusEnum::TransactionApproved,
                        'previous_quote_status_id' => $oldQuoteStatus,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }

                $canCaptureEp = true;

                // Log for Berlin Service - Extend Customer Subscription
                $customerData = app(CustomerService::class)->getCustomerById($quoteModel->customer_id);
                if ($customerData) {
                    $quoteOptions = QuoteTypeId::getOptions();
                    $responseExtend = app(BerlinService::class)->extendCustomerSubscription($customerData->id, $customerData->email, strtoupper($quoteOptions[$quoteTypeId]).'-QUOTE', strtolower($quoteOptions[$quoteTypeId]).'-quote-myalfred-we');
                    info('Transaction Approved and subscription extended for Quote Code: '.$quoteModel->code.'. Response: '.$responseExtend);
                }

                // Log for creating duplicate lead for TRAVEL
                if ($quoteTypeId == QuoteTypeId::Travel && $quoteModel->payments()->count() > 1 && ! $sendUpdateId) {
                    if (app(TravelQuoteService::class)->createDuplicateLead($quoteModel)) {
                        $successMessage .= ', '.$quoteModel->code.'-1 Created For Booking The Additional Policy';
                        info('Duplicate lead created for Quote Code: '.$quoteModel->code.'-1');
                    }
                }
            }
            if (! $sendUpdateId) {
                $this->updateLeadStatus($masterPayment);
                info('Lead status updated for Master Payment with Quote Code: '.$quoteModel->code);
            }

            if ($isFromJob && $splitPaymentId > 0) {
                CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::SUCCESS, 'message' => PaymentProcessJobEnum::SUCCESS_MESSAGE]);
                info('Payment Process Job updated to SUCCESS for Split Payment ID: '.$splitPaymentId);
            }
            DB::commit();
        } catch (Exception $exception) {
            $canCaptureEp = false;
            if ($isFromJob && $splitPaymentId > 0) {
                CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::FAILED, 'message' => $exception->getMessage()]);
                info('Payment Process Job failed for Split Payment ID: '.$splitPaymentId.' with error: '.$exception->getMessage());
            }
            Log::error('Error in processMasterPaymentApprove for Quote Code: '.$quoteModel->code.': '.$exception->getMessage());
            DB::rollBack();
        }

        if ($canCaptureEp) {
            EmbeddedProductRepository::capturePayment($quoteId, $modelType);
            info('Captured EP and sent documents for Quote Code: '.$quoteModel->code);
        }

        return $successMessage;
    }

    // Update lead status for ecomm quotes
    private function updateLeadStatus($payment)
    {
        $quoteType = '';
        if ($payment->paymentable_type == CarQuote::class) {
            $quoteType = quoteTypeCode::Car;
        } elseif ($payment->paymentable_type == HealthQuote::class) {
            $quoteType = quoteTypeCode::Health;
        } elseif ($payment->paymentable_type == TravelQuote::class) {
            $quoteType = quoteTypeCode::Travel;
        }
        // If a quote type is found, get the corresponding quote object
        if ($quoteType !== '') {
            $quoteModel = $this->getQuoteObject($quoteType, $payment->paymentable_id);
            if ($quoteModel) {
                $quoteModel->payment_status_id = $payment->payment_status_id;
                if ($payment->payment_status_id == PaymentStatusEnum::PAID) {
                    $quoteModel->payment_paid_at = now();
                }
                $quoteModel->save();
            }
        }
    }

    public function updateCommissionSchedule($payment)
    {
        $paymentSplits = $payment->paymentSplits;
        $commissionSplitSumWithoutLastSplit = 0;
        $commissionVatApplicable = $payment->commission_vat_applicable;
        foreach ($paymentSplits as $paymentSplit) {
            $commissionSplitAmount = $this->calculateCommissionSplit($payment, $paymentSplit);
            /*
             to prevent difference in amount due to rounding number, sum all the Commission Split Amount except the last one,
             and then subtract that amount from the total commission without vat and use the result as commission for last commission split
            */
            if ($paymentSplit->sr_no == count($paymentSplits)) {
                $commissionSplitAmount = floatval(sprintf('%.2f', $commissionVatApplicable - $commissionSplitSumWithoutLastSplit));
            } else {
                $commissionSplitSumWithoutLastSplit += $commissionSplitAmount;
            }
            $paymentSplit->commission_vat_applicable = $commissionSplitAmount;
            /* Add Vat on commission to the first Installment of commission */
            $paymentSplit->commission_vat = $paymentSplit->sr_no == 1 ? $payment->commission_vat : 0;
            $paymentSplit->save();
        }
    }

    private function calculateCommissionSplit($payment, $paymentSplit)
    {
        $commissionVatApplicable = $payment->commission_vat_applicable;
        $totalPriceVatApplicable = $payment->paymentSplits()->sum('price_vat_applicable');

        return roundNumber(($paymentSplit->price_vat_applicable / $totalPriceVatApplicable) * $commissionVatApplicable);
    }

    // function to calculate the price vat
    public function calculatePriceAndVat($frequency, $masterTotalPrice, $splitPaymentNumber, $splitPaymentAmount, $modelType, $quoteId, $totalSplitPayments, $send_update_id = null)
    {
        $priceWithoutVat = $splitPaymentAmount;
        $vat = 0;
        $priceVatNotApplicable = 0;

        $vatValue = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::VAT_VALUE);
        if (! $vatValue) {
            return [$priceWithoutVat, $vat];
        }
        [$priceWithoutVat, $vat] = $this->calculateMasterPriceAndVat($frequency, $masterTotalPrice, $modelType, $quoteId, $send_update_id);
        if ($vat > 0) {
            $discount = 0;

            if ($send_update_id > 0) {
                $quoteModel = SendUpdateLogRepository::getLogById($send_update_id);
            } else {
                $quoteModel = $this->getQuoteObject($modelType, $quoteId);
                if (isset($quoteModel->price_vat_not_applicable) && $quoteModel->price_vat_not_applicable > 0) {
                    $priceVatNotApplicable = $quoteModel->price_vat_not_applicable;
                    $priceVatNotApplicable = $priceVatNotApplicable / $totalSplitPayments;
                }
            }
            $splitPaymentAmount = $splitPaymentAmount - $priceVatNotApplicable;
            $paymentDiscount = $quoteModel->payments()->where('code', $quoteModel->code)->value('discount_value');
            if ($splitPaymentNumber === 1 && $paymentDiscount > 0) {
                $splitPaymentAmount = $splitPaymentAmount + $paymentDiscount; //discount
            }

            if ($frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                $priceWithoutVat = $splitPaymentAmount / (1 + ($vatValue / 100));
                $vat = $priceWithoutVat * $vatValue / 100;
            } elseif ($splitPaymentNumber === 1) {
                $priceWithoutVat = $splitPaymentAmount - $vat;
            } else {
                $priceWithoutVat = $splitPaymentAmount;
                $vat = 0;
            }

            $priceWithoutVat = $priceWithoutVat + $priceVatNotApplicable;

        } else {
            $priceWithoutVat = $splitPaymentAmount;
        }

        return [$priceWithoutVat, $vat];
    }

    // function to calculate the price vat for master payment
    public function calculateMasterPriceAndVat($frequency, $masterTotalPrice, $modelType, $quoteId, $send_update_id = null)
    {
        $vat = 0;
        $priceWithoutVat = $masterTotalPrice;
        $priceVatNotApplicable = 0;
        $vatValue = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::VAT_VALUE);
        if (! $vatValue) {
            return [$priceWithoutVat, $vat];
        }
        $computedPrice = 0;
        $ecommLobs = [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel, quoteTypeCode::Bike];
        if ($send_update_id > 0) {
            $quoteModel = SendUpdateLogRepository::getLogById($send_update_id);
        } else {

            if (in_array($modelType, $ecommLobs)) {
                $computedPrice = $masterTotalPrice;
            } else {
                $quoteModel = $this->getQuoteObject($modelType, $quoteId);
            }
        }

        if (isset($quoteModel)) {
            if (isset($quoteModel->price_vat_applicable) && $quoteModel->price_vat_applicable > 0) {
                $computedPrice = $quoteModel->price_vat_applicable;
            }
            if (isset($quoteModel->price_vat_not_applicable) && $quoteModel->price_vat_not_applicable > 0) {
                $priceVatNotApplicable = $quoteModel->price_vat_not_applicable;
            }

        }

        if ($computedPrice > 0) {

            if (in_array($modelType, $ecommLobs) && ! $send_update_id) {
                $priceWithoutVat = $computedPrice / (1 + ($vatValue / 100));
                $vat = $priceWithoutVat * $vatValue / 100;
            } else {
                $priceWithoutVat = $computedPrice;
                $vat = ($priceWithoutVat * $vatValue) / 100;
            }
            $priceWithoutVat = $priceWithoutVat + $priceVatNotApplicable;

            return [$priceWithoutVat, $vat];
        }

        return [$priceWithoutVat, $vat];
    }

}
