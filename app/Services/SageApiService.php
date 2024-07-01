<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\SageEnum;
use App\Factories\SagePayloadFactory;
use App\Jobs\BookPolicyOnSageJob;
use App\Models\ApplicationStorage;
use App\Models\BusinessInsuranceType;
use App\Models\Customer;
use App\Models\Lookup;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\SageApiLog;
use App\Models\SendUpdateLog;
use App\Models\User;
use App\Repositories\PaymentRepository;
use App\Repositories\SageApiLogRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\SageLoggable;
use App\Traits\TeamHierarchyTrait;

class SageApiService
{
    use GenericQueriesAllLobs;
    use SageLoggable, TeamHierarchyTrait;

    protected $sageLogin;
    protected $sagePassword;
    protected $sageRequestUrl;
    protected $sageBatchNumber;
    protected $recursiveCallStatus;

    public function __construct()
    {
        //Guzzle was not working for post request
        $this->sageLogin = env('SAGE_300_LOGIN');
        $this->sagePassword = env('SAGE_300_PASSWORD');
        $this->sageRequestUrl = env('SAGE_300_BASE_URL').env('SAGE_300_VERSION');
        $this->sageBatchNumber = '';
        $this->recursiveCallStatus = SageEnum::STATUS_SUCCESS;
    }

    // This payload moved to SagePayloadFactory
    public static function sagePayLoad($modelType, $payment, $quote, $paymentSplits)
    {
        $firstChildPayment = $paymentSplits->first();
        $insuredFullName = $quote?->customer?->insured_first_name.' '.$quote?->customer?->insured_last_name;
        $latestEndorsementCode = '';
        if ($quote->personal_quote_id) {
            $latestEndorsement = SendUpdateLogRepository::endorsementsByPersonalQuoteId($quote->personal_quote_id)->first();
            $latestEndorsementCode = $latestEndorsement->code;
        }

        $businessTypeOfInsuranceCode = '';
        if ($quote->business_type_of_insurance_id) {
            $businessTypeOfInsurance = BusinessInsuranceType::find($quote->business_type_of_insurance_id);
            $businessTypeOfInsuranceCode = $businessTypeOfInsurance->code;
        }

        $sageRequest = new \stdClass();

        // $sageRequest->discount = 2;
        $sageRequest->discount = floatval($payment->discount_value);
        $sageRequest->invoiceDescription = $payment->invoice_description;
        $sageRequest->bookingDate = date('Y-m-d', strtotime($quote['policy_booking_date']));
        $sageRequest->policyBookingDate = date('Ymd', strtotime($quote['policy_booking_date']));
        $sageRequest->policyExpiryDate = date('Ymd', strtotime($quote['renewal_expiry_date']));
        $sageRequest->insurerInvoiceDate = date('Y-m-d', strtotime($payment->insurer_invoice_date));

        if (! empty($paymentSplits)) {
            $sageRequest->paymentDueDate = date('Y-m-d', strtotime($paymentSplits[0]['due_date']));
        }

        $sageRequest->mainClassInsurance = $modelType;
        $sageRequest->policyNumber = substr($quote->policy_number, 60);
        $sageRequest->originalPolicyNumber = $quote->policy_number;
        $sageRequest->policyIssuer = $payment->policyIssuer?->name ?? '';
        $sageRequest->requestType = Lookup::where('id', $quote->transaction_type_id)->first()->text ?? '';
        $sageRequest->subClass = $businessTypeOfInsuranceCode;
        $sageRequest->ccCode = $firstChildPayment->cc_payment_id ?? '';
        $sageRequest->isPostDatedCheck = $firstChildPayment->payment_method == PaymentMethodsEnum::PostDatedCheque ? 'Yes' : 'No';
        $sageRequest->checkDetails = $firstChildPayment->check_detail ?? '';
        $sageRequest->endorsementNumber = $latestEndorsementCode;
        $sageRequest->insured = $insuredFullName;
        $sageRequest->policyHolder = $insuredFullName;
        $sageRequest->premiumCollectedBy = ucfirst($payment->collection_type);

        $sageRequest->invoicePaymentStatus = $payment->payment_status_id;
        // $sageRequest->invoicePaymentStatus = 'paid';
        $advisorName = '';
        $managerName = '';
        if (! empty($quote->advisor_id)) {
            $advisor = User::where('id', $quote->advisor_id)->first();
            $advisorName = $advisor->name;
            $managerName = implode(',', getManagersByUser($advisor->id)->pluck('name')->toArray());
        }
        $sageRequest->advisorName = $advisorName;
        $sageRequest->manager = $managerName;

        //calculate vat
        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()?->value;
        $sageRequest->vatOnPremium = $vatPercentage && $quote->price_vat_applicable ? (($quote->price_vat_applicable * $vatPercentage) / 100) : 0;

        $sageRequest->premiumWithoutTax = floatval($quote->price_vat_applicable ?? 0) + floatval($quote->price_vat_not_applicable ?? 0);
        $sageRequest->premiumWithTax = floatval($quote->price_with_vat);
        $sageRequest->vatOnCommission = floatval($payment->commission_vat);
        $sageRequest->totalAmount = floatval($payment->total_amount);
        $sageRequest->totalPrice = floatval($payment->total_price);
        $sageRequest->commission = floatval($payment->commission);
        $sageRequest->commissionIncludingVat = floatval($payment->commission_vat_applicable);
        $sageRequest->commissionWithOutVat = $payment->commission_vat_not_applicable ? floatval($payment->commission_vat_not_applicable) : floatval($payment->commission_without_vat);
        $sageRequest->commissionPercentage = strval($payment->commmission_percentage);

        // Slice the last 18 characters from the string to avoid sage document number length issue and store the original values in optional fields
        $sageRequest->insurerPremiumNumber = (string) substr($payment['insurer_tax_number'], -18);
        $sageRequest->insurerCommissionNumber = (string) substr($payment['insurer_commmission_invoice_number'], -18);
        $sageRequest->originalInsurerPremiumNumber = (string) $payment['insurer_tax_number'];
        $sageRequest->originalInsurerCommissionNumber = (string) $payment['insurer_commmission_invoice_number'];

        if (count($paymentSplits) == 1) {
            $sageRequest->sage_reciept_id = $paymentSplits[0]['sage_reciept_id'];
            $sageRequest->collection_amount = $paymentSplits[0]['collection_amount'] + $sageRequest->discount;
        } else {
            $sageRequest->invoicePaymentStatus = $paymentSplits[0]['payment_status_id'];
        }

        //Insurer GL Account and Vendor Number
        $sageRequest->insurerGlLiaiblityAccount = $payment->insuranceProvider?->gl_liaiblity_account;
        $sageRequest->sageVenderId = $payment->insuranceProvider?->sage_vendor_id;
        $sageRequest->sageInsurerCustomerId = $payment->insuranceProvider?->sage_insurer_customer_id;

        return $sageRequest;
    }

