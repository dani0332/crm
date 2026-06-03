<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CollectionTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\DocumentTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\InsuranceProviderEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentProcessJobEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentStatusTextEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SageEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Factories\SagePayloadFactory;
use App\Models\CarQuote;
use App\Models\CcPaymentProcess;
use App\Models\EmbeddedTransaction;
use App\Models\FtcEmailLog;
use App\Models\HealthQuote;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\PersonalQuote;
use App\Models\QuoteDocument;
use App\Models\SendUpdateLog;
use App\Models\TravelQuote;
use App\Repositories\LookupRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Rules\PlaceholderPrimaryEmail;
use App\Services\Life\EmbeddedProductService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Traits\CentralTrait;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\HandlesDeadlockRetries;
use App\Traits\SageLoggable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;
use stdClass;

class SplitPaymentService
{
    use CentralTrait;
    use GenericQueriesAllLobs;
    use HandlesDeadlockRetries;
    use SageLoggable;

    private const AFIA_WEBSITE_DOMAIN_CONFIG_KEY = 'constants.AFIA_WEBSITE_DOMAIN';
    private const HOME_INSURANCE_BASE_PATH = '/home-insurance/quote/';

    public function calculateDiscount($totalSplitPayments, $discountValue)
    {
        $discount = 0;
        if ($totalSplitPayments > 0) {
            $discount = $discountValue / $totalSplitPayments;
        }

        return $discount;
    }