    // Code Refactor, Old function verifySageCustomer updated function sageCustomer
    public function sageCustomer($quoteTypeId, $quote, $totalSteps = 4)
    {
        $sageCustomerNumber = false;
        $customer = Customer::where('id', $quote->customer_id)->first();

        if ($customer) {
            $response = '';
            $customer->data = ['quoteTypeId' => $quoteTypeId, 'id' => $quote->id];
            $sageLogArray = $quote->sageApiLogs->keyBy('step')->toArray();
            $customerPayload = [
                'sage_request_type' => SageEnum::SRT_CREATE_CUSTOMER,
                'entry_type' => SageEnum::SCT_STRAIGHT,
                'endPoint' => SageEnum::END_POINT_AR_CUSTOMER,
                'payload' => [],
            ];

            if ($customer->sage_customer_number) {
                $this->logSageApiCall($customerPayload, $response, $quote, 1, $totalSteps);
                $sageCustomerNumber = $customer->sage_customer_number;
            } else {
                $isLiveApiCallStep1 = true;
                $sageSecondLog = isset($sageLogArray[1]) ? $sageLogArray[1] : false;

                if ($sageSecondLog && $sageSecondLog['status'] == SageEnum::STATUS_SUCCESS) {
                    $isLiveApiCallStep1 = false;
                    $response = json_decode($sageSecondLog['response'], true);
                } else {
                    $customerPayload = SagePayloadFactory::createCustomerPayload($customer);
                    $curlResponse = $this->postToSage300($customerPayload['endPoint'], $customerPayload['payload']);
                    $response = json_decode($curlResponse, true);
                }
                $responseError = isset($response['error']['code']) ? $response['error']['code'] : false;
                if ($responseError && $responseError == SageEnum::ERROR_RECORD_DUPLICATE) {
                    $sageCustomerNumber = $customerPayload['customerNumber'];
                } elseif (isset($response['CustomerNumber'])) {
                    $sageCustomerNumber = $response['customerNumber'];
                }

                if ($sageCustomerNumber) {
                    if ($isLiveApiCallStep1) {
                        $this->logSageApiCall($customerPayload, $response, $quote, 1, $totalSteps);
                    }
                    unset($customer->data);
                    $customer->sage_customer_number = $sageCustomerNumber;
                    $customer->save();
                } else {
                    $this->logSageApiCall($customerPayload, $response, $quote, 1, $totalSteps, 'fail');
                }
            }
        }

        return $sageCustomerNumber;
    }

    public function verifySageCustomer($customerId, $data = null, $logModal = null, $sageLogArray = [], $totalSteps = 4)
    {
        $customer = Customer::find($customerId);
        $customer->data = ! empty($data) ? $data : [];
        $sageCustomerNumber = false;
        $payLoadOptions['endPoint'] = 'AR/ARCustomers';
        $payLoadOptions['payload'] = [];
        $response = '';
        if ($customer) {
            if ($customer->sage_customer_number) {
                $this->logSageApiCall($payLoadOptions, $response, $logModal, 1, $totalSteps);
                $sageCustomerNumber = $customer->sage_customer_number;
            } else {
                $isLiveApiCallStep1 = true;
                if (isset($sageLogArray[1]) && $sageLogArray[1]['status'] == config('constants.SAGE_LOG_SUCCESS_STATUS')) {
                    $isLiveApiCallStep1 = false;
                    $response = json_decode($sageLogArray[1]['response'], true);
                } else {
                    $payLoadOptions = SagePayloadFactory::createCustomerPayload($customer);
                    $jsonResponse = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
                    $response = json_decode($jsonResponse, true);
                }
                if (isset($response['error']['code']) && $response['error']['code'] == config('constants.SAGE_ERROR_DUPLICATE_CLIENT')) {
                    $sageCustomerNumber = $payLoadOptions['customerNumber'];
                } elseif (isset($response['CustomerNumber'])) {
                    $sageCustomerNumber = $response['CustomerNumber'];
                }
                // The customer already exists on Sage
                if (isset($sageLogArray[1]) && $sageCustomerNumber === false && $sageLogArray[1]['status'] != config('constants.SAGE_LOG_SUCCESS_STATUS')) {
                    $customerSageDbPayload = json_decode($sageLogArray[1]['sage_payload'], true);
                    if (isset($customerSageDbPayload['CustomerNumber'])) {
                        $sageCustomerNumber = $customerSageDbPayload['CustomerNumber'];
                    }
                }

                if ($sageCustomerNumber) {
                    if ($isLiveApiCallStep1) {
                        $this->logSageApiCall($payLoadOptions, $response, $logModal, 1, $totalSteps);
                    }
                    unset($customer->data);
                    $customer->sage_customer_number = $sageCustomerNumber;
                    $customer->save();
                } else {
                    $this->logSageApiCall($payLoadOptions, $response, $logModal, 1, $totalSteps, 'fail');
                }
            }
        }

        return $sageCustomerNumber;
    }

    public function postToSage300($endPoint, $payLoad, $verb = 'POST')
    {
        // Create the payload data for the POST request
        $sageEndPoint = $this->sageRequestUrl.$endPoint;
        //Http facade not giving expected response,so have to use curl
        $ch = curl_init($sageEndPoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($verb == 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        } elseif ($verb == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payLoad));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        // Add basic authentication
        curl_setopt($ch, CURLOPT_USERPWD, "$this->sageLogin:$this->sagePassword");
        $response = curl_exec($ch);

        if ($response === false || $response == '') {
            $errorResponse = curl_error($ch);
            $errorResponse = json_decode($errorResponse, true);
            // echo $errorResponse; exit;
            if (is_array($errorResponse)) {
                $httpCode = $errorResponse['error']['code'];

                if (isset($errorResponse['error']['message']['value'])) {
                    $errorMessage = $errorResponse['error']['message']['value'];
                } else {
                    $errorMessage = 'An error occurred';
                }
                $response = response()->json(['error' => $errorMessage, 'code' => $httpCode], $httpCode);
            }
            // else {
            //     $httpCode = 401;
            //     $response = response()->json(['error' => 'Verify sage api credentials', 'code' => $httpCode], $httpCode);
            // }
        }
        curl_close($ch);

        // Return response or handle errors
        return $response;
    }

    public function handleDocumentsToSage($request, $quote, $extras = [])
    {
        $customerTotalSteps = 4;
        $stepsAsPerType = [
            SageEnum::SUT_NORMAL => 13,
            SageEnum::SUT_REVE_CORR => 21,
        ];

        $isSageEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::SAGE_ENABLED);
        if (! $isSageEnabled) {
            info('Book Update - Sage300 is not enabled');

            return ['status' => false, 'message' => 'Sage300 is not enabled'];
        }

        // PT_SEND_UPDATE : Process Type Send Update
        if ($extras['type'] == SageEnum::PT_SEND_UPDATE) {
            $customerTotalSteps = in_array($extras['send_update_type'], array_keys($stepsAsPerType)) ? $stepsAsPerType[$extras['send_update_type']] : 4;
        }

        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($request->quoteType));
        $quoteModel = $this->getModelObject($request->quoteType);
        $sageCustomerNumber = $this->sageCustomer($quoteTypeId, $quote, $customerTotalSteps);
        $quote->quoteTypeObject = $quoteModel;
        $quoteDetails = $quote;

        if ($sageCustomerNumber) {
            info('Book Update - Customer found in Sage300 - Customer Number: '.$sageCustomerNumber.' - QuoteType: '.$request->quoteType.' - QuoteUUID: '.$request->quoteUuid.' - SendUpdateUUID: '.$extras['send_update_log']->uuid);
            $response = '';
            $getingPaymentDetails = $this->getPaymentDetails($request, $extras);

            if ($extras['type'] == SageEnum::PT_SEND_UPDATE) {
                $sendUpdateLog = SendUpdateLog::where('id', $request->sendUpdateId)->first();
                $quoteDetails = [
                    'policy_booking_date' => $sendUpdateLog->booking_date,
                    'renewal_expiry_date' => $sendUpdateLog->expiry_date,
                    'policy_number' => $sendUpdateLog->policy_number,
                    'transaction_type_id' => $quote->transaction_type_id,
                    'advisor_id' => $sendUpdateLog->advisor_id,
                    'price_vat_applicable' => $getingPaymentDetails['payment']->total_price,
                    'price_with_vat' => $getingPaymentDetails['payment']->total_amount,
                ];

                if (isset($getingPaymentDetails['mainLeadDetails'])) {
                    $extras['mainLeadDetails'] = $getingPaymentDetails['mainLeadDetails'];
                }
            }

            $sageRequestPayload = SagePayloadFactory::sagePayLoad($request->quoteType, $quoteDetails, $getingPaymentDetails['payment'], $getingPaymentDetails['splitPayments']);
            $sageRequestPayload->customerId = $sageCustomerNumber;

            if (! $sageRequestPayload->insurerGlLiaiblityAccount || ! $sageRequestPayload->sageVenderId || ! $sageRequestPayload->sageInsurerCustomerId) {
                info('Book Update - Sage Vendor ID or GL Account for Insurance Provider or Sage Insurer Customer ID not found. QuoteType: '.$request->quoteType.' - QuoteUUID: '.$request->quoteUuid.' - SendUpdateUUID: '.$extras['send_update_log']->uuid);

                if (! $sageRequestPayload->insurerGlLiaiblityAccount && ! $sageRequestPayload->sageVenderId && ! $sageRequestPayload->sageInsurerCustomerId) {
                    return ['status' => false, 'message' => 'Sage Vendor ID, Sage Insurer Customer ID and GL Account for Insurance Provider not found.'];
                } elseif (! $sageRequestPayload->insurerGlLiaiblityAccount) {
                    return ['status' => false, 'message' => 'GL Account for Insurance Provider not found.'];
                } elseif (! $sageRequestPayload->sageVenderId) {
                    return ['status' => false, 'message' => 'Sage Vendor ID for Insurance Provider not found.'];
                } elseif (! $sageRequestPayload->sageInsurerCustomerId) {
                    return ['status' => false, 'message' => 'Sage Insurer Customer ID for Insurance Provider not found.'];
                }
            }

            switch ($extras['type']) {
                case SageEnum::PT_SEND_UPDATE:
                    if ($request->send_update_type == SageEnum::SUT_REVE_CORR) {
                        $extras['reverse_invoice'] = $request->reversalInvoice;
                    }
                    $response = $this->handleSendUpdateCalls($quote, $sageRequestPayload, $getingPaymentDetails['payment'], $getingPaymentDetails['splitPayments'], $extras);
                    break;
            }

            if (! empty($response)) {
                return ['status' => $response['status'], 'message' => $response['message']];
            }

            logger()->error('Book Update - Something went wrong');

            return ['status' => false, 'message' => 'Something went wrong'];
        }

        logger()->error('Book Update - Customer not found in Sage300. QuoteType: '.$request->quoteType.' - QuoteUUID: '.$request->quoteUuid.' - SendUpdateUUID: '.$extras['send_update_log']->uuid);

        return ['status' => false, 'message' => 'Customer not found in Sage300'];
    }

    private function getPaymentDetails($request, $extras)
    {
        $paymentClause = ($extras['type'] == SageEnum::PT_SEND_UPDATE) ? ['send_update_log_id' => $request->sendUpdateId] : ['code' => $request->code];
        $payment = Payment::where($paymentClause)->first();

        if ($extras['type'] !== SageEnum::PT_SEND_UPDATE) {
            $payment = Payment::where($paymentClause)->first();
            $splitPayments = PaymentSplits::where('code', $payment->code)->get();

            return ['payment' => $payment, 'splitPayments' => $splitPayments];
        } else {
            $payment = Payment::where($paymentClause)->first();
            if ($payment) {
                info('Book Update - Fetching Payment details from Send Update. QuoteType: '.$request->quoteType.' - QuoteUUID: '.$request->quoteUuid.' - SendUpdateUUID: '.$extras['send_update_log']->uuid);
                $splitPayments = PaymentSplits::where('code', $payment->code)->get();

                return ['payment' => $payment, 'splitPayments' => $splitPayments];
            } else {
                // If we don't have payment details then we fetched it from the Main Lead
                info('Book Update - Fetching Payment details from Main Lead. QuoteType: '.$request->quoteType.' - QuoteUUID: '.$request->quoteUuid.' - SendUpdateUUID: '.$extras['send_update_log']->uuid);
                $getQuoteDetails = $this->getQuoteObjectBy($request->quoteType, $request->quoteUuid, 'uuid');
                $getQuoteDetails->load(['payments' => function ($query) {
                    $query->whereNull('send_update_log_id');
                }, 'payments.paymentSplits']);

                $payment = $getQuoteDetails->payments->first();
                $splitPayments = $payment->paymentSplits;

                // Most CPD cases have no vaalue then should it set as Credit Note - Need to verify this with Denber
                $mainLeadDetails = [
                    'payment' => [
                        'insurer_tax_number' => $payment->insurer_tax_number,
                        'insurer_commmission_invoice_number' => $payment->insurer_commmission_invoice_number,
                    ],
                ];

                $payment->fill([
                    'discount_value' => $extras['send_update_log']->discount, // --
                    'invoice_description' => $extras['send_update_log']->invoice_description,
                    'insurer_invoice_date' => $extras['send_update_log']->invoice_date,
                    'commission_vat' => '', // Need to verify this field
                    'total_price' => $extras['send_update_log']->price_without_vat, // Need to verify this field
                    'total_amount' => $extras['send_update_log']->price_vat_applicable, // Need to verify this field
                    'commission' => $extras['send_update_log']->total_commission,
                    'commission_vat_applicable' => $extras['send_update_log']->commission_vat_applicable,
                    'commission_vat_not_applicable' => $extras['send_update_log']->commission_vat_not_applicable,
                    'commmission_percentage' => $extras['send_update_log']->commission_percentage,
                    'insurer_tax_number' => $extras['send_update_log']->insurer_tax_invoice_number,
                    'insurer_commmission_invoice_number' => $extras['send_update_log']->insurer_commission_invoice_number,
                    'policy_expiry_date' => $extras['send_update_log']->expiry_date,
                    'broker_invoice_number' => $extras['send_update_log']->broker_invoice_number,
                    'frequency' => PaymentFrequency::UPFRONT,
                ]);

                $splitPayments->first()->fill([
                    'due_date' => $extras['send_update_log']->invoice_date,
                    'payment_amount' => $extras['send_update_log']->price_vat_applicable, //+ abs($extras['send_update_log']->total_commission), // Need to verify with Denber
                    'collection_amount' => $extras['send_update_log']->price_vat_applicable, // Need to verify this field, I think we should add discount here
                ]);

                return ['payment' => $payment, 'splitPayments' => $splitPayments, 'mainLeadDetails' => $mainLeadDetails];
            }
        }
    }

    private function handleSendUpdateCalls($quote, $sageRequestPayload, $payment, $splitPayments, $extras)
    {
        $quoteModelObject = ! empty($extras['send_update_log']) ? $extras['send_update_log'] : $quote;

        if ($extras['send_update_type'] == SageEnum::SUT_REVE_CORR) {
            $getPaymentByInsurerInvoiceNumber = PaymentRepository::getPaymentByInsurerInvoiceNumber($quote, $extras['reverse_invoice']);
            if ($getPaymentByInsurerInvoiceNumber->send_update_log_id !== null) {
                $getReverseInvoiceRelation = [
                    'section_type' => $quoteModelObject->getMorphClass(),
                    'section_id' => $getPaymentByInsurerInvoiceNumber->send_update_log_id,
                ];
            } else {
                $getReverseInvoiceRelation = [
                    'section_type' => $getPaymentByInsurerInvoiceNumber->paymentable_type,
                    'section_id' => $getPaymentByInsurerInvoiceNumber->paymentable_id,
                ];
            }

            $sageLogArray = SageApiLog::where($getReverseInvoiceRelation)
                ->whereNotIn('entry_type', [
                    SageEnum::SRT_GET_AR_INVOICE,
                    SageEnum::SRT_GET_AP_INVOICE,
                    SageEnum::SCT_REVERSAL,
                    SageEnum::SCT_CORRECTION,
                ])->orderBy('step')->get()->toArray();
        } else {
            $sageLogArray = $quoteModelObject->sageApiLogs?->whereNotIn('entry_type', [
                SageEnum::SRT_GET_AR_INVOICE,
                SageEnum::SRT_GET_AP_INVOICE,
                SageEnum::SCT_REVERSAL,
                SageEnum::SCT_CORRECTION,
            ])->keyBy('step')->toArray();
        }

        switch ($extras['send_update_type']) {
            case SageEnum::SUT_NORMAL:
                info('Book Update - Sage300 APIs calls start for Straight Forward cases');
                $response = $this->handleSendUpdateNormalCalls($quote, $payment, $splitPayments, $sageRequestPayload, $sageLogArray, $extras);
                break;

            case SageEnum::SUT_REVE_CORR:
                info('Book Update - Sage300 APIs calls start for Reversal and Correction cases');
                $extras['sageLogArray'] = $sageLogArray;
                $sageRevCorrLogs = $quoteModelObject->sageApiLogs?->whereIn('entry_type', [
                    SageEnum::SRT_GET_AR_INVOICE,
                    SageEnum::SRT_GET_AP_INVOICE,
                    SageEnum::SCT_REVERSAL,
                    SageEnum::SCT_CORRECTION,
                ])->keyBy('step')->toArray();
                $response = $this->handleSendUpdateRevCorrCalls($quote, $payment, $splitPayments, $sageRequestPayload, $sageRevCorrLogs, $extras);
                break;
        }

        return $response;
    }

    private function handleSendUpdateNormalCalls($quote, $payment, $splitPayments, $sageRequestPayload, $sageLogArray, $extras)
    {
        $startingStep = 2;
        $totalSteps = 13;

        if ($payment->frequency == PaymentFrequency::UPFRONT) {
            info('Book Update - Creating AR Invoice and mark as posted');
            $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                'iterator' => 0,
                'lastIteration' => 2,
                'startingStep' => $startingStep,
                'totalSteps' => $totalSteps,
                'entryType' => SageEnum::SCT_STRAIGHT,
                'requestType' => SageEnum::SRT_CREATE_AR_PREM_COMM_INV,
                'sendUpdateLog' => $extras['send_update_log'] ?? [],
                'mainLeadDetails' => $extras['mainLeadDetails'] ?? [],
            ]);

            info('Book Update - Creating AP Invoice and mark as posted');
            $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                'iterator' => 0,
                'lastIteration' => 2,
                'startingStep' => ($startingStep + 3),
                'totalSteps' => $totalSteps,
                'entryType' => SageEnum::SCT_STRAIGHT,
                'requestType' => SageEnum::SRT_CREATE_AP_PREM_INV,
                'sendUpdateLog' => $extras['send_update_log'] ?? [],
                'mainLeadDetails' => $extras['mainLeadDetails'] ?? [],
            ]);

            $startingStep = 8;
            $totalSteps = 13;

        } else {
            info('Book Update - Creating AR Split Payment Invoice and mark as posted');
            $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                'iterator' => 0,
                'lastIteration' => 2,
                'startingStep' => $startingStep,
                'totalSteps' => $totalSteps,
                'entryType' => SageEnum::SCT_STRAIGHT,
                'requestType' => SageEnum::SRT_CREATE_AR_SPPAY_INV,
                'payment' => $payment,
                'splitPayments' => $splitPayments,
                'sendUpdateLog' => $extras['send_update_log'] ?? [],
            ]);

            info('Book Update - Creating AP Split Payment Invoice and mark as posted');
            $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                'iterator' => 0,
                'lastIteration' => $extras['ap_patch_call_enable'] ? 2 : 0,
                'startingStep' => ($startingStep + 4),
                'totalSteps' => $totalSteps,
                'entryType' => SageEnum::SCT_STRAIGHT,
                'requestType' => SageEnum::SRT_CREATE_AP_SPPAY_INV,
                'payment' => $payment,
                'splitPayments' => $splitPayments,
                'sendUpdateLog' => $extras['send_update_log'] ?? [],
                'apPatchCallEnable' => $extras['ap_patch_call_enable'],
            ]);

            $startingStep = 10;
        }

        if ($sageRequestPayload->discount > 0) {
            info('Book Update - Creating AR Discount Invoice and mark as posted');
            $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                'iterator' => 0,
                'lastIteration' => 2,
                'startingStep' => $startingStep,
                'totalSteps' => $totalSteps,
                'entryType' => SageEnum::SCT_STRAIGHT,
                'requestType' => SageEnum::SRT_CREATE_AR_DISC_INV,
                'sendUpdateLog' => $extras['send_update_log'] ?? [],
            ]);

            $totalSteps = $startingStep == 8 ? 13 : 15;
            $startingStep = ($startingStep + 3);
        }

        // For Apply Pre-payment adjustments they should do it manually on Sage
        // if ($payment->send_update_log_id !== null) {
        //     $totalSteps = 15;
        //     if (strtolower($sageRequestPayload->invoicePaymentStatus) == PaymentStatusEnum::PAID && $payment->send_update_log_id !== null) {
        //         if ($payment->frequency == PaymentFrequency::UPFRONT) {
        //             info('Book Update - Creating Apply Payment Invoices - Receipt One for Upfront Payment with Invoice Payment Status Paid');
        //             $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
        //                 'iterator' => 0,
        //                 'lastIteration' => 2,
        //                 'startingStep' => $startingStep,
        //                 'totalSteps' => $totalSteps,
        //                 'entryType' => SageEnum::SCT_STRAIGHT,
        //                 'requestType' => SageEnum::SRT_CREATE_PAY_REC_ONE_INV,
        //                 'payment' => $payment,
        //                 'splitPayments' => $splitPayments,
        //                 'sendUpdateLog' => $extras['send_update_log'] ?? [],
        //             ]);
        //         }
        //         // Apply prepayment mapping manually on sage
        //         elseif ($payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
        //             info('Book Update - Creating Apply Payment - AR Split Pre Payment for Split Payment with Invoice Payment Status Paid');
        //             $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
        //                 'iterator' => 0,
        //                 'lastIteration' => 2,
        //                 'startingStep' => $startingStep,
        //                 'totalSteps' => $totalSteps,
        //                 'entryType' => SageEnum::SCT_STRAIGHT,
        //                 'requestType' => SageEnum::SRT_CREATE_AR_SP_PRE_PAYMENT,
        //                 'payment' => $payment,
        //                 'splitPayments' => $splitPayments,
        //                 'sendUpdateLog' => $extras['send_update_log'] ?? [],
        //             ]);
        //         }
        //         $totalSteps = 18;
        //     }

        //     if (! in_array($payment->frequency, [PaymentFrequency::UPFRONT, PaymentFrequency::SPLIT_PAYMENTS]) && in_array($splitPayments[0]['payment_status_id'], [PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED])) {
        //         info('Book Update - Creating AR Split Pre Payment for Upfront/Split Payment with Payment Status Paid/Captured');
        //         $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
        //             'iterator' => 0,
        //             'lastIteration' => 2,
        //             'startingStep' => $startingStep,
        //             'totalSteps' => $totalSteps,
        //             'entryType' => SageEnum::SCT_STRAIGHT,
        //             'requestType' => SageEnum::SRT_CREATE_AR_SP_PRE_PAYMENT,
        //             'payment' => $payment,
        //             'splitPayments' => $splitPayments,
        //             'sendUpdateLog' => $extras['send_update_log'] ?? [],
        //         ]);
        //     }
        // }

        $response = ['status' => $_REQUEST['status'] ?? true, 'message' => $_REQUEST['message'] ?? 'Invoices created successfully'];

        return ['status' => $response['status'], 'message' => $response['message']];
    }

    private function handleSendUpdateRevCorrCalls($quote, $payment, $splitPayments, $sageRequestPayload, $sageLogArray, $extras)
    {
        $sendUpdateLog = $extras['send_update_log'];
        info('Book Update - Fetching Invoices for Reverse and Correction from Sage APIs Logs');
        $invoicesForReverse = collect($extras['sageLogArray'])->filter(function ($sageApiLog) {
            return in_array($sageApiLog['sage_request_type'], [
                SageEnum::SRT_CREATE_AR_PREM_COMM_INV,
                SageEnum::SRT_CREATE_AR_SPPAY_INV,
                SageEnum::SRT_CREATE_AP_PREM_INV,
                SageEnum::SRT_CREATE_AP_SPPAY_INV,
                SageEnum::SRT_CREATE_AR_DISC_INV,
            ]) && $sageApiLog['status'] == 'success';
        })->values()->toArray();

        if (empty($invoicesForReverse)) {
            info('Book Update - No Invoices found for Reverse and Correction');

            return ['status' => false, 'message' => 'No Invoices found for Reverse and Correction'];
        }
        $reverseSendUpdateTypes = collect($invoicesForReverse)->pluck('sage_request_type')->toArray();
        $checkARInvoices = [SageEnum::SRT_CREATE_AR_PREM_COMM_INV, SageEnum::SRT_CREATE_AR_SPPAY_INV];
        $checkAPInvoices = [SageEnum::SRT_CREATE_AP_PREM_INV, SageEnum::SRT_CREATE_AP_SPPAY_INV];
        foreach ($reverseSendUpdateTypes as $reverseSendUpdateTypeKey => $reverseSendUpdateType) {
            $invoiceResponse = json_decode($invoicesForReverse[$reverseSendUpdateTypeKey]['response']);

            if ($payment->frequency == SageEnum::SF_UPFRONT) {
                if (in_array($reverseSendUpdateType, $checkARInvoices)) {
                    info('Book Update - Creating AR Reverse and Correction Invoices and mark as posted');
                    $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                        'iterator' => 0,
                        'lastIteration' => 6,
                        'startingStep' => 1,
                        'totalSteps' => 21,
                        'batchNumber' => $invoiceResponse->BatchNumber,
                        'entryType' => SageEnum::SCT_STRAIGHT,
                        'invoiceType' => SageEnum::SRT_GET_AR_INVOICE,
                        'requestType' => SageEnum::SRT_REV_CORR_AR_PREM_COMM_INV,
                        'sendUpdateLog' => $extras['send_update_log'] ?? [],
                        'reversalInvoice' => collect($invoicesForReverse)->whereIn('sage_request_type', $checkARInvoices)->first() ?? [],
                    ]);
                }

                if (in_array($reverseSendUpdateType, $checkAPInvoices)) {
                    info('Book Update - Creating AP Reverse and Correction Invoices and mark as posted');
                    $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                        'iterator' => 0,
                        'lastIteration' => 6,
                        'startingStep' => 8,
                        'totalSteps' => 21,
                        'batchNumber' => $invoiceResponse->BatchNumber,
                        'entryType' => SageEnum::SCT_STRAIGHT,
                        'invoiceType' => SageEnum::SRT_GET_AP_INVOICE,
                        'requestType' => SageEnum::SRT_REV_CORR_AP_PREM_INV,
                        'sendUpdateLog' => $extras['send_update_log'] ?? [],
                        'reversalInvoice' => collect($invoicesForReverse)->whereIn('sage_request_type', $checkAPInvoices)->first() ?? [],
                    ]);
                }

                $startingStep = 15;
                $totalSteps = 23;
            } else {
                if (in_array($reverseSendUpdateType, $checkARInvoices)) {
                    info('Book Update - Creating AR Reverse and Correction Split Payment Invoices and mark as posted');
                    $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                        'iterator' => 0,
                        'lastIteration' => 7,
                        'startingStep' => 1,
                        'totalSteps' => 21,
                        'batchNumber' => $invoiceResponse->BatchNumber,
                        'entryType' => SageEnum::SCT_STRAIGHT,
                        'invoiceType' => SageEnum::SRT_GET_AR_INVOICE,
                        'requestType' => SageEnum::SRT_REV_CORR_AR_SPPAY_INV,
                        'payment' => $payment,
                        'splitPayments' => $splitPayments,
                        'sendUpdateLog' => $extras['send_update_log'] ?? [],
                        'reversalInvoice' => collect($invoicesForReverse)->whereIn('sage_request_type', $checkARInvoices)->first() ?? [],
                    ]);
                }

                if (in_array($reverseSendUpdateType, $checkAPInvoices)) {
                    info('Book Update - Creating AP Reverse and Correction Split Payment Invoices and mark as posted');
                    // This Split Invoice for Reverse and Correction need to be tested
                    $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                        'iterator' => 0,
                        'lastIteration' => $extras['ap_patch_call_enable'] ? 7 : 4, // TODO :: This is temporary solution, this after AP Split patch working fine
                        'startingStep' => 9,
                        'totalSteps' => 21,
                        'batchNumber' => $invoiceResponse->BatchNumber,
                        'entryType' => SageEnum::SCT_STRAIGHT,
                        'invoiceType' => SageEnum::SRT_GET_AP_INVOICE,
                        'requestType' => SageEnum::SRT_REV_CORR_AP_SPPAY_INV,
                        'payment' => $payment,
                        'splitPayments' => $splitPayments,
                        'sendUpdateLog' => $extras['send_update_log'] ?? [],
                        'reversalInvoice' => collect($invoicesForReverse)->whereIn('sage_request_type', $checkAPInvoices)->first() ?? [],
                        'apPatchCallEnable' => $extras['ap_patch_call_enable'], // TODO :: This is temporary solution, this after AP Split patch working fine
                    ]);
                }

                $startingStep = 17;
                $totalSteps = 23;
            }

            if ($reverseSendUpdateType == SageEnum::SRT_CREATE_AR_DISC_INV && $sendUpdateLog && $sendUpdateLog->discount > 0) {
                info('Book Update - Creating AR Reverse and Correction Invoices for Discount and mark as posted');
                $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                    'iterator' => 0,
                    'lastIteration' => 6,
                    'startingStep' => $startingStep,
                    'totalSteps' => $totalSteps,
                    'batchNumber' => $invoiceResponse->BatchNumber,
                    'entryType' => SageEnum::SCT_STRAIGHT,
                    'invoiceType' => SageEnum::SRT_GET_AR_INVOICE,
                    'requestType' => SageEnum::SRT_REV_CORR_AR_DIS_INV,
                    'sendUpdateLog' => $extras['send_update_log'] ?? [],
                    'reversalInvoice' => collect($invoicesForReverse)->where('sage_request_type', SageEnum::SRT_CREATE_AR_DISC_INV)->first() ?? [],
                ]);
            }
        }

        $response = ['status' => $_REQUEST['status'] ?? true, 'message' => $_REQUEST['message'] ?? 'Invoices reversed and corrected successfully'];
        info('Book Update - Response: '.$response['message']);

        return ['status' => $response['status'], 'message' => $response['message']];
    }

    public function sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, $extraParams)
    {
        if ($this->recursiveCallStatus == SageEnum::STATUS_FAIL) {
            return true;
        }

        if ($extraParams['iterator'] > $extraParams['lastIteration']) {
            return true;
        }

        $sageInvResponse = [];
        $isLiveApiCall = true;
        $sageEntryType = $extraParams['entryType'];
        $arrayKey = isset($extraParams['arrayKey']) ? $extraParams['arrayKey'] : 0;
        $sageAPIsParams = SagePayloadFactory::handleSageAPIsParms($extraParams['requestType'], $sageEntryType);
        if (! isset($extraParams['recursiveCall']) && ($extraParams['startingStep'] < array_key_first($sageLogArray))) {
            $sageLogKey =
            $extraParams['startingStep'] = array_key_first($sageLogArray);
        } else {
            $sageLogKey = $extraParams['startingStep'];
        }

        $methodName = $sageAPIsParams['recursiveCalls'][$arrayKey];
        $quoteObject = ! empty($extraParams['sendUpdateLog']) ? $extraParams['sendUpdateLog'] : $quote;

        if (isset($extraParams['invoiceType'])) {
            $sageInvResponse = SageApiLogRepository::getInvoiceResponse([
                'reverseInvoiceDetails' => $extraParams['reversalInvoice'],
                'quoteTypeObject' => $quoteObject->getMorphClass(),
                'quoteTypeId' => $quoteObject->id,
                'invoiceType' => $extraParams['invoiceType'],
            ]);
        }

        // This case added for Split Payment patch
        if (isset($extraParams['payment']) && $extraParams['payment']->total_payments > 1 &&
            ((in_array($extraParams['requestType'], [SageEnum::SRT_CREATE_AR_SPPAY_INV, SageEnum::SRT_CREATE_AP_SPPAY_INV])) ||
            (in_array($extraParams['requestType'], [SageEnum::SRT_REV_CORR_AR_SPPAY_INV, SageEnum::SRT_REV_CORR_AP_SPPAY_INV]) && ($extraParams['revCorrSplitPayment'] ?? false)))) {

            $invoiceType = in_array($extraParams['requestType'], [SageEnum::SRT_CREATE_AR_SPPAY_INV, SageEnum::SRT_REV_CORR_AR_SPPAY_INV]) ? SageEnum::AR_INVOICE : SageEnum::AP_INVOICE;
            $splitPaymentResponse = $this->splitPaymentsPatch($quote, $sageRequestPayload, $sageLogArray, $extraParams, $sageInvResponse);

            if (isset($splitPaymentResponse['status']) && $splitPaymentResponse['status'] == false) {
                $_REQUEST['status'] = false;
                $_REQUEST['message'] = $splitPaymentResponse['message'];
                $this->recursiveCallStatus = SageEnum::STATUS_FAIL;

                return $_REQUEST;
            }

            $sageLogKey =
            $extraParams['startingStep'] = $extraParams['startingStep'] + 2;
            $extraParams['iterator'] = $extraParams['iterator'] + 1;

            // TODO :: This is temporary solution, this after AP Split patch working fine
            if (isset($extraParams['apPatchCallEnable']) && ! $extraParams['apPatchCallEnable'] && in_array($extraParams['requestType'], [SageEnum::SRT_CREATE_AP_SPPAY_INV, SageEnum::SRT_REV_CORR_AP_SPPAY_INV])) {
                return true;
            }

            if (in_array($extraParams['requestType'], [SageEnum::SRT_REV_CORR_AR_SPPAY_INV, SageEnum::SRT_REV_CORR_AP_SPPAY_INV]) && ($extraParams['revCorrSplitPayment'] ?? false)) {
                $arrayKey = $arrayKey + 1;
                $extraParams['iterator'] = $extraParams['iterator'] + 1;
            }

            $methodName = $sageAPIsParams['recursiveCalls'][$arrayKey];
        }

        if (method_exists(SagePayloadFactory::class, $methodName)) {
            $requestParms = isset($sageAPIsParams['extraDetails'][$methodName]['requestParms']) &&
                    $sageAPIsParams['extraDetails'][$methodName]['requestParms'] == 'payload' ? $sageRequestPayload : $this->sageBatchNumber;

            switch ($methodName) {
                case 'getInvoiceDetails':
                    $payLoadOptions = SagePayloadFactory::{$methodName}($extraParams['invoiceType'], $extraParams['batchNumber']);
                    break;

                case 'createARInvoicePremAndComm':
                case 'createAPInvoicePrem':
                case 'createARInvoiceDis':
                    $payLoadOptions = SagePayloadFactory::{$methodName}($requestParms, $sageEntryType, $sageInvResponse, ['mainLeadDetails' => $extraParams['mainLeadDetails'] ?? []]);
                    break;

                case 'arSplitPrepaymentPayload':
                    $payLoadOptions = SagePayloadFactory::{$methodName}($quote, $sageRequestPayload->customerId, $extraParams['payment'], $extraParams['splitPayments'], true);
                    break;

                default:
                    if (in_array($extraParams['requestType'], [SageEnum::SRT_CREATE_AR_DISC_INV, SageEnum::SRT_REV_CORR_AR_DIS_INV])) {
                        $payLoadOptions = SagePayloadFactory::{$methodName}($requestParms, $sageEntryType, SageEnum::SCT_DISCOUNT, ['sage_request_type' => $extraParams['requestType']]);
                    } else {
                        if ($methodName == 'createPaymentReceiptOneInvoice') {
                            $payLoadOptions = SagePayloadFactory::{$methodName}($quote, $sageRequestPayload->customerId, $extraParams['payment'], $extraParams['splitPayments'], true);
                        } else {
                            $payLoadOptions = SagePayloadFactory::{$methodName}($requestParms, $sageEntryType, SageEnum::SCT_STRAIGHT, ['sage_request_type' => $extraParams['requestType']]);
                        }
                    }
                    break;
            }

        } else {
            $_REQUEST['status'] = false;
            $_REQUEST['message'] = 'Something went wrong';
            $this->recursiveCallStatus = SageEnum::STATUS_FAIL;
            logger()->error('Book Update - Failed - Something went wrong');

            return $_REQUEST;
        }

        if (isset($sageLogArray[$sageLogKey]) && $sageLogArray[$sageLogKey]['status'] == SageEnum::STATUS_SUCCESS) {
            $isLiveApiCall = false;
            $resp = ($sageLogArray[$sageLogKey]['response'] == 'null') ? '' : $sageLogArray[$sageLogKey]['response'];
            $sageResponse = json_decode($sageLogArray[$sageLogKey]['response'], true);
            info('Book Update - Sage API Call - Method Name ('.$methodName.') Already called - QuoteUUID: '.$quote->uuid.' - SendUpdateUUID: '.$extraParams['sendUpdateLog']->uuid);

        } else {
            $resp = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload'] ?? [], $sageAPIsParams['extraDetails'][$methodName]['verb'] ?? 'POST');
            $sageResponse = json_decode($resp, true);
            info('Book Update - Sage API Call - Method Name ('.$methodName.') - QuoteUUID: '.$quote->uuid.' - SendUpdateUUID: '.$extraParams['sendUpdateLog']->uuid);
        }

        if (in_array($methodName, ['createARInvoicePremAndComm', 'createAPInvoicePrem', 'createARInvoiceDis', 'createPaymentReceiptOneInvoice', 'arSplitPrepaymentPayload']) && isset($sageResponse['BatchNumber'])) {
            $this->sageBatchNumber = $sageResponse['BatchNumber'];
        }

        $isLogResponse = isset($sageAPIsParams['extraDetails'][$methodName]['logResponse']);
        if ($isLogResponse) {
            $conditionCheck = $sageAPIsParams['extraDetails'][$methodName]['conditionChecks']['type'] == 'isset' ?
                isset($sageResponse[$sageAPIsParams['extraDetails'][$methodName]['conditionChecks']['condtion_to_check']]) :
                ($resp !== $sageAPIsParams['extraDetails'][$methodName]['conditionChecks']['condtion_to_check']);

            $respParams = $sageAPIsParams['extraDetails'][$methodName]['conditionChecks']['type'] == 'isset' ?
                $sageResponse : $resp;

            if ($conditionCheck) {
                $this->logSageApiCall($payLoadOptions, $respParams, $quoteObject, $extraParams['startingStep'], $extraParams['totalSteps'], SageEnum::STATUS_FAIL);

                $responseMessage = isset($respParams['error']['message']['value']) ?
                    $respParams['error']['message']['value'] : $sageAPIsParams['extraDetails'][$methodName]['errorMessage'];
                $_REQUEST['status'] = false;
                $_REQUEST['message'] = $responseMessage;
                $this->recursiveCallStatus = SageEnum::STATUS_FAIL;
                logger()->error('Book Update - Sage API Failed - Response: '.$responseMessage);

                return $_REQUEST;
            } else {
                if ($isLiveApiCall) {
                    $this->logSageApiCall($payLoadOptions, $respParams, $quoteObject, $extraParams['startingStep'], $extraParams['totalSteps']);
                }
            }
        }

        $extraParams['iterator'] = $extraParams['iterator'] + 1;
        $arrayKey = $arrayKey + 1;

        if ($methodName == 'getInvoiceDetails') {
            $isFollowUpCondition = $sageResponse['BatchNumber'] == $extraParams['batchNumber'];
        } else {
            $isFollowUpCondition = isset($sageAPIsParams['extraDetails'][$methodName]['nextCondition']) ?
                ! empty($sageResponse[$sageAPIsParams['extraDetails'][$methodName]['nextCondition']]) : true;
        }

        if ($isFollowUpCondition) {
            if ($isLiveApiCall) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $quoteObject, $extraParams['startingStep'], $extraParams['totalSteps']);
            }

            // Need to add split cases for reversal and correction
            if (in_array($extraParams['requestType'], [
                SageEnum::SRT_REV_CORR_AR_PREM_COMM_INV,
                SageEnum::SRT_REV_CORR_AR_SPPAY_INV,
                SageEnum::SRT_REV_CORR_AP_PREM_INV,
                SageEnum::SRT_REV_CORR_AP_SPPAY_INV,
                SageEnum::SRT_REV_CORR_AR_DIS_INV,
            ])) {
                $sageEntryType = $extraParams['iterator'] >= 4 ? SageEnum::SCT_CORRECTION : SageEnum::SCT_REVERSAL;
                $arrayKey = ($extraParams['iterator'] == 4) ? 1 : $arrayKey;

                if (in_array($extraParams['requestType'], [SageEnum::SRT_REV_CORR_AR_SPPAY_INV, SageEnum::SRT_REV_CORR_AP_SPPAY_INV])) {
                    $extraParams['revCorrSplitPayment'] = ($sageEntryType == SageEnum::SCT_CORRECTION && $arrayKey == 1) ? true : false;
                }
            }

            $paymentDetails = [
                'payment' => $extraParams['payment'] ?? ($extraParams['paymentDetails']['payment'] ?? []),
                'splitPayments' => $extraParams['splitPayments'] ?? ($extraParams['paymentDetails']['splitPayments'] ?? []),
            ];

            $recursiveCallData = [
                'iterator' => $extraParams['iterator'],
                'lastIteration' => $extraParams['lastIteration'],
                'startingStep' => $extraParams['startingStep'] + 1,
                'totalSteps' => $extraParams['totalSteps'],
                'entryType' => $sageEntryType,
                'arrayKey' => $arrayKey,
                'requestType' => $extraParams['requestType'],
                'sendUpdateLog' => $extraParams['sendUpdateLog'] ?? [],
                'reversalInvoice' => $extraParams['reversalInvoice'] ?? [],
                'recursiveCall' => true,
                'revCorrSplitPayment' => $extraParams['revCorrSplitPayment'] ?? false,
                'paymentDetails' => $paymentDetails ?? [],
                'apPatchCallEnable' => $extraParams['apPatchCallEnable'] ?? true, // TODO :: This is temporary solution, this after AP Split patch working fine
            ];

            if (isset($extraParams['batchNumber']) && isset($extraParams['invoiceType'])) {
                $recursiveCallData = array_merge($recursiveCallData, [
                    'batchNumber' => $extraParams['batchNumber'],
                    'invoiceType' => $extraParams['invoiceType'],
                ]);
            }

            if ($recursiveCallData['revCorrSplitPayment']) {
                $recursiveCallData['payment'] = $paymentDetails['payment'];
                $recursiveCallData['splitPayments'] = $paymentDetails['splitPayments'];
            }

            // Recursive call as per the next step
            $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, $recursiveCallData);

        } else {
            $this->logSageApiCall($payLoadOptions, $sageResponse, $quoteObject, $extraParams['startingStep'], $extraParams['totalSteps'], SageEnum::STATUS_FAIL);

            $responseMessage = isset($sageResponse['error']['message']['value']) ?
                    $sageResponse['error']['message']['value'] : $sageAPIsParams['extraDetails'][$methodName]['errorMessage'];
            $_REQUEST['status'] = false;
            $_REQUEST['message'] = $responseMessage;
            $this->recursiveCallStatus = SageEnum::STATUS_FAIL;
            logger()->error('Book Update - Sage API Failed - Response: '.$responseMessage);

            return $_REQUEST;
        }
    }

    public function splitPaymentsPatch($quote, $sageRequestPayload, $sageLogArray, $extras, $reverseInvoiceResponse = [])
    {
        $isLiveApiCall = true;
        $quoteObject = ! empty($extras['sendUpdateLog']) ? $extras['sendUpdateLog'] : $quote;
        $processDetails = (in_array($extras['requestType'], [SageEnum::SRT_CREATE_AR_SPPAY_INV, SageEnum::SRT_REV_CORR_AR_SPPAY_INV])) ?
            ['methodName' => 'createARInvoiceSplitPayments', 'invoiceType' => SageEnum::AR_INVOICE, 'url' => 'AR/ARInvoiceBatches', 'requestType' => SageEnum::SRT_AR_SPPAY_PAY_SCDULE_PATCH, 'entryType' => ($extras['requestType'] == SageEnum::SRT_CREATE_AR_SPPAY_INV) ? SageEnum::SCT_STRAIGHT : $extras['entryType']] :
            ['methodName' => 'createAPInvoiceSplitPayments', 'invoiceType' => SageEnum::AP_INVOICE, 'url' => 'AP/APInvoiceBatches', 'requestType' => SageEnum::SRT_AP_SPPAY_PAY_SCDULE_PATCH, 'entryType' => ($extras['requestType'] == SageEnum::SRT_CREATE_AP_SPPAY_INV) ? SageEnum::SCT_STRAIGHT : $extras['entryType']];

        if (isset($sageLogArray[$extras['startingStep']]) && $sageLogArray[$extras['startingStep']]['status'] == SageEnum::STATUS_SUCCESS) {
            $isLiveApiCall = false;
            $postedResponse = json_decode($sageLogArray[$extras['startingStep']]['response'], true);
            info('Book Update - Sage API Call - Method Name ('.$processDetails['methodName'].') Already called - '.(! empty($extras['sendUpdateLog']) ? 'SendUpdateUUID' : 'QuoteUUID').': '.$quoteObject->uuid);

        } else {
            ${$processDetails['methodName']} = SagePayloadFactory::{$processDetails['methodName']}($sageRequestPayload, $extras['splitPayments'], $extras['entryType'], $reverseInvoiceResponse);
            $resp = $this->postToSage300(${$processDetails['methodName']}['endPoint'], ${$processDetails['methodName']}['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if (! empty($postedResponse['BatchNumber'])) {
            $this->sageBatchNumber = $postedResponse['BatchNumber'];

            if ($isLiveApiCall) {
                $this->logSageApiCall(${$processDetails['methodName']}, $postedResponse, $quoteObject, $extras['startingStep'], $extras['totalSteps']);
                info('Book Update - Sage API Call - Method Name ('.$processDetails['methodName'].') - '.(! empty($extras['sendUpdateLog']) ? 'SendUpdateUUID' : 'QuoteUUID').': '.$quoteObject->uuid);
            }

            $url = $processDetails['url'].'('.$this->sageBatchNumber.')';
            $resp = $this->postToSage300($url, [], 'GET');
            $postedResponse = json_decode($resp, true);

            if (empty($postedResponse['Invoices'][0]['InvoicePaymentSchedules'])) {
                $returnMessage['status'] = false;
                $returnMessage['message'] = 'Error while getting '.$processDetails['invoiceType'].' Split paymets from sage';
                logger()->error('Book Update - Sage API Failed - Response: Error while getting '.$processDetails['invoiceType'].' Split paymets from sage');

                return $returnMessage;

            } else {
                $extras['startingStep'] = $extras['startingStep'] + 1;

                info('Book Update - Prepare Patch payload for Split Payments');
                foreach ($postedResponse['Invoices'][0]['InvoicePaymentSchedules'] as $key => $value) {
                    // Add discount value to the first installment of payment in sage for balancing the amount
                    $amountDue = roundNumber($extras['splitPayments'][$key]['payment_amount'] + ($extras['splitPayments'][$key]['sr_no'] == 1 ? $extras['payment']->discount_value : 0));
                    $invoicePaymentSchedulesDueDate = SagePayloadFactory::calculateDueDate(date('Y-m-d', strtotime($extras['splitPayments'][$key]['due_date'])), $sageRequestPayload->insurerInvoiceDate);

                    if ($extras['payment']->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                        $dueDate = $invoicePaymentSchedulesDueDate;
                    } else {
                        $dueDate = $extras['splitPayments'][$key]['sr_no'] == 1 ? $invoicePaymentSchedulesDueDate : date('Y-m-d', strtotime($extras['splitPayments'][$key]['due_date']));
                    }

                    $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['AmountDue'] = $amountDue;
                    $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['DueDate'] = $dueDate;
                }

                if (in_array($extras['requestType'], [SageEnum::SRT_CREATE_AR_SPPAY_INV, SageEnum::SRT_REV_CORR_AR_SPPAY_INV])) {
                    info('Book Update - Prepare Patch payload for Commission Split Payments');
                    $vatOnCommission = floatval($extras['payment']->commission_vat);
                    $commission = floatval($extras['payment']->commission);
                    $commissionWithoutVat = ($commission - $vatOnCommission);
                    $commissionSplit = $commissionWithoutVat > 0 ? $commissionWithoutVat / count($extras['splitPayments']) : 0;

                    $commissionSplitSumWithoutLastSplit = 0;
                    foreach ($postedResponse['Invoices'][1]['InvoicePaymentSchedules'] as $key => $value) {
                        // Add vat on commission split payments to the first installment of commission in sage for balancing the amount
                        $dueCommissionSplitAmount = roundNumber($commissionSplit);
                        if ($postedResponse['Invoices'][1]['InvoicePaymentSchedules'][$key]['PaymentNumber'] == 1) {
                            $dueCommissionSplitAmount = roundNumber($commissionSplit) + roundNumber($vatOnCommission);
                        }

                        // To prevent difference in amount due to rounding number, sum all the dueCommissionSplitAmount except the last one,
                        // and then subtract that amount from the total commission with vat and use the result as dueAmount for last installment
                        if ($postedResponse['Invoices'][1]['InvoicePaymentSchedules'][$key]['PaymentNumber'] == count($extras['splitPayments'])) {
                            $dueCommissionSplitAmount = floatval(sprintf('%.2f', $commission - $commissionSplitSumWithoutLastSplit));
                        } else {
                            $commissionSplitSumWithoutLastSplit += $dueCommissionSplitAmount;
                        }

                        $invoicePaymentSchedulesDueDate = SagePayloadFactory::calculateDueDate(date('Y-m-d', strtotime($extras['splitPayments'][$key]['due_date'])), $sageRequestPayload->insurerInvoiceDate);
                        if ($extras['payment']->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                            $dueDate = $invoicePaymentSchedulesDueDate;
                        } else {
                            $dueDate = $extras['splitPayments'][$key]['sr_no'] == 1 ? $invoicePaymentSchedulesDueDate : date('Y-m-d', strtotime($extras['splitPayments'][$key]['due_date']));
                        }

                        $postedResponse['Invoices'][1]['InvoicePaymentSchedules'][$key]['AmountDue'] = $dueCommissionSplitAmount;
                        $postedResponse['Invoices'][1]['InvoicePaymentSchedules'][$key]['DueDate'] = $dueDate;
                    }
                }

                // TODO :: This is temporary solution, this after AP Split patch working fine
                if (isset($extras['apPatchCallEnable']) && $extras['apPatchCallEnable'] || in_array($extras['requestType'], [SageEnum::SRT_CREATE_AR_SPPAY_INV, SageEnum::SRT_REV_CORR_AR_SPPAY_INV])) {
                    if (isset($sageLogArray[$extras['startingStep']]) && $sageLogArray[$extras['startingStep']]['status'] == SageEnum::STATUS_SUCCESS) {
                        $isLiveApiCall = false;
                        $payLoadOptions =
                        $postedResponse = json_decode($sageLogArray[$extras['startingStep']]['response'], true);
                    } else {
                        $payLoadOptions = $postedResponse;
                        $resp = $this->postToSage300($url, $postedResponse, 'PATCH');
                        $postedResponse = json_decode($resp, true);
                    }

                    $postedResponse = ($postedResponse == '') ? [] : $postedResponse;
                    $postedResponse['endPoint'] = $url;
                    $postedResponse['payload'] = $payLoadOptions;
                    $postedResponse['sage_request_type'] = $processDetails['requestType'];
                    $postedResponse['entry_type'] = $processDetails['entryType'];

                    if (isset($postedResponse['error'])) {
                        $this->logSageApiCall($postedResponse, $resp, $quoteObject, $extras['startingStep'], $extras['totalSteps'], SageEnum::STATUS_FAIL);
                        $responseMessage = isset(json_decode($resp, true)['error']['message']['value']) ?
                            json_decode($resp, true)['error']['message']['value'] : 'Error while making '.$processDetails['invoiceType'].' Split paymets patch to sage';
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = $responseMessage;
                        logger()->error('Book Update - Sage API Failed - Response: '.$responseMessage);

                        return $returnMessage;
                    }

                    if ($isLiveApiCall) {
                        $this->logSageApiCall($postedResponse, $resp, $quoteObject, $extras['startingStep'], $extras['totalSteps']);
                    }
                }
            }

        } else {
            $this->logSageApiCall(${$processDetails['methodName']}, $postedResponse, $quoteObject, $extras['startingStep'], $extras['totalSteps'], SageEnum::STATUS_FAIL);

            $responseMessage = isset($postedResponse['error']['message']['value']) ?
            $postedResponse['error']['message']['value'] : $processDetails['invoiceType'].' Split payment failed from Sage';
            $returnMessage['message'] = $responseMessage;
            $returnMessage['status'] = false;
            logger()->error('Book Update - Sage API Failed - Response: '.$responseMessage);

            return $returnMessage;
        }
    }

    public function postBookPolicyToSage($request, $payment, $quote, $paymentSplits, $data, $skipAPInvoicePatchAndPosting = false, $aPInvoicePatchAndPostingOnly = false)
    {
        // payload
        $sageRequest = $this->sagePayLoad($request->model_type, $payment, $quote, $paymentSplits);

        // check sage is enabled or not
        $isSageEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::SAGE_ENABLED);

        if (! $isSageEnabled) {
            $returnMessage['status'] = false;
            $returnMessage['message'] = 'Sage is not enabled';
        }

        $sageLogArray = $quote->sageApiLogs->keyBy('step')->toArray();
        // sape customer number generation
        $sageCustomerNumber = $this->verifySageCustomer($quote->customer_id, $data, $quote, $sageLogArray, 13);

        if (empty($sageCustomerNumber)) {
            return ['status' => false, 'message' => 'Customer not found in sage'];
        }

        $sageRequest->customerId = $sageCustomerNumber;

        if (! $sageRequest->insurerGlLiaiblityAccount && ! $sageRequest->sageVenderId && ! $sageRequest->sageInsurerCustomerId) {
            return ['status' => false, 'message' => 'Sage Vendor ID, Sage Insurer Customer ID and GL Account for Insurance Provider not found.'];
        } elseif (! $sageRequest->insurerGlLiaiblityAccount) {
            return ['status' => false, 'message' => 'GL Account for Insurance Provider not found.'];
        } elseif (! $sageRequest->sageVenderId) {
            return ['status' => false, 'message' => 'Sage Vendor ID for Insurance Provider not found.'];
        } elseif (! $sageRequest->sageInsurerCustomerId) {
            return ['status' => false, 'message' => 'Sage Insurer Customer ID for Insurance Provider not found.'];
        }

        BookPolicyOnSageJob::dispatch($sageRequest, $quote, $payment, $paymentSplits, $skipAPInvoicePatchAndPosting, $aPInvoicePatchAndPostingOnly)->onQueue('sage-book-policy');

        return ['status' => true, 'message' => 'Policy is being Booked'];
    }


}