    public function uploadDiscountDocuments($discountDocuments, $paymentSplitRecord)
    {
        LoggerService::info("Uploading discount documents for payment code: {$paymentSplitRecord->code} called");
        foreach ($discountDocuments[0] as $document) {
            $quoteDocumentRec = QuoteDocument::find($document['id']);
            if ($quoteDocumentRec) {
                $quoteDocumentRec->payment_split_id = $paymentSplitRecord->id;
                $quoteDocumentRec->save();
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
        } elseif (
            $paymentType == PaymentMethodsEnum::BankTransfer ||
            $paymentType == PaymentMethodsEnum::Cheque ||
            $paymentType == PaymentMethodsEnum::PostDatedCheque
        ) {
            $childPaymentStatus = PaymentStatusEnum::PENDING;
        } elseif ($paymentType == PaymentMethodsEnum::CreditApproval) {
            $childPaymentStatus = PaymentStatusEnum::CREDIT_APPROVED;
        }

        return $childPaymentStatus;
    }

    /* Will be removed once Sage Enhancements are verified */
    /*public function createSageRecipt($request, $splitPayment, $splitAmount = null)
    {
        if ($splitAmount != null) {
            $request->collection_amount = $splitAmount;
        }

        $returnMessage = ['status' => 'error', 'response' => ''];
        $quote = $this->getQuoteObject($request->modelType, $request->quote_id);
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($request->modelType));
        $customerData = ['quoteTypeId' => $quoteTypeId, 'id' => $quote->id];
        $sageLogArray = $splitPayment->sageApiLogs->keyBy('step')->toArray();

        $sageApiService = new SageApiService;
        $sageCustomerNumber = $sageApiService->verifySageCustomer($request->customer_id, $customerData, $splitPayment, 4, $request->advisor_id);
        if ($sageCustomerNumber == '') {
            LoggerService::info('Child payment code: '.$splitPayment->code.' with serial no: '.$splitPayment->sr_no.' SAGE API Payments Error: Customer not found in Sage');
            $returnMessage['response'] = 'Customer not found in sage - Ref:'.$quote->code;

            return $returnMessage;
        }

        LoggerService::info('Child payment code: '.$splitPayment->code.' with serial no: '.$splitPayment->sr_no.' SAGE API Payments: Verified Sage customer number: '.$sageCustomerNumber);
        $request->merge(['sage_customer_number' => $sageCustomerNumber]);

        if ($splitPayment->sr_no == 1) {
            $request->merge(['discount' => $splitPayment->payment->discount_value]);
        }

        $isLiveApiCallStep2 = true;
        $payLoadOptions = SagePayloadFactory::createPrepaymentPayload($request);

        if (isset($sageLogArray[2]) && $sageLogArray[2]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('SAGE API : createPrepaymentReceipts Sent Already for '.$quote->code);
            $isLiveApiCallStep2 = false;
            $sageResponse = json_decode($sageLogArray[2]['response'], true);
        } else {
            LoggerService::info('SAGE API :  Send createPrepaymentReceipts for '.$quote->code);
            $request->merge(['sage_payment_code' => $splitPayment->payment_method]);
            $payLoadOptions = SagePayloadFactory::createPrepaymentReceiptPayload($request);
            $message = $sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($message, true);
        }

        if (! empty($sageResponse['BatchNumber'])) {
            LoggerService::info('Child payment code: '.$splitPayment->code.' with serial no: '.$splitPayment->sr_no.' SAGE API Payments: Created AR Prepayment Receipts batch '.$sageResponse['BatchNumber']);

            if ($isLiveApiCallStep2) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $splitPayment, 2, 4, SageEnum::STATUS_SUCCESS, $request->advisor_id);
            }

            $isLiveApiCallStep3 = true;
            $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptArPayment($sageResponse['BatchNumber']);

            if (isset($sageLogArray[3]) && $sageLogArray[3]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info('SAGE API : readyToPostPrepaymentReceipts Sent Already for '.$quote->code);
                $isLiveApiCallStep3 = false;
                $readyToPostResponse = $sageLogArray[3]['response'];
            } else {
                LoggerService::info('SAGE API : Send readyToPostPrepaymentReceipts  for '.$quote->code);
                $readyToPostResponse = $sageApiService->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $readyToPostArray = json_decode($readyToPostResponse, true);

                if (isset($readyToPostArray['error']['message']['value'])) {
                    LoggerService::info('Child payment code: '.$splitPayment->code.' with serial no: '.$splitPayment->sr_no.' SAGE API Payments Error: Failed to post AR Prepayment Receipts batch '.$sageResponse['BatchNumber'].' Error: '.$readyToPostArray['error']['message']['value']);

                    $aRReceiptBatch = $sageApiService->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$sageResponse['BatchNumber'].')', [], 'GET');
                    LoggerService::info('Child payment code: '.$splitPayment->code.' with serial no: '.$splitPayment->sr_no.' SAGE API Payments: Status of AR Prepayment Receipts batch: '.$aRReceiptBatch);
                    $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                    if (isset($aRReceiptBatch['BatchStatus']) && $aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $splitPayment, 3, 4, SageEnum::STATUS_SUCCESS, $request->advisor_id);
                    } else {
                        LoggerService::info('Child payment code: '.$splitPayment->code.' with serial no: '.$splitPayment->sr_no.' SAGE API Payments Error: Failed to post AR Prepayment Receipts batch '.$sageResponse['BatchNumber']);
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $splitPayment, 3, 4, SageEnum::STATUS_FAIL, $request->advisor_id);
                        $returnMessage['response'] = 'Error while making ready to post to sage - Ref:'.$quote->code;

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

            $sendUpdateLog = $splitPayment->payment?->sendUpdateLog;
            $isSendUpdateBooked = $sendUpdateLog?->status == SendUpdateLogStatusEnum::UPDATE_BOOKED;

            $isPolicyBooked = $quote->quote_status_id == QuoteStatusEnum::PolicyBooked;

            $shouldSchedulePostPrepayment = ($isPolicyBooked && ! $sendUpdateLog) || ($sendUpdateLog && $isSendUpdateBooked);

            if ($shouldSchedulePostPrepayment) {
                LoggerService::info(self::class.' fn:'.__FUNCTION__.' trigger post prepayment schedule for PaymentSplitID : '.$splitPayment->id);
                $postPrepayment = (new SageApiService)->schedulePostPrepaymentToSageProcess([$quote, $request->modelType, $splitPayment, $sendUpdateLog]);
                if (! $postPrepayment['status']) {
                    LoggerService::info(self::class.' fn:'.__FUNCTION__.' failed to scheduled post prepayment for PaymentSplitID : '.$splitPayment->id, extra: $postPrepayment);
                } else {
                    LoggerService::info(self::class.' fn:'.__FUNCTION__.' post prepayment scheduled for PaymentSplitID : '.$splitPayment->id, extra: $postPrepayment);
                }
            }

            $documentNumberForReciept = $sageResponse['ReceiptsAdjustments'][0]['DocumentNumber'];
            LoggerService::info('Child payment code: '.$splitPayment->code.' with serial no: '.$splitPayment->sr_no.' SAGE API Payments: Successfully created receipt');
            $returnMessage = ['status' => 'success', 'response' => $documentNumberForReciept];

        } else {
            LoggerService::info('Child payment code: '.$splitPayment->code.' with serial no: '.$splitPayment->sr_no.' SAGE API Payments Error: Document number not generated from Sage');
            $this->logSageApiCall($payLoadOptions, $sageResponse, $splitPayment, 2, 4, SageEnum::STATUS_FAIL, $request->advisor_id);
            $returnMessage['response'] = 'Document number not generated from sage - Ref:'.$quote->code;

            return $returnMessage;
        }

        return $returnMessage;
    }*/

    // function to check if the payment structure is new
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
                    // Get Ecommerce Health Premium
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
                        // get vat from settings
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

                if ($payment->payment_status_id == PaymentStatusEnum::DRAFT) { // draft
                    $payment->payment_status_id = PaymentStatusEnum::NEW; // new
                } elseif (
                    ($payment->payment_status_id == PaymentStatusEnum::CAPTURED || $payment->payment_status_id == PaymentStatusEnum::PARTIAL_CAPTURED)
                    && $premium > 0
                ) {
                    $capturedAmount = 0;
                    foreach ($childPayments as $childPayment) {
                        if (
                            $childPayment->payment_status_id == PaymentStatusEnum::CAPTURED
                            || $childPayment->payment_status_id == PaymentStatusEnum::PARTIAL_CAPTURED
                        ) {
                            $capturedAmount += $childPayment->captured_amount;
                        }
                    }
                    if ($capturedAmount > 0 && $premium > $capturedAmount) {
                        $payment->payment_status_id = PaymentStatusEnum::PARTIALLY_PAID; // partially paid
                    }
                }

                $payment->collection_date = $payment->updated_at;
                $payment->save();

                if ($childPayments->isNotEmpty()) {
                    $payment_sr_no = 1;
                    foreach ($childPayments as $childPayment) {

                        if ($childPayment->payment_status_id == PaymentStatusEnum::DRAFT) { // draft
                            $childPayment->payment_status_id = PaymentStatusEnum::NEW; // new
                        }
                        // Create a new SplitPayment record
                        $collectionAmount = 0;
                        if (
                            $childPayment->payment_status_id == PaymentStatusEnum::PAID || $childPayment->payment_status_id == PaymentStatusEnum::CAPTURED // if paid or captured
                            || $childPayment->payment_status_id == PaymentStatusEnum::PARTIAL_CAPTURED || $childPayment->payment_status_id == PaymentStatusEnum::PARTIALLY_PAID // if partial paid or captured
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
                        // //$childPayment->delete();
                    }
                    if ($payment->code == $code) {

                        if ($premium > 0 && $parentCollectionAmount > 0 && $premium > $parentCollectionAmount) {
                            $payment->payment_status_id = PaymentStatusEnum::PARTIALLY_PAID; // partially paid
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

    public function createReceipt($modelType, $quoteId, $splitPayment, $send_update_id = null, $isFromJob = false)
    {
        LoggerService::info('Child payment code: '.$splitPayment->code.' with serial no: '.$splitPayment->sr_no.' create receipt called from job '.($isFromJob ? 'true' : 'false'));

        try {
            $quote = $this->getQuoteObject($modelType, $quoteId);
            $quote->load(['customer', 'advisor']);
            $data = $this->prepareReceiptData($quote, $splitPayment, $modelType, $send_update_id);
            $documentType = $this->getDocumentType($modelType);
            $data['document_type_code'] = $documentType;
            $data['quote_uuid'] = $quote->uuid;
            if ($send_update_id > 0) {
                $quote = SendUpdateLog::find($send_update_id);
            }
            $pdf = PDF::loadView('pdf.payment_receipt', compact('data'))->setOptions(['defaultFont' => 'DejaVu Sans']);
            $pdf->setPaper('A4');
            $pdfFile = $pdf->output();

            $document = app(QuoteDocumentService::class)->uploadQuoteDocument($pdfFile, $data, $quote, false, true);
        } catch (\Exception $ex) {
            $errorMessage = 'Child payment code: '.$splitPayment->code.' with serial no: '.$splitPayment->sr_no.' Payment Reciept - ERROR';
            LoggerService::error($errorMessage, exception: $ex);
            if ($isFromJob && $splitPayment->id > 0) {
                CcPaymentProcess::where('payment_splits_id', $splitPayment->id)->update(['status' => PaymentProcessJobEnum::FAILED, 'message' => $errorMessage]);
                LoggerService::error('Payment Process Job failed for Split Payment ID: '.$splitPayment->id.' with error: '.$errorMessage, exception: $ex);
                $this->handleAutomationError($quote, $modelType, $splitPayment->payment);
            }
        }
    }

    private function prepareReceiptData($quote, $splitPayment, $modelType, $send_update_id)
    {
        $data = [];
        $data['order_amount'] = number_format($splitPayment->collection_amount, 2, '.', ',');
        $data['payment_split_id'] = $splitPayment->id;

        $data['customer_name'] = ! empty($quote->first_name) ? $quote->first_name.' '.$quote->last_name : $quote->customer->first_name.' '.$quote->customer->last_name;

        $data['advisor_name'] = $quote->advisor->name ?? '';
        $data['advisor_email'] = $quote->advisor->email ?? '';
        $data['advisor_mobile_no'] = $quote->advisor->mobile_no ?? '';
        $data['advisor_landline_no'] = $quote->advisor->landline_no ?? '';
        $data['profile_photo_path'] = $quote->advisor->profile_photo_path ?? '';

        $data['receipt_number'] = $splitPayment->code;
        $data['order_number'] = $splitPayment->code.'-'.$splitPayment->sr_no;
        $data['pdf_filename'] = $splitPayment->code.'-'.$splitPayment->sr_no;
        $data['order_at'] = date(config('constants.RECEIPT_ORDER_DATE'), strtotime($splitPayment->verified_at));
        $orderDateFormat = config('constants.DATE_DISPLAY_FORMAT');
        $data['captured_at'] = date($orderDateFormat, strtotime($splitPayment->verified_at));
        if ($splitPayment->captured_at != null) {
            $data['captured_at'] = date($orderDateFormat, strtotime($splitPayment->captured_at));
        }
        $data['insurance_company'] = $this->getInsuranceCompany($quote, $send_update_id);
        info('Insurance company for '.$quote->code.' is '.$data['insurance_company']);
        $splitPayment->load(['payment', 'paymentMethod']);
        $data['payment_method'] = $splitPayment->paymentMethod->name;
        $data['remarks'] = $splitPayment->payment->notes;
        $data['vat'] = number_format(0, 2, '.', ',');
        $data['discount'] = number_format(0, 2, '.', ',');

        $data['type_of_insurance'] = $this->getTypeOfInsurance($quote, $modelType);

        return $data;
    }

    private function getDocumentType($modelType)
    {
        switch ($modelType) {
            case QuoteTypes::HOME->value:
                return DocumentTypeCode::HOMPD_RECEIPT;
            case QuoteTypes::HEALTH->value:
                return DocumentTypeCode::HPD_RECEIPT;
            case QuoteTypes::LIFE->value:
                return DocumentTypeCode::LPD_RECEIPT;
            case QuoteTypes::BUSINESS->value:
                return DocumentTypeCode::CLPD_RECEIPT;
            case QuoteTypes::BIKE->value:
                return DocumentTypeCode::BPD_RECEIPT;
            case QuoteTypes::YACHT->value:
                return DocumentTypeCode::YPD_RECEIPT;
            case QuoteTypes::TRAVEL->value:
                return DocumentTypeCode::TPD_RECEIPT;
            case QuoteTypes::PET->value:
                return DocumentTypeCode::PPD_RECEIPT;
            case QuoteTypes::CYCLE->value:
                return DocumentTypeCode::CYCPD_RECEIPT;
            case QuoteTypes::GROUP_MEDICAL->value:
                return DocumentTypeCode::GMQPD_RECEIPT;
            case QuoteTypes::SAVINGS->value:
                return DocumentTypeCode::SPD_RECEIPT;
            case QuoteTypes::DEVICE->value:
                return DocumentTypeCode::DEVICE_SMARTPHONE_PAYMENT_RECEIPT;
            default:
                return DocumentTypeCode::CPD_RECEIPT;
        }
    }

    private function getInsuranceCompany($quote, $send_update_id)
    {
        if ($send_update_id > 0) {
            $quote = SendUpdateLog::find($send_update_id);
        }
        $quote->load(['payments.insuranceProvider']);
        $payment = $quote->payments()->first();

        return $payment->insuranceProvider->text;
    }

    private function getTypeOfInsurance($quote, $modelType)
    {
        if ($modelType == QuoteTypes::BUSINESS->value) {
            $quote->load(['businessTypeOfInsurance']);

            return $quote->businessTypeOfInsurance->text;
        } else {
            return $modelType.' Insurance';
        }
    }

    public function generateSplitPaymentLink($request)
    {
        LoggerService::info('Generate split payment link called for payment code: '.$request->paymentCode.' and sr no: '.$request->splitPaymentId);

        // Fetch the split payment record from the database.
        $splitPayment = PaymentSplits::where(['code' => $request->paymentCode, 'sr_no' => $request->splitPaymentId])->first();

        // Validate that the split payment and its parent payment exist.
        if (! $splitPayment || ! $splitPayment->payment) {
            return response()->json(['success' => false, 'message' => 'Payment split or payments not found.']);
        }

        $payment = $splitPayment->payment;
        $modelType = $request->modelType;
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
        $processNewUrl = true;

        // for car quote with plan detail enabled, de-select embeded products & generate old url
        if ($quoteTypeId == QuoteTypeId::Car && $request->isPlanDetailEnabled) {
            (new EmbeddedProductService)->deSelectEPTransactions($payment->paymentable_id);
            $processNewUrl = false;
        }

        if ($processNewUrl && $payment->frequency == PaymentFrequency::UPFRONT && $payment->payment_methods_code == PaymentMethodsEnum::CreditCard) {
            // Check if the transaction is an "embedded" transaction from the main website's quote flow.
            $isEmbedded = EmbeddedTransaction::where('quote_request_type', $payment->paymentable_type)
                ->where('quote_request_id', $payment->paymentable_id)
                ->select('id')
                ->limit(1)
                ->exists();

            if ($isEmbedded) {
                // For embedded transactions, generate a link that directs the user back to the website's payment page.
                LoggerService::info("Generating embedded payment link for {$request->paymentCode}-{$request->splitPaymentId}.");
                $paymentLink = config('constants.AFIA_WEBSITE_DOMAIN');
                $lob = strtolower($modelType);
                $urlIdentifier = $this->getPaymentLinkURLIdentifier($modelType);
                $paymentLink = "{$paymentLink}/{$urlIdentifier}-insurance/quote/{$request->quoteUuid}/payment";

                $insuranceProvider = getInsuranceProvider($payment, $lob);
                $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));

                $paymentParams = [
                    'planId' => $payment->plan_id,
                    'providerCode' => $insuranceProvider->code,
                    'quoteTypeId' => $quoteTypeId,
                ];
                $paymentLinkURL = $paymentLink.'?'.http_build_query($paymentParams);

                return response()->json(['success' => true, 'payment_link' => $paymentLinkURL]);
            }
        }

        // For standard transactions, check if a valid, non-expired payment link already exists.
        if ($splitPayment->payment_link != null && now() < Carbon::parse($splitPayment->payment_link_created_at)->addDays(3)) {
            LoggerService::info("Returning existing payment link for {$request->paymentCode}-{$request->splitPaymentId}.");

            return response()->json(['success' => true, 'payment_link' => $splitPayment->payment_link]);
        }

        // If no valid link exists, generate a new one.
        LoggerService::info("Generating standard payment link for {$request->paymentCode}-{$request->splitPaymentId}.");
        $paymentLink = config('constants.PAYMENT_REDIRECT_LINK');
        $paymentLink .= $splitPayment->payment_method === PaymentMethodsEnum::InsureNowPayLater ? 'tabby' : 'checkout';

        $paymentParams = [
            'code' => $payment->code.'-'.$splitPayment->sr_no,
            'quoteTypeId' => $quoteTypeId,
        ];
        $paymentLinkURL = $paymentLink.'?'.http_build_query($paymentParams);

        return response()->json(['success' => true, 'payment_link' => $paymentLinkURL]);
    }

    private function getPaymentLinkURLIdentifier($modelType)
    {
        return strtolower(quoteTypeCode::resolveQuoteType($modelType));
    }

    public function generateInsurerPaymentLink($request)
    {
        $splitPayment = PaymentSplits::where(['code' => $request->paymentCode, 'sr_no' => $request->splitPaymentId])->first();
        if (! $splitPayment) {
            return response()->json(['success' => false]);
        }
        $ftcEmailLog = FtcEmailLog::where('link', $splitPayment->insurer_payment_link)->first();
        if (! $ftcEmailLog) {
            return response()->json(['success' => false]);
        }
        $payment = $splitPayment->payment;
        $modelType = $request->modelType;
        $quoteId = $request->quoteId;
        $quoteModel = $this->getQuoteObject($modelType, $quoteId);
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));

        $paymentLink = config('constants.AFIA_WEBSITE_DOMAIN');
        $paymentLinkURL = $paymentLink.'/redirect/'.$quoteTypeId.'/'.$quoteModel->uuid.'/'.$quoteModel->plan?->id.'?uid='.$ftcEmailLog->uuid ?? ''.'?uid='.$ftcEmailLog->uuid;

        return response()->json(['success' => true, 'payment_link' => $paymentLinkURL]);
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
        // Move select queries outside transaction
        $paymentSplit = PaymentSplits::find($splitPaymentId);
        LoggerService::info("Processing split payment approval for {$paymentSplit->code} with serial no: {$paymentSplit->sr_no} is from job: ".($isFromJob ? 'true' : 'false'));

        $payment = $paymentSplit->payment;
        $sendUpdateId = $payment->send_update_log_id;
        $mainLeadObject = $this->getQuoteObject($modelType, $quoteId);
        if (! $mainLeadObject) {
            $extra = [
                'modelType' => $modelType,
                'quoteId' => $quoteId,
                'splitId' => $splitPaymentId,
                'amountCollected' => $amountCollected,
                'isFromJob' => $isFromJob,
            ];
            LoggerService::info("processSplitPaymentApprove: Quote not found for Model Type {$modelType} and Quote Id: {$quoteId}", extra: $extra);
            if ($isFromJob) {
                CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::FAILED, 'message' => PaymentProcessJobEnum::QUOTE_NOTFOUND_MESSAGE]);

                return false;
            } else {
                vAbort("Quote not found for Model Type {$modelType} and Quote Id: {$quoteId}");
            }
        }
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
            info("Payment split {$paymentSplit->code} failed - Quote not found");
            CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::FAILED, 'message' => PaymentProcessJobEnum::QUOTE_NOTFOUND_MESSAGE]);

            return false;
        }

        LoggerService::startQuoteLogging($quoteModel);

        if ($paymentSplit->payment_method == PaymentMethodsEnum::CreditCard) {
            // Log message for creating Sage receipt
            LoggerService::info("Creating Sage receipt for payment split Code: {$paymentSplit->code}, Serial: {$paymentSplit->sr_no} - Current Sage receipt ID: {$paymentSplit->sage_reciept_id}");
            $isAbuDhabiBranch = $this->isAbuDhabiBranch($modelType, $mainLeadObject);
            $shouldCreatePrepaymentPremiumReceipt = (new SageApiService)->shouldCreateAndSchedulePostPrepayment($quoteModel, $paymentSplit); /* Handle NRA case where payment is approved after policy/send update is booked */
            info('Child payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no.' trigger creation of Premium Sage receipt  : ', ['shouldCreatePrepaymentPremiumReceipt' => $shouldCreatePrepaymentPremiumReceipt]);
            if ((new SageApiService)->isSageEnabled() && $shouldCreatePrepaymentPremiumReceipt && ! $isAbuDhabiBranch && empty($paymentSplit->sage_reciept_id)) {
                // Create an empty Request object
                $sageRequest = new stdClass;
                $sageRequest->userId = auth()->id();
                $sageRequest->quoteType = $modelType;
                $sageRequest->modelType = $modelType;
                $sageRequest->quote_id = $quoteId;
                $sageRequest->customer_id = $quoteModel?->customer_id;
                $sageRequest->advisor_id = $quoteModel?->advisor_id;

                /* Handle NRA case where payment is approved after policy/send update is booked */
                $sageARPrepaymentResponse = (new SageApiService)->createARPrepaymentPremiumReceipt($sageRequest, $quoteModel, $payment, $paymentSplit, $amountCollected);
                /*$sageRequest->sage_customer_number = $sageARPrepaymentResponse['sageCustomerNumber'];
                $sageAPPrepaymentResponse = (new SageApiService)->createAPPrepaymentPremiumReceipt($sageRequest, $quoteModel, $payment, $paymentSplit, $amountCollected);*/

                if ($sageARPrepaymentResponse['status'] /* && $sageAPPrepaymentResponse['status'] */) {
                    LoggerService::info("Sage receipt created successfully for payment split Code: {$paymentSplit->code}, Serial: {$paymentSplit->sr_no} with Document Number: {$sageARPrepaymentResponse['message']}");
                } else {
                    $sageMessage = $sageARPrepaymentResponse['message'];
                    LoggerService::info("Sage receipt creation failed for payment split Code: {$paymentSplit->code}, Serial: {$paymentSplit->sr_no} with error: {$sageMessage}");

                    if ($isFromJob) {
                        CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::FAILED, 'message' => $sageMessage]);
                        $this->handleAutomationError($quoteModel, $modelType, $paymentSplit->payment);

                        return false;
                    } else {
                        vAbort($sageMessage);
                    }
                }
            }

            // Log message for capturing split payment
            LoggerService::info("Capturing payment for split payment Code: {$paymentSplit->code}, Serial: {$paymentSplit->sr_no} with split payment status id: {$paymentSplit->payment_status_id}");

            if (! in_array($paymentSplit->payment_status_id, [PaymentStatusEnum::PAID, PaymentStatusEnum::PARTIALLY_PAID])) {
                $createdBy = null;
                // this is only for car main lead payment, whenever this function is called from automation job.
                if ($modelType == quoteTypeCode::Car && ! $sendUpdateId) {
                    $mainLeadPayment = $quoteModel->payments()->mainLeadPayment()->first();
                    $insuranceProvider = getInsuranceProvider($mainLeadPayment, $modelType, $quoteModel);
                    if ($insuranceProvider->code == InsuranceProvidersEnum::AXA) {
                        $createdBy = $quoteModel->kycDocumentUser?->createdBy?->email;
                    }
                }
                $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
                // Calling the Marshall API to capture the payment
                $capturePaymentResponse = app(CRUDService::class)->capturePayment($quoteModel, $paymentSplit, $quoteTypeId, $amountCollected, $createdBy);
                if ($capturePaymentResponse->getStatusCode() != 200) {
                    $data = json_decode($capturePaymentResponse->getContent(), true);
                    $this->handleCapturePaymentError($data[0] ?? '', $isFromJob, $paymentSplit->id, $paymentSplit->code);
                    if ($isFromJob) {
                        $this->handleAutomationError($quoteModel, $modelType, $paymentSplit->payment);
                    }
                }
            }

            // Creating the receipt for the split payment if the payment is from job and the collection type is broker
            // As of now we are not using broker in payment
            if ($isFromJob && $payment->collection_type == CollectionTypeEnum::BROKER) {
                $existingReceipts = QuoteDocument::where(['payment_split_id' => $splitPaymentId, 'document_type_text' => DocumentTypeEnum::RECEIPT])->get();
                if ($existingReceipts->count() === 0) {
                    $this->createReceipt($modelType, $quoteId, $paymentSplit, $sendUpdateId, $isFromJob);
                }
            }
        }

        $paymentSplit = PaymentSplits::with([
            'payment' => function ($query) {
                $query->with(['insuranceProvider', 'sendUpdateLog']);
            },
        ])->find($splitPaymentId);

        $parentPayment = $paymentSplit->payment;
        $shouldCreateReceipt = $this->shouldCreateReceipt($parentPayment, $paymentSplit);
        $shouldProcessPayment = $this->shouldProcessPayment($paymentSplit, $isFromJob, $modelType);

        LoggerService::info("for split payment Code: {$paymentSplit->code}, Serial: {$paymentSplit->sr_no} shouldProcessPayment: ".($shouldProcessPayment ? 'true' : 'false'));
        // Only start transaction if we need to process the payment
        if ($shouldProcessPayment) {
            $retryResponse = $this->handleWithDeadlockRetries(function () use ($paymentSplit, $amountCollected, $modelType, $quoteId, $isFromJob, $sendUpdateId, $parentPayment, $shouldCreateReceipt) {
                if (! isset($paymentSplit->collection_amount)) {

                    if (empty($paymentSplit->verified_at)) {
                        $paymentSplit->verified_at = now();
                        $paymentSplit->verified_by = Auth::user()->id ?? null;
                    }

                    $paymentSplit->collection_amount = $amountCollected;
                    $paymentSplit->save();

                    $parentPayment->captured_amount += $amountCollected;
                    $parentPayment->save();
                }

                LoggerService::info("Child payment code: {$paymentSplit->code} with serial no: {$paymentSplit->sr_no} Payment Split verified and collection amount updated");

                // Create payment receipt for broker & As of now we are not using broker in payment
                // We will remove this code soon
                if ($shouldCreateReceipt) {
                    $this->createReceipt($modelType, $quoteId, $paymentSplit, $sendUpdateId, $isFromJob);
                }

                // Handling the send update log status
                if ($parentPayment->send_update_log_id) {
                    LoggerService::info("Child payment code: {$paymentSplit->code} with serial no: {$paymentSplit->sr_no} Starting send update log process");

                    $sendUpdateLog = $parentPayment->sendUpdateLog;
                    if (in_array($sendUpdateLog->status, SendUpdateLogStatusEnum::getSendUpdateBookingStatuses())) {
                        LoggerService::info("Child payment code: {$paymentSplit->code} with serial no: {$paymentSplit->sr_no} Send update log status is already in the list of update booking queued, update booking failed or update booked, so skipping the update");
                    } else {
                        $sendUpdateLog->update([
                            'status' => SendUpdateLogStatusEnum::TRANSACTION_APPROVED,
                        ]);
                        LoggerService::info("Child payment code: {$paymentSplit->code} with serial no: {$paymentSplit->sr_no} Send update log status updated successfully");
                    }
                }
            }, $maxRetries);

            // Process master payment approve if the payment is from job
            if ($isFromJob) {
                $this->processMasterPaymentApprove($modelType, $quoteId, $parentPayment->send_update_log_id, true);
            }

            LoggerService::info("Split payment code: {$paymentSplit->code}, Serial: {$paymentSplit->sr_no} after approve: ");

            if (isset($retryResponse['status']) && $retryResponse['status'] == PaymentProcessJobEnum::FAILED) {
                LoggerService::info("Child payment code: {$paymentSplit->code} with serial no: {$paymentSplit->sr_no} Failed to approve split payment");
                if ($isFromJob) {
                    CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::FAILED, 'message' => $retryResponse['message']]);
                    $this->handleAutomationError($quoteModel, $modelType, $paymentSplit->payment);

                    return false;
                } else {
                    info("Child payment code: {$paymentSplit->code} with serial no: {$paymentSplit->sr_no} Failed to approve split payment");
                    Log::error('Error in processSplitPaymentApprove '.$quoteModel->code.': '.$retryResponse['message']);
                }
            } else {
                LoggerService::info("Split payment Code: {$paymentSplit->code}, Serial: {$paymentSplit->sr_no} all condition meet and isFromJob : ".($isFromJob ? 'true' : 'False'));
                if ($isFromJob) { // TODO : Add Ecom check to make sure only customer purchased policy schedule for automation
                    LoggerService::info("Split payment Code: {$paymentSplit->code}, Serial: {$paymentSplit->sr_no}  createPolicyIssuanceAutomation started");
                    $this->createPolicyIssuanceAutomation($quoteModel, $modelType, $paymentSplit->payment);

                }
                CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::SUCCESS, 'message' => PaymentProcessJobEnum::SUCCESS_MESSAGE]);

                return true;
            }
        } elseif ($isFromJob) {
            CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::SUCCESS, 'message' => PaymentProcessJobEnum::SUCCESS_MESSAGE]);

            return true;
        }

        return true;
    }

    private function handleCapturePaymentError($error, $isFromJob, $splitPaymentId, $quoteCode)
    {
        if ($isFromJob && $splitPaymentId > 0) {
            CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::FAILED, 'message' => $error]);
            info('Payment Process Job failed for Split Payment ID: '.$splitPaymentId.' with error: '.$error);
        }
        Log::error('Error in handleCreditCardPayment for Quote Code: '.$quoteCode.': '.$error);
    }

    // function to process the master payment approve
    public function processMasterPaymentApprove($modelType, $quoteId, $sendUpdateId, $isFromJob = false, $splitPaymentId = 0, $paymentCode = '')
    {
        $oldQuoteStatus = null;
        if ($sendUpdateId > 0) {
            $quoteModel = SendUpdateLogRepository::getLogById($sendUpdateId);
        } else {
            $quoteModel = $this->getQuoteObject($modelType, $quoteId);
            $oldQuoteStatus = $quoteModel->quote_status_id;
        }

        LoggerService::startQuoteLogging($quoteModel);

        LoggerService::info('Master payment code: '.$quoteModel->code.' Processing master payment approval started');
        $totalApproved = $quoteModel->payments()->where('is_approved', 1)->count();
        $totalPaymentsCount = $quoteModel->payments()->count();

        $quoteModel->load(['payments' => function ($query) use ($paymentCode, $sendUpdateId, $quoteModel) {
            $query->with(['paymentSplits', 'insuranceProvider', 'sendUpdateLog']);

            if ($paymentCode != '') {
                $query->where('code', $paymentCode);
            } else {
                $query->when($sendUpdateId > 0,
                    fn ($q) => $q->where('send_update_log_id', $sendUpdateId),
                    fn ($q) => $q->where('code', $quoteModel->code)
                );
            }
        }]);

        $masterPayment = $quoteModel->payments->first();

        if (! $masterPayment) {
            LoggerService::info('Master payment not found during capture payment for quote code: '.$quoteModel->code);
            $errorMessage = 'Master payment not found for quote code: '.$quoteModel->code;

            if ($isFromJob && $splitPaymentId > 0) {
                CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::FAILED, 'message' => $errorMessage]);
                LoggerService::error('Master payment code: '.$quoteModel->code.' Payment Process Job failed for Split Payment ID: '.$splitPaymentId.' - Master payment not found');
            }

            return $errorMessage;
        }

        $primaryEmailQuote = $quoteModel;
        if ($sendUpdateId > 0) {
            $quoteType = QuoteTypes::getName($quoteModel->quote_type_id)?->value;
            if ($quoteType) {
                $primaryEmailQuote = $this->getQuoteObjectBy($quoteType, $quoteModel->quote_uuid, 'uuid');
            }
        }

        if (PlaceholderPrimaryEmail::hasPlaceholderPrimaryEmail($primaryEmailQuote ?: null)) {
            $errorMessage = PlaceholderPrimaryEmail::message();
            LoggerService::warning('Master payment approval blocked due to placeholder primary email', [
                'quote_code' => $quoteModel->code,
                'send_update_id' => $sendUpdateId,
                'quote_uuid' => $primaryEmailQuote->uuid ?? null,
            ]);

            if ($isFromJob && $splitPaymentId > 0) {
                CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update([
                    'status' => PaymentProcessJobEnum::FAILED,
                    'message' => $errorMessage,
                ]);
            }

            return $errorMessage;
        }

        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
        $masterPaymentStatus = $masterPayment->payment_status_id;

        $totalPaidPayments = $masterPayment->paymentSplits->whereIn('payment_status_id', [
            PaymentStatusEnum::PAID,
            PaymentStatusEnum::CAPTURED,
        ])->count();

        $totalPartialPaidPayments = $masterPayment->paymentSplits->whereIn('payment_status_id', [
            PaymentStatusEnum::PARTIAL_CAPTURED,
            PaymentStatusEnum::PARTIALLY_PAID,
        ])->count();

        if ($totalPaidPayments == $masterPayment->total_payments) {
            $masterPaymentStatus = PaymentStatusEnum::CAPTURED;
        } elseif ($totalPartialPaidPayments > 0) {
            $masterPaymentStatus = PaymentStatusEnum::PARTIAL_CAPTURED;
        }

        $successMessage = '';

        DB::beginTransaction();
        try {

            $masterPayment->update([
                'is_approved' => 1,
                'payment_status_id' => $masterPaymentStatus,
                'updated_by' => Auth::user()->id ?? null,
            ]);

            $totalApproved++;

            LoggerService::info("Master payment code: {$quoteModel->code} Master payment approved with Payment Status: {$masterPaymentStatus} and total approved payments: {$totalApproved} and total payments count: {$totalPaymentsCount} insurance provider code: {$masterPayment->insuranceProvider->code} and isFromJob: ".($isFromJob ? 'true' : 'false'));

            $successMessage = 'Processing master payment approval completed';

            if (
                (
                    in_array($masterPayment->insuranceProvider->code, [InsuranceProviderEnum::QIC->value, InsuranceProviderEnum::DIC->value]) &&
                    $isFromJob &&
                    $totalApproved > 0
                ) ||
                ($totalApproved == $totalPaymentsCount)
            ) {
                if ($sendUpdateId) {
                    if (in_array($quoteModel->status, SendUpdateLogStatusEnum::getSendUpdateBookingStatuses())) {
                        LoggerService::info("Master payment code: {$quoteModel->code} Quote status is already in the list of update booking queued, update booking failed or update booked, so skipping the update");
                    } else {
                        $quoteModel->status = SendUpdateLogStatusEnum::TRANSACTION_APPROVED;
                        LoggerService::info("Master payment code: {$quoteModel->code} Quote status updated to Transaction Approved for send update");
                    }
                } else {
                    $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($quoteModel);
                    LoggerService::info("Master payment code: {$quoteModel->code} Lock Lead status: {$lockLeadSectionsDetails['lead_status']} Quote Status ID: {$quoteModel->quote_status_id}");
                    if (! $lockLeadSectionsDetails['lead_status'] || $quoteModel->quote_status_id == QuoteStatusEnum::TransactionDeclined) {
                        $quoteModel->quote_status_id = QuoteStatusEnum::TransactionApproved;

                        app(CRUDService::class)->calculateScore($quoteModel, $modelType);
                        LoggerService::info("Master payment code: {$quoteModel->code} Transaction Score Calculated and quote status updated to Transaction Approved for main lead");
                    }
                }
                $quoteModel->save();
                LoggerService::info("Master payment code: {$quoteModel->code} - Old Quote Status: {$oldQuoteStatus} New Quote Status: {$quoteModel->quote_status_id} quote type id: {$quoteTypeId} and total payment count: {$totalPaymentsCount}");

                // Log for creating duplicate lead for TRAVEL
                LoggerService::info("Master payment code: {$quoteModel->code} Travel duplicate lead eligibility check - quoteTypeId: {$quoteTypeId} (Travel=".QuoteTypeId::Travel."), totalPaymentsCount: {$totalPaymentsCount}, sendUpdateId: {$sendUpdateId}, totalApproved: {$totalApproved}");
                if ($quoteTypeId == QuoteTypeId::Travel && $totalPaymentsCount > 1 && ! $sendUpdateId) {
                    $quoteStatusId = $quoteModel->quote_status_id;
                    LoggerService::info("Master payment code: {$quoteModel->code} Travel duplicate lead block entered - quoteStatusId: {$quoteStatusId}, insuranceProviderCode: {$masterPayment->insuranceProvider->code}, isFromJob: ".($isFromJob ? 'true' : 'false'));
                    if (
                        in_array($masterPayment->insuranceProvider->code, [InsuranceProviderEnum::QIC->value, InsuranceProviderEnum::DIC->value]) &&
                        $isFromJob &&
                        $totalApproved != $totalPaymentsCount
                    ) {
                        $quoteStatusId = QuoteStatusEnum::PaymentPending;
                        LoggerService::info("Master payment code: {$quoteModel->code} QIC, DIC partial approval - overriding quoteStatusId to PaymentPending, totalApproved: {$totalApproved}, totalPaymentsCount: {$totalPaymentsCount}");
                    }
                    if (app(TravelQuoteService::class)->createDuplicateLead($quoteModel, $quoteStatusId)) {
                        $successMessage .= ', '.$quoteModel->code.'-1 Created For Booking The Additional Policy';
                        LoggerService::info("Master payment code: {$quoteModel->code} Duplicate lead created for Quote Code: {$quoteModel->code}-1");
                    }
                }
            }
            if (! $sendUpdateId) {
                $this->updateLeadStatus($masterPayment);
                LoggerService::info("Master payment code: {$quoteModel->code} Lead status updated for quote according to payment status");
            }

            if ($isFromJob && $splitPaymentId > 0) {
                CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::SUCCESS, 'message' => PaymentProcessJobEnum::SUCCESS_MESSAGE]);
                LoggerService::info("Master payment code: {$quoteModel->code} Payment Process Job updated to SUCCESS");
            }
            DB::commit();
        } catch (\Exception $exception) {
            if ($isFromJob && $splitPaymentId > 0) {
                CcPaymentProcess::where('payment_splits_id', $splitPaymentId)->update(['status' => PaymentProcessJobEnum::FAILED, 'message' => $exception->getMessage()]);
                LoggerService::error('Master payment code: '.$quoteModel->code.' Payment Process Job failed for Split Payment ID: '.$splitPaymentId, exception: $exception);
                $this->handleAutomationError($quoteModel, $modelType, $masterPayment);
            }
            LoggerService::error('Error in processMasterPaymentApprove for Quote Code', exception: $exception);
            DB::rollBack();
        }

        return $successMessage;
    }

    // Update lead status for ecomm quotes
    public function updateLeadStatus($payment)
    {
        $quoteTypeId = null;
        $quoteModel = $payment->paymentable;
        $ecommQuotes = [
            CarQuote::class,
            HealthQuote::class,
            TravelQuote::class,
        ];
        if ($quoteModel) {
            $oldPaymentStatus = $quoteModel->payment_status_id;
            $quoteModel->payment_status_id = $payment->payment_status_id;
            if ($payment->paymentable_type == PersonalQuote::class) {
                $quoteTypeId = $quoteModel->quote_type_id;
            }
            if ((in_array($payment->paymentable_type, $ecommQuotes) || in_array($quoteTypeId, [QuoteTypeId::Life, QuoteTypeId::Cyber])) && $payment->payment_status_id == PaymentStatusEnum::PAID) {
                $quoteModel->payment_paid_at = now();
                LoggerService::info("Master payment code: {$payment->code} - Quote type: {$payment->paymentable_type}");

                // Update lead source for revival quotes after payment is paid
                $isRevival = $quoteModel->source == LeadSourceEnum::REVIVAL || $quoteModel->source == LeadSourceEnum::REVIVAL_REPLIED;
                $payment->paymentable_type == HealthQuote::class && $isRevival && $quoteModel->source = LeadSourceEnum::REVIVAL_PAID;
            }
            $quoteModel->save();
            // Log after successfully saving the quote model
            LoggerService::info("Master payment code: {$payment->code} quote payment status updated from {$oldPaymentStatus} to {$payment->payment_status_id}");
        }
    }

    public function updateCommissionSchedule($payment)
    {
        $paymentSplits = $payment->paymentSplits;
        $commissionSplitSumWithoutLastSplit = 0;
        $commission = $payment->commission_vat_applicable ?: $payment->commission_vat_not_applicable;
        $maxRetries = 5;

        $response = $this->handleWithDeadlockRetries(function () use ($payment, $paymentSplits, $commission, $commissionSplitSumWithoutLastSplit) {
            LoggerService::info('Starting commission split calculation', extra: [
                'payment_id' => $payment->id,
                'total_commission' => $commission,
                'total_payment_splits' => count($paymentSplits),
                'initial_commission_split_sum' => $commissionSplitSumWithoutLastSplit,
                'commission_vat' => $payment->commission_vat,
            ]);

            foreach ($paymentSplits as $paymentSplit) {
                $commissionSplitAmount = $this->calculateCommissionSplit($payment, $paymentSplit);

                LoggerService::info('Processing payment split', extra: [
                    'payment_id' => $payment->id,
                    'payment_split_id' => $paymentSplit->id,
                    'sr_no' => $paymentSplit->sr_no,
                    'calculated_commission_split' => $commissionSplitAmount,
                    'current_commission_split_sum' => $commissionSplitSumWithoutLastSplit,
                ]);

                /* to prevent difference in amount due to rounding number, sum all the Commission Split Amount except the last one,
                 and then subtract that amount from the total commission without vat and use the result as commission for last commission split */
                if ($paymentSplit->sr_no == count($paymentSplits)) {
                    $originalCommissionSplitAmount = $commissionSplitAmount;
                    $commissionSplitAmount = (float) sprintf(
                        '%.2f',
                        $commission - $commissionSplitSumWithoutLastSplit
                    );

                    LoggerService::info('Last payment split - adjusting for rounding', extra: [
                        'payment_id' => $payment->id,
                        'payment_split_id' => $paymentSplit->id,
                        'sr_no' => $paymentSplit->sr_no,
                        'original_commission_split' => $originalCommissionSplitAmount,
                        'adjusted_commission_split' => $commissionSplitAmount,
                        'total_commission' => $commission,
                        'commission_split_sum_without_last' => $commissionSplitSumWithoutLastSplit,
                        'difference' => $commissionSplitAmount - $originalCommissionSplitAmount,
                    ]);
                } else {
                    $commissionSplitSumWithoutLastSplit += $commissionSplitAmount;

                    LoggerService::info('Accumulated commission split sum', extra: [
                        'payment_id' => $payment->id,
                        'payment_split_id' => $paymentSplit->id,
                        'sr_no' => $paymentSplit->sr_no,
                        'added_amount' => $commissionSplitAmount,
                        'new_total_sum' => $commissionSplitSumWithoutLastSplit,
                    ]);
                }

                $paymentSplit->commission_vat_applicable = $commissionSplitAmount;
                /* Add Vat on commission to the first Installment of commission */
                $paymentSplit->commission_vat = $paymentSplit->sr_no == 1 ? $payment->commission_vat : 0;

                LoggerService::info('Saving payment split with commission values', extra: [
                    'payment_id' => $payment->id,
                    'payment_split_id' => $paymentSplit->id,
                    'sr_no' => $paymentSplit->sr_no,
                    'commission_vat_applicable' => $paymentSplit->commission_vat_applicable,
                    'commission_vat' => $paymentSplit->commission_vat,
                    'is_first_split' => $paymentSplit->sr_no == 1,
                ]);

                $paymentSplit->save();
            }

            LoggerService::info('Completed commission split calculation', extra: [
                'payment_id' => $payment->id,
                'total_commission' => $commission,
                'final_commission_split_sum' => $commissionSplitSumWithoutLastSplit,
            ]);
        }, $maxRetries);

        if (isset($response['status']) && in_array($response['status'], [GenericRequestEnum::FAILED, GenericRequestEnum::ERROR])) {
            info(self::class.' : updateCommissionSchedule - Payment Code: '.$payment->code.' - Failed to update Commission Split Schedule with error: '.$response['message']);

            return ['status' => false, 'message' => $response['message'] ?? 'Failed to update Commission Split Schedule.'];
        }
        info(self::class.' : updateCommissionSchedule - Payment Code: '.$payment->code.' - Commission Split Schedule updated successfully');

        return ['status' => true, 'message' => 'Commission Split Schedule updated successfully.'];
    }

    private function calculateCommissionSplit($payment, $paymentSplit)
    {
        $commission = $payment->commission_vat_applicable ?: $payment->commission_vat_not_applicable;
        $totalPriceVatApplicable = $payment->paymentSplits()->sum('price_vat_applicable');
        if ($totalPriceVatApplicable == 0) {
            $totalPriceVatApplicable = 1;
        }
        LoggerService::info('fn: calculateCommissionSplit - Payment Code: '.$payment->code.' - Total Price Vat Applicable: '.$totalPriceVatApplicable);

        return roundNumber(($paymentSplit->price_vat_applicable / $totalPriceVatApplicable) * $commission);
    }

    // function to calculate the price vat
    public function calculatePriceAndVat($frequency, $masterTotalPrice, $splitPaymentNumber, $splitPaymentAmount, $modelType, $quoteId, $totalSplitPayments, $paymentCode, $send_update_id = null)
    {
        $priceWithoutVat = $splitPaymentAmount;
        $vat = 0;
        $priceVatNotApplicable = 0;

        $vatValue = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::VAT_VALUE);
        if (! $vatValue) {
            return [$priceWithoutVat, $vat];
        }
        [$priceWithoutVat, $vat] = $this->calculateMasterPriceAndVat($masterTotalPrice, $modelType, $quoteId, $paymentCode, $send_update_id);
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

        return [round($priceWithoutVat, 2), round($vat, 2)];
    }

    public function calculateMasterPriceAndVat($masterTotalPrice, $modelType, $quoteId, $paymentCode, $send_update_id = null)
    {
        $vat = 0;
        $priceWithoutVat = $masterTotalPrice;
        $priceVatNotApplicable = 0;
        $vatValue = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::VAT_VALUE);

        if (! $vatValue) {
            LoggerService::info('SplitPaymentService - No VAT value found in application storage payment code: '.$paymentCode, extra: [
                'masterTotalPrice' => $masterTotalPrice,
            ]);

            return [$priceWithoutVat, $vat];
        }

        $computedPrice = 0;

        $ecommLobs = [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel, quoteTypeCode::Bike, quoteTypeCode::Home, quoteTypeCode::CYBER, quoteTypeCode::Device];
        $noVatLobs = [quoteTypeCode::Life, quoteTypeCode::SAVINGS];

        if ($send_update_id > 0) {
            $quoteModel = SendUpdateLogRepository::getLogById($send_update_id);
            LoggerService::info('SplitPaymentService - Processing send update log for payment code: '.$paymentCode);
        } else {
            if (in_array($modelType, $ecommLobs)) {
                $computedPrice = $masterTotalPrice;
                LoggerService::info('SplitPaymentService - Processing ecommLob quote for payment code: '.$paymentCode, extra: [
                    'modelType' => $modelType,
                    'computedPrice' => $computedPrice,
                ]);
            } else {
                $quoteModel = $this->getQuoteObject($modelType, $quoteId);
                LoggerService::info('SplitPaymentService - Processing non-ecommLob quote for payment code: '.$paymentCode, extra: [
                    'modelType' => $modelType,
                ]);
            }
        }

        // For Life and Savings LOBs - use masterTotalPrice directly, no VAT
        if (in_array($modelType, $noVatLobs) && $masterTotalPrice > 0) {
            $computedPrice = $masterTotalPrice;
            $priceVatNotApplicable = 0;
        } elseif (isset($quoteModel)) {
            if (isset($quoteModel->price_vat_applicable) && $quoteModel->price_vat_applicable > 0) {
                $computedPrice = $quoteModel->price_vat_applicable;
                LoggerService::info('SplitPaymentService - Using price_vat_applicable from quote for payment code: '.$paymentCode, extra: [
                    'price_vat_applicable' => $quoteModel->price_vat_applicable,
                ]);
            }

            if (isset($quoteModel->price_vat_not_applicable) && $quoteModel->price_vat_not_applicable > 0) {
                $priceVatNotApplicable = $quoteModel->price_vat_not_applicable;
                LoggerService::info('SplitPaymentService - Using price_vat_not_applicable from payment code: '.$paymentCode, extra: [
                    'price_vat_not_applicable' => $quoteModel->price_vat_not_applicable,
                ]);
            }

            if ($send_update_id > 0 && isset($quoteModel->price_with_vat) && $quoteModel->price_with_vat > 0) {
                $computedPrice = $quoteModel->price_with_vat;
                LoggerService::info('SplitPaymentService - Using price_with_vat from send update log for payment code: '.$paymentCode, extra: [
                    'price_with_vat' => $quoteModel->price_with_vat,
                ]);
            }
        }

        if ($computedPrice > 0) {
            // For Life and Savings LOBs - no VAT calculation, price_vat_applicable = total_price
            if (in_array($modelType, $noVatLobs)) {
                $priceWithoutVat = $computedPrice;
                $vat = 0;
            } elseif (in_array($modelType, $ecommLobs) || $send_update_id > 0) {
                $priceWithoutVat = $computedPrice / (1 + ($vatValue / 100));
                $vat = $priceWithoutVat * $vatValue / 100;
                LoggerService::info('SplitPaymentService - ecommLob VAT calculation for payment code: '.$paymentCode, extra: [
                    'modelType' => $modelType,
                    'priceWithoutVat' => $priceWithoutVat,
                    'vat' => $vat,
                ]);
            } else {
                $priceWithoutVat = $computedPrice;
                $vat = ($priceWithoutVat * $vatValue) / 100;
                LoggerService::info('SplitPaymentService - non-ecommLob VAT calculation for payment code: '.$paymentCode, extra: [
                    'modelType' => $modelType,
                    'priceWithoutVat' => $priceWithoutVat,
                    'vat' => $vat,
                ]);
            }

            $priceWithoutVat = $priceWithoutVat + $priceVatNotApplicable;
            LoggerService::info('SplitPaymentService - Final price after adding non-applicable VAT amount for payment code: '.$paymentCode, extra: [
                'final_priceWithoutVat' => $priceWithoutVat,
                'priceVatNotApplicable' => $priceVatNotApplicable,
                'vat' => $vat,
            ]);

            return [round($priceWithoutVat, 2), round($vat, 2)];
        }

        LoggerService::info('SplitPaymentService - No computed price available, using default values for payment code: '.$paymentCode, extra: [
            'priceWithoutVat' => $priceWithoutVat,
            'vat' => $vat,
        ]);

        return [round($priceWithoutVat, 2), round($vat, 2)];
    }

    // function to delete split payment
    public function deleteSplitPayment($splitPaymentId, $code)
    {
        $maxRetries = 2;
        $paymentSplit = PaymentSplits::find($splitPaymentId);
        $masterPayment = $paymentSplit->payment;
        $this->handleWithDeadlockRetries(function () use ($paymentSplit, $masterPayment, $code) {
            LoggerService::info("Processing delete split payment with payment split code: {$code}");
            $this->deletePaymentSplit($paymentSplit);
            $this->updateMasterPayment($masterPayment);
        }, $maxRetries);
        LoggerService::info("Delete split payment completed for payment split code: {$code}");
    }

    private function updateMasterPayment($masterPayment)
    {
        // Get active payment splits only
        $activeSplits = $masterPayment->paymentSplits()->get();
        $totalSplits = $activeSplits->count();

        if ($totalSplits === 1) {
            $this->updateMasterPaymentForSingleSplit($masterPayment, $activeSplits->first());
        } elseif ($totalSplits > 1) {
            $this->updateMasterPaymentForMultipleSplits($masterPayment, $totalSplits);
        }

        // Calculate total amount only from active splits
        $masterPayment->total_amount = $activeSplits->sum('payment_amount');
        $masterPayment->saveQuietly();

        LoggerService::info('Updated Master Payment For Code: '.$masterPayment->code.' with new total payments: '.$masterPayment->total_payments.' and frequency: '.$masterPayment->frequency);
    }

    private function updateMasterPaymentForSingleSplit($masterPayment, $remainingSplit)
    {
        $masterPayment->total_payments = 1;
        $masterPayment->frequency = PaymentFrequency::UPFRONT;
        $masterPayment->payment_methods_code = $remainingSplit->payment_method;

        if ($remainingSplit->payment_status_id != PaymentStatusEnum::PAID) {
            $masterPayment->payment_status_id = $remainingSplit->payment_status_id;
        }
    }

    private function updateMasterPaymentForMultipleSplits($masterPayment)
    {
        $masterPayment->total_payments = $masterPayment->total_payments - 1;
        if ($masterPayment->frequency != PaymentFrequency::SPLIT_PAYMENTS) {
            $masterPayment->frequency = PaymentFrequency::CUSTOM;
        }
    }

    private function deletePaymentSplit($paymentSplit)
    {
        // Delete QuoteDocuments referencing the payment split
        $paymentSplit->documents()->forceDelete();
        LoggerService::info('Deleted QuoteDocuments for Payment Split ID: '.$paymentSplit->id);

        // Delete the payment split
        $paymentSplit->delete();
        LoggerService::info('Deleted Payment Split For Code: '.$paymentSplit->code.' Split Payment: '.$paymentSplit->id.'-'.$paymentSplit->sr_no);
    }

    /**
     * For each payment split, if the parent payment's frequency is UPFRONT and its status is PAID,
     * the method updates the payment split's payment amount to match the parent payment's total amount and logs this update.
     * If the collection amount is greater than or equal to the payment amount, the payment split's status is set to PAID, otherwise, it is set to PARTIALLY_PAID
     * This method trigger when policy details section update
     */
    public function updateSplitPaymentStatusAndAmount($payment, $isCreditCardEnabled = true)
    {
        LoggerService::info('fn:processMasterPayment - SplitPaymentService Quote Code: '.$payment->code.' fn: Updating child payment status');
        $paymentSplits = PaymentSplits::where('code', $payment->code)->get();
        if (! $paymentSplits->isEmpty()) {
            foreach ($paymentSplits as $paymentSplit) {
                info('Quote Code: '.$payment->code.' Updating TA for Split Payment frequency is : '.$payment->frequency.' and payment_status_id: '.$payment->payment_status_id);
                if ($payment->frequency == PaymentFrequency::UPFRONT && in_array($payment->payment_status_id, [PaymentStatusEnum::PAID, PaymentStatusEnum::NEW, PaymentStatusEnum::OVERDUE])) {
                    info('Quote Code: '.$payment->code.' Updating PA BTA: '.$paymentSplit->payment_amount.' WTA: '.$payment->total_amount);
                    if ($paymentSplit->payment_amount != $payment->total_amount) {
                        $paymentSplit->payment_amount = $payment->total_amount;
                        $paymentSplit->price_vat_applicable = $payment->price_vat_applicable;
                        $paymentSplit->price_vat = $payment->price_vat;
                    }
                }
                if (! ($paymentSplit->collection_amount == null || $paymentSplit->collection_amount == 0)) {
                    // Format both amounts to 2 decimal places
                    $collectionAmount = round($paymentSplit->collection_amount, 2);
                    $paymentAmount = round($paymentSplit->payment_amount, 2);

                    if ($collectionAmount >= $paymentAmount) {
                        $paymentSplit->payment_status_id = PaymentStatusEnum::PAID;
                    } else {
                        $paymentSplit->payment_status_id = PaymentStatusEnum::PARTIALLY_PAID;
                    }
                }
                if (! $isCreditCardEnabled && $paymentSplit->payment_method == PaymentMethodsEnum::CreditCard && $payment->isInsurerPayment() && ! in_array($paymentSplit->payment_status_id, [PaymentStatusEnum::PAID, PaymentStatusEnum::AUTHORISED])) {
                    $paymentSplit->payment_method = PaymentMethodsEnum::InsurerPayment;
                }
                if ($paymentSplit->isDirty()) {
                    PaymentSplits::withoutEvents(function () use ($paymentSplit) {
                        $paymentSplit->save();
                    });
                }
            }
        }
    }

    private function createPolicyIssuanceAutomation($quote, $quoteType, $payment)
    {
        try {
            LoggerService::info("createPolicyIssuanceAutomation called for quote: {$quote->code}");

            if ($payment?->send_update_log_id > 0) {
                LoggerService::info('Payment is from send update log - skipping policy issuance automation');

                return;
            }

            $insuranceProvider = getInsuranceProvider($payment, $quoteType);

            if (! $insuranceProvider) {
                LoggerService::info("No insurance provider found for quote: {$quote->code} - skipping policy issuance automation");

                return;
            }

            LoggerService::info("Insurance provider found: {$insuranceProvider->code} for quote: {$quote->code}");

            $insuranceProviderAutomation = (new PolicyIssuanceService)->init($quoteType, $insuranceProvider->code);

            if (! isset($insuranceProviderAutomation)) {
                LoggerService::info("Insurance provider automation not available for {$insuranceProvider->code} - quote: {$quote->code}");

                return;
            }

            LoggerService::info("Insurance provider automation initialized for {$insuranceProvider->code} - quote: {$quote->code}");

            // Check without triggering lazy load
            $hasExistingStatus = ! is_null($quote->getAttributeValue('insurer_api_status_id'));
            LoggerService::info("Checking existing status for quote: {$quote->code} - hasExistingStatus: ".($hasExistingStatus ? 'true' : 'false'));

            if ($hasExistingStatus) {
                LoggerService::info("Quote {$quote->code} already has insurer_api_status_id - skipping policy issuance");

                return;
            }

            LoggerService::info("schedulePolicyIssuance for quote: {$quote->code}");
            $insuranceProviderAutomation?->createPolicyIssuanceSchedule($quote, $insuranceProvider);
            LoggerService::info("schedulePolicyIssuance completed for quote: {$quote->code}");
        } catch (\Exception $e) {
            LoggerService::error("Exception in createPolicyIssuanceAutomation for quote: {$quote->code}", exception: $e);
        }
    }

    private function handleAutomationError($quote, $quoteType, $payment)
    {
        $insuranceProvider = getInsuranceProvider($payment, $quoteType);
        if ($insuranceProvider) {
            $insuranceProviderAutomation = (new PolicyIssuanceService)->init($quoteType, $insuranceProvider->code);
            $shouldUpdateAPIIssuanceAndInsurerStatus = (new PolicyIssuanceService)->shouldUpdateAPIIssuanceAndInsurerStatus($quoteType, $insuranceProvider);
            if ($quoteType === QuoteTypes::DEVICE->value && $shouldUpdateAPIIssuanceAndInsurerStatus) {
                app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, $quoteType, PolicyIssuanceEnum::AUTO_CAPTURE_FAILED_STATUS_ID, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID, PolicyIssuanceEnum::PROCESS_INVOLVED_PAYMENT_CAPTURE);
            } elseif (($quoteType === QuoteTypes::CAR->value && in_array($insuranceProvider->code, [InsuranceProvidersEnum::AXA])) || $shouldUpdateAPIIssuanceAndInsurerStatus) {
                app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, $quoteType, PolicyIssuanceEnum::AUTO_CAPTURE_FAILED_STATUS_ID, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);
            } else {
                // TODO:: This should be updated with the new function in PolicyIssuanceService
                $insuranceProviderAutomation?->updateQuoteApiIssuanceStatusAndAllocate($quote, PolicyIssuanceEnum::AUTO_CAPTURE_FAILED_STATUS_ID, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);
            }
        }
    }
    /**
     * Check if the commission fields in booking details section is disabled
     *
     * @return array
     */
    public function checkCommissionStatus($payment)
    {
        if (isTapEnabled() && $payment && $payment->isInsurerPayment()) {
            $paymentSplits = $payment->paymentSplits;
            if ($paymentSplits->isNotEmpty()) {
                $hasPaidCreditCardPayment = $paymentSplits->contains(function ($split) {
                    return $split->payment_method == PaymentMethodsEnum::CreditCard && $split->payment_status_id == PaymentStatusEnum::PAID;
                });
                if (! $hasPaidCreditCardPayment) {
                    return [
                        'isCommissionDisabled' => true,
                        'disabledCommissionTooltip' => PaymentTooltip::DISABLED_COMMISSION,
                    ];
                }
            }
        }

        return ['isCommissionDisabled' => false, 'disabledCommissionTooltip' => ''];
    }

    private function shouldProcessPayment($paymentSplit, $isFromJob, $modelType): bool
    {
        $paymentCode = $paymentSplit->code;
        $payment = $paymentSplit->payment;
        $insuranceProvider = $payment->insuranceProvider->code ?? null;
        $paymentNotApproved = ($modelType == QuoteTypes::TRAVEL->value && $insuranceProvider == InsuranceProvidersEnum::QIC) ? ! $payment->is_approved : true;

        LoggerService::info(
            "Evaluating shouldProcessPayment for split payment Code: {$paymentCode}", [
                'isFromJob' => $isFromJob ? 'true' : 'false',
                'modelType' => $modelType,
                'paymentNotApproved' => $paymentNotApproved ? 'true' : 'false',
                'insuranceProvider' => $insuranceProvider,
            ]
        );

        // Travel / Car / Health (TCH) — split-payment job gate for these LOBs
        $travelCarHealthModelTypes = [
            QuoteTypes::TRAVEL->value,
            QuoteTypes::CAR->value,
            QuoteTypes::HEALTH->value,
        ];
        $isTchQuote = in_array($modelType, $travelCarHealthModelTypes);
        LoggerService::info(
            "Split payment Code: {$paymentCode} isTchQuote: ".($isTchQuote ? 'true' : 'false'),
            ['travelCarHealthModelTypes' => $travelCarHealthModelTypes]
        );

        // Split CC job: insurer codes that gate processing with TCH (QIC, AXA, RSA, ADNIC)
        $allowedProviders = [
            InsuranceProvidersEnum::QIC,
            InsuranceProvidersEnum::AXA,
            InsuranceProvidersEnum::RSA,
            InsuranceProvidersEnum::ADNIC,
            InsuranceProvidersEnum::DIC,
        ];
        $isAllowedProvider = in_array($insuranceProvider, $allowedProviders);

        // check if cyber quote
        $isCyberQuote = $modelType == QuoteTypes::CYBER->value;
        $isAwni = $insuranceProvider == InsuranceProvidersEnum::AWNI;

        $isDeviceQuote = $modelType == QuoteTypes::DEVICE->value;
        $isNgi = $insuranceProvider == InsuranceProvidersEnum::NGI;

        LoggerService::info("Split payment Code: {$paymentCode} isCyberQuote: ".($isCyberQuote ? 'true' : 'false').' isAwni: '.($isAwni ? 'true' : 'false').' isDeviceQuote: '.($isDeviceQuote ? 'true' : 'false').' isNgi: '.($isNgi ? 'true' : 'false'));

        // Only process if payment is not approved and:
        // - not from job, or
        // - from job AND is Travel/Car/Health AND provider is QIC/AXA/RSA/ADNIC
        // - from job AND is Cyber AND provider is AWNI
        $shouldProcess = $paymentNotApproved && (
            ! $isFromJob ||
            ($isTchQuote && $isAllowedProvider) ||
            ($isDeviceQuote && $isNgi) ||
            ($isCyberQuote && $isAwni)
        );

        LoggerService::info("Split payment Code: {$paymentCode} shouldProcess: ".($shouldProcess ? 'true' : 'false'));

        return $shouldProcess;
    }

    private function shouldCreateReceipt($parentPayment, $paymentSplit): bool
    {
        return $parentPayment->collection_type == CollectionTypeEnum::BROKER &&
            ! in_array($paymentSplit->payment_method, [
                PaymentMethodsEnum::CreditCard,
                PaymentMethodsEnum::CreditApproval,
            ]) &&
            in_array($paymentSplit->payment_status_id, [
                PaymentStatusEnum::PAID,
                PaymentStatusEnum::PARTIALLY_PAID,
            ]);
    }
}
