<?php

namespace App\Services;

use App\Enums\QuoteTypeId;
use App\Enums\SageEnums;
use App\Factories\SagePayloadFactory;
use App\Models\Customer;
use App\Models\Lookup;
use App\Models\User;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Traits\SageLoggable;
use Illuminate\Support\Facades\Auth;

class SageApiService
{
    use SageLoggable;

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
        $this->recursiveCallStatus = SageEnums::STATUS_SUCCESS;
    }

    // This payload moved to SagePayloadFactory, Need to verify
    public static function sagePayLoad($modelType, $payment, $quote, $paymentSplits)
    {
        $sageRequest = new \stdClass();

        // $sageRequest->discount = 2;
        $sageRequest->discount = floatval($payment->discount_value);
        $sageRequest->invoiceDescription = $payment->invoice_description;
        $sageRequest->bookingDate = date('Y-m-d', strtotime($quote['policy_booking_date']));
        $sageRequest->policyExpiryDate = date('Ymd', strtotime($quote['renewal_expiry_date']));
        $sageRequest->insurerInvoiceDate = date('Y-m-d', strtotime($payment->insurer_invoice_date));

        if (! empty($paymentSplits)) {
            $sageRequest->paymentDueDate = date('Y-m-d', strtotime($paymentSplits[0]['due_date']));
        }

        $sageRequest->mainClassInsurance = $modelType;
        $sageRequest->policyNumber = $quote->policy_number;

        $sageRequest->policyIssuer = Auth::user()->name;
        $sageRequest->requestType = Lookup::where('id', $quote->transaction_type_id)->first()->text ?? '';
        $sageRequest->subClass = '';

        $sageRequest->invoicePaymentStatus = $payment->transaction_payment_status;
        // $sageRequest->invoicePaymentStatus = 'paid';
        $advisorName = '';
        if (! empty($quote->advisor_id)) {

            $advisorName = User::where('id', $quote->advisor_id)->value('name');
        }
        $sageRequest->advisorName = $advisorName;
        $sageRequest->premiumWithoutTax = floatval($quote->price_without_vat);
        $sageRequest->premiumWithTax = floatval($quote->price_with_vat);
        $sageRequest->vatOnCommission = floatval($payment->commission_vat);
        $sageRequest->commission = floatval($payment->commission);
        $sageRequest->commissionIncludingVat = floatval($payment->commission_vat_applicable);
        $sageRequest->commissionWithOutVat = $payment->commission_vat_not_applicable;

        $sageRequest->insurerPremiumNumber = (string) $payment['insurer_tax_number'];
        $sageRequest->insurerCommissionNumber = (string) $payment['insurer_commmission_invoice_number'];
        if (count($paymentSplits) == 1) {
            $sageRequest->sage_reciept_id = $paymentSplits[0]['sage_reciept_id'];
            $sageRequest->collection_amount = $paymentSplits[0]['collection_amount'];
        }

        return $sageRequest;
    }

    // Code Refactor, Old function verifySageCustomer updated function sageCustomer, Need to verify
    public function sageCustomer($quoteTypeId, $quote, $totalSteps = 4) 
    {
        $sageCustomerNumber = false;
        $customer = Customer::where('id', $quote->customer_id)->first();

        if ($customer) {
            $response = '';
            $customer->data = ['quoteTypeId' => $quoteTypeId, 'id' => $quote->id];
            $sageLogArray = $quote->sageLog->keyBy('step')->toArray();
            $customerPayload = [
                'endPoint' => SageEnums::END_POINT_AR_CUSTOMER,
                'payload' => []
            ];

            if ($customer->sage_customer_number) {
                $this->logSageApiCall($customerPayload, $response, $quote, 1, $totalSteps);
                $sageCustomerNumber = $customer->sage_customer_number;
            } else {
                $isLiveApiCallStep1 = true;
                $sageSecondLog = isset($sageLogArray[1]) ? $sageLogArray[1] : false;

                if($sageSecondLog && $sageSecondLog['status'] == SageEnums::STATUS_SUCCESS) {
                    $isLiveApiCallStep1 = false;
                    $response = json_decode($sageSecondLog['response'], true);
                } else {
                    $customerPayload = SagePayloadFactory::createCustomerPayload($customer);
                    $curlResponse = $this->postToSage300($customerPayload['endPoint'], $customerPayload['payload']);
                    $response = json_decode($curlResponse, true);
                }
                $responseError = isset($response['error']['code']) ? $response['error']['code'] : false;
                if ($responseError && $responseError == SageEnums::ERROR_RECORD_DUPLICATE) {
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
                if (isset($sageLogArray[1]) && $sageLogArray[1]['status'] == 'success') {
                    $isLiveApiCallStep1 = false;
                    $response = json_decode($sageLogArray[1]['response'], true);
                } else {
                    $payLoadOptions = SagePayloadFactory::createCustomerPayload($customer);
                    $jsonResponse = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
                    $response = json_decode($jsonResponse, true);
                }
                if (isset($response['error']['code']) && $response['error']['code'] == 'RecordDuplicate') {
                    $sageCustomerNumber = $payLoadOptions['customerNumber'];
                } elseif (isset($response['CustomerNumber'])) {
                    $sageCustomerNumber = $response['CustomerNumber'];
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
        info('response-----------'.json_encode($response));
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
        if ($extras['type'] == SageEnums::TYPE_SEND_UPDATE) {
            $customerTotalSteps = $extras['send_update_type'] == SageEnums::SEND_UPDATE_NORMAL ? 13 : 4;
        }
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($request->quoteType));
        $sageCustomerNumber = $this->sageCustomer($quoteTypeId, $quote, $customerTotalSteps);

        if ($sageCustomerNumber) {

            $response = '';
            $paymentFilterAsPerType = ($extras['type'] == SageEnums::TYPE_SEND_UPDATE) ? ['send_update_log_id' => $request->sendUpdateId] : ['code' => $quote['code']];
            $payment = Payment::where($paymentFilterAsPerType)->first();
            $splitPayments = PaymentSplits::where('code', $payment->code)->get();
            $sageRequestPayload = SagePayloadFactory::sagePayLoad($request->quoteType, $quote, $payment, $splitPayments);
            $sageRequestPayload->customerId = $sageCustomerNumber;

            switch ($extras['type']) {
                case SageEnums::TYPE_SEND_UPDATE:
                    $response = $this->handleSendUpdateCalls($quote, $sageRequestPayload, $payment, $splitPayments, $extras);
                    break;
            }

            if(!empty($response))
                return ['status' => $response['status'], 'message' => $response['message']]; 
        }

        return ['status' => false, 'message' => 'Customer not found in Sage']; 
    }

    private function handleSendUpdateCalls($quote, $sageRequestPayload, $payment, $splitPayments, $extras)
    {
        $sageLogArray = $quote->sageLog->keyBy('step')->toArray();

        switch ($extras['send_update_type']) {
            case SageEnums::SEND_UPDATE_NORMAL:
                $this->handleSendUpdateNormalCalls($quote, $payment, $splitPayments, $sageRequestPayload, $sageLogArray);
                break;

            case SageEnums::SEND_UPDATE_REVERSAL_CORRECTION:
                $this->handleSendUpdateReversalCalls($quote, $payment, $splitPayments, $sageRequestPayload, $sageLogArray, $extras);
                break;

            
        }
    }

    private function handleSendUpdateNormalCalls($quote, $payment, $splitPayments, $sageRequestPayload, $sageLogArray)
    {
        // If upfront Payment
        if ($payment->total_payments == 1) {
            // Create AR and AP Invoices
            $this->recursiveSageAPIsCalls($quote, $sageRequestPayload, $sageLogArray, ['iterator' => 0, 'lastIteration' => 2, 'startingStep' => 2, 'totalSteps' => 13, 'documentType' => SageEnums::DOCUMENT_TYPE_CREATE_AR_INVOICE]);
            $this->recursiveSageAPIsCalls($quote, $sageRequestPayload, $sageLogArray, ['iterator' => 3, 'lastIteration' => 5, 'startingStep' => 5, 'totalSteps' => 13, 'documentType' => SageEnums::DOCUMENT_TYPE_CREATE_AP_INVOICE]);

            // $this->recursiveSageApiCall(5, $sageRequestPayload, $sageAPIsParams, $quote);
        }

        if ($sageRequestPayload->discount > 0) {
            // Create AR Invoice DIS
            $this->recursiveSageAPIsCalls($quote, $sageRequestPayload, $sageLogArray, ['iterator' => 6, 'lastIteration' => 8, 'startingStep' => 8, 'totalSteps' => 13, 'documentType' => SageEnums::DOCUMENT_TYPE_CREATE_AR_INVOICE_DIS]);

            // $this->recursiveSageApiCall(8, $sageRequestPayload, $sageAPIsParams, $quote, 8, 13, 6);
        }

        // Apply Split Payment Invoices
        if (strtolower($sageRequestPayload->invoicePaymentStatus) == SageEnums::STATUS_PAID) {

            $totalSteps = $payment->total_payments > 1 ? 16 : 13;
            $currentStep = 11;

            $this->recursiveSageAPIsCalls($quote, $sageRequestPayload, $sageLogArray, [
                'iterator' => 9, 
                'lastIteration' => $payment->total_payments > 1 ? 13 : 12, 
                'startingStep' => $currentStep, 
                'totalSteps' => $totalSteps, 
                'documentType' => SageEnums::DOCUMENT_TYPE_APPLY_SPLIT_PAYMENT_INVOICES,
                'payment' => $payment,
                'splitPayments' => $splitPayments
            ]);
        }
    }

    private function handleSendUpdateReversalCalls($quote, $payment, $splitPayments, $sageRequestPayload, $sageLogArray, $extras)
    {
        $sendUpdateLog = $extras['send_update_log'];


        
        dd($extras['send_update_log']->toArray());
    }

    private function recursiveSageAPIsCalls($quote, $sageRequestPayload, $sageLogArray, $extras)
    {
        if ($this->recursiveCallStatus == SageEnums::STATUS_FAIL)
            return true;

        if ($extras['iterator'] > $extras['lastIteration'])
            return true;

        $arrayKey = isset($extras['arrayKey']) ? $extras['arrayKey'] : 0;
        $isLiveApiCall = true;
        $sageAPIsParams = SagePayloadFactory::handleSageAPIsParms($extras['documentType']);
        $methodName = $sageAPIsParams['recursiveCalls'][$arrayKey];

        if (isset($sageLogArray[$extras['startingStep']]) && $sageLogArray[$extras['startingStep']]['status'] == SageEnums::STATUS_SUCCESS) {
            $isLiveApiCall = false;
            $sageResponse = json_decode($sageLogArray[$extras['startingStep']]['response'], true);
        } else {
            // This case only for Split Payments
            if($extras['payment']->total_payments > 1) {
                if (isset($sageLogArray[$extras['startingStep']]) && $sageLogArray[$extras['startingStep']]['status'] == SageEnums::STATUS_SUCCESS) {
                    $isLiveApiCall = false;
                    $sageResponse = json_decode($sageLogArray[$extras['startingStep']]['response'], true);
                } else {
                    $createARInvoiceSplitPayments = SagePayloadFactory::createARInvoiceSplitPayments($sageRequestPayload, $extras['splitPayments']);
                    $resp = $this->postToSage300($createARInvoiceSplitPayments['endPoint'], $createARInvoiceSplitPayments['payload']);
                    $postedResponse = json_decode($resp, true);
                }

                if (! empty($postedResponse['BatchNumber'])) {
                    if ($isLiveApiCall) {
                        $this->logSageApiCall($createARInvoiceSplitPayments, $postedResponse, $quote, $extras['startingStep'], $extras['totalSteps']);
                    }
                    $url = 'AR/ARInvoiceBatches('.$postedResponse['BatchNumber'].')';
                    $resp = $this->postToSage300($url, [], 'GET');
                    $postedResponse = json_decode($resp, true);

                    if (! empty($postedResponse['Invoices'][0]['InvoicePaymentSchedules'])) {
                        foreach ($postedResponse['Invoices'][0]['InvoicePaymentSchedules'] as $key => $value) {
                            $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['AmountDue'] = $extras['splitPayments'][$key]['collection_amount'];
                            $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['DueDate'] = date('Y-m-d', strtotime($extras['splitPayments'][$key]['due_date']));
                        }
                        if (isset($sageLogArray[$extras['startingStep']]) && $sageLogArray[$extras['startingStep']]['status'] == SageEnums::STATUS_SUCCESS) {
                            $isLiveApiCall = false;
                            $postedResponse = json_decode($sageLogArray[$extras['startingStep']]['response'], true);
                        } else {
                            $payLoadOptions = $postedResponse;
                            $resp = $this->postToSage300($url, $postedResponse, 'PATCH');
                            $postedResponse = json_decode($resp, true);
                        }
                    }
                } else {
                    $this->logSageApiCall($createARInvoiceSplitPayments, $postedResponse, $quote, $extras['startingStep'], $totalSteps, 'fail');
                    $returnMessage['message'] = 'Split payment failed from Sage';
                    $returnMessage['status'] = false;

                    return $returnMessage;
                }

            } else {
                if (method_exists(SagePayloadFactory::class, $methodName)) {
                    $requestParms = isset($sageAPIsParams['extraDetails'][$methodName]['requestParms']) && 
                        $sageAPIsParams['extraDetails'][$methodName]['requestParms'] == 'payload' ? $sageRequestPayload : $this->sageBatchNumber;
    
                    if ($methodName == 'readyToPostReceiptArPayment') {
                        $payLoadOptions = SagePayloadFactory::{$methodName}($sageRequestPayload->customerId, $extras['splitPayments']);
                    } else if($methodName == 'arSplitPrepaymentPayload') {
                        $payLoadOptions = SagePayloadFactory::{$methodName}($quote, $sageRequestPayload->customerId, $extras['payment'], $extras['splitPayments']);
                    } else {
                        $payLoadOptions = SagePayloadFactory::{$methodName}($requestParms);
                    }
    
                    $resp = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload'], $sageAPIsParams['extraDetails'][$methodName]['verb'] ?? 'POST');
                    $sageResponse = json_decode($resp, true);
                    
                    if (in_array($methodName, ['createARInvoicePremAndComm', 'createAPInvoicePrem', 'createARInvoiceDis', 'createPaymontRecieptOneInvoice']) && isset($sageResponse['BatchNumber'])) {
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
                            $this->logSageApiCall($payLoadOptions, $respParams, $quote, $extras['startingStep'], $extras['totalSteps'], SageEnums::STATUS_FAIL);
                            $returnMessage['status'] = false;
                            $returnMessage['message'] = $sageAPIsParams['extraDetails'][$methodName]['errorMessage'];
                            $this->recursiveCallStatus = SageEnums::STATUS_FAIL;
        
                            return $returnMessage;
                        } else {
                            if ($isLiveApiCall) {
                                $this->logSageApiCall($payLoadOptions, $respParams, $quote, $extras['startingStep'], $extras['totalSteps']);
                            }
                        }
                    }
                    
                } else {
                    $returnMessage['status'] = false;
                    $returnMessage['message'] = 'Something went wrong';
                    $this->recursiveCallStatus = SageEnums::STATUS_FAIL;
                }
            }
        }

        $extras['iterator'] = $extras['iterator'] + 1;
        $arrayKey = $arrayKey + 1;
        $isFollowUpCondition = isset($sageAPIsParams['extraDetails'][$methodName]['nextCondition']) ? 
            !empty($sageResponse[$sageAPIsParams['extraDetails'][$methodName]['nextCondition']]) : true;

        if ($isFollowUpCondition) {
            if ($isLiveApiCall) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $quote, $extras['startingStep'], $extras['totalSteps']);
            }
            $this->recursiveSageAPIsCalls($quote, $sageRequestPayload, $sageLogArray, [
                'iterator' => $extras['iterator'], 
                'lastIteration' => $extras['lastIteration'], 
                'startingStep' => $extras['startingStep'] + 1, 
                'totalSteps' => $extras['totalSteps'], 
                'documentType' => $extras['documentType'],
                'arrayKey' => $arrayKey
            ]);
        } else {
            $this->logSageApiCall($payLoadOptions, $sageResponse, $quote, $extras['startingStep'], $extras['totalSteps'], SageEnums::STATUS_FAIL);
            $returnMessage['status'] = false;
            $returnMessage['message'] = $sageAPIsParams['extraDetails'][$methodName]['errorMessage'];
            $this->recursiveCallStatus = SageEnums::STATUS_FAIL;

            return $returnMessage;
        }
    }

    public function postBookPolicyToSage($request, $payment, $quote, $paymentSplits, $data)
    {

        // payload
        $sageRequest = $this->sagePayLoad($request->model_type, $payment, $quote, $paymentSplits);

        $sageLogArray = $quote->sageLog->keyBy('step')->toArray();
        
        // sape customer number generation
        $sageCustomerNumber = $this->verifySageCustomer($quote->customer_id, $data, $quote, $sageLogArray, 13);

        if ($sageCustomerNumber) {

            $sageRequest->customerId = $sageCustomerNumber;

            // total_payments = 1 means upfront payment

            if ($payment->total_payments == 1) {

                /* createARInvoicePremAndComm */
                $isLiveApiCallStep2 = true;
                if (isset($sageLogArray[2]) && $sageLogArray[2]['status'] == 'success') {
                    $isLiveApiCallStep2 = false;
                    $sageResponse = json_decode($sageLogArray[2]['response'], true);
                } else {
                    $payLoadOptions = SagePayloadFactory::createARInvoicePremAndComm($sageRequest);
                    $resp = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
                    $sageResponse = json_decode($resp, true);
                }

                if (! empty($sageResponse['BatchNumber'])) {

                    if ($isLiveApiCallStep2) {
                        $this->logSageApiCall($payLoadOptions, $sageResponse, $quote, 2, 13);
                    }
                    $isLiveApiCallStep3 = true;
                    if (isset($sageLogArray[3]) && $sageLogArray[3]['status'] == 'success') {
                        $isLiveApiCallStep3 = false;
                        $readyToPostResponse = json_decode($sageLogArray[3]['response'], true);
                    } else {
                        $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr($sageResponse['BatchNumber']);
                        $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
                    }

                    if ($readyToPostResponse !== '') {
                        $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, 3, 13, 'fail');
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making Ar invoice & prem ready to post to sage';

                        return $returnMessage;
                    } else {
                        if ($isLiveApiCallStep3) {
                            $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, 3, 13);
                        }
                    }

                    $isLiveApiCallStep4 = true;
                    if (isset($sageLogArray[4]) && $sageLogArray[4]['status'] == 'success') {
                        $isLiveApiCallStep4 = false;
                        $postedResponse = json_decode($sageLogArray[4]['response'], true);
                    } else {
                        $aRPostInvoices = SagePayloadFactory::aRPostInvoices($sageResponse['BatchNumber']);
                        $resp = $this->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                        $postedResponse = json_decode($resp, true);
                    }

                    if (isset($postedResponse['error'])) {
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making Ar invoice & prem Posted to sage';
                        $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, 4, 13, 'fail');

                        return $returnMessage;
                    } else {
                        if ($isLiveApiCallStep4) {
                            $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, 4, 13);
                        }
                    }
                } else {
                    $this->logSageApiCall($payLoadOptions, $sageResponse, $quote, 2, 13, 'fail');
                    $returnMessage['message'] = 'Ar invoice & prem failed from sage';
                    $returnMessage['status'] = false;

                    return $returnMessage;
                }
            }

            /* createAPInvoicePrem */

            // total_payments = 1 means upfront payment

            if ($payment->total_payments == 1) {
                $isLiveApiCallStep5 = true;
                if (isset($sageLogArray[5]) && $sageLogArray[5]['status'] == 'success') {
                    $isLiveApiCallStep5 = false;
                    $postedResponse = json_decode($sageLogArray[5]['response'], true);
                } else {
                    $createAPInvoicePrem = SagePayloadFactory::createAPInvoicePrem($sageRequest);
                    $resp = $this->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
                    $postedResponse = json_decode($resp, true);
                }

                if (! empty($postedResponse['BatchNumber'])) {

                    if ($isLiveApiCallStep5) {
                        $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $quote, 5, 13);
                    }

                    $isLiveApiCallStep6 = true;
                    if (isset($sageLogArray[6]) && $sageLogArray[6]['status'] == 'success') {
                        $isLiveApiCallStep6 = false;
                        $readyToPostResponse = json_decode($sageLogArray[6]['response'], true);
                    } else {
                        $readyToPostInvoiceAP = SagePayloadFactory::readyToPostInvoiceAP($postedResponse['BatchNumber']);
                        $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
                    }

                    if ($readyToPostResponse !== '') {
                        $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $quote, 6, 13, 'fail');
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making AP invoice ready to post to sage';

                        return $returnMessage;
                    } else {
                        if ($isLiveApiCallStep6) {
                            $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $quote, 6, 13);
                        }
                    }

                    $isLiveApiCallStep7 = true;
                    if (isset($sageLogArray[7]) && $sageLogArray[7]['status'] == 'success') {
                        $isLiveApiCallStep7 = false;
                        $postedResponse = json_decode($sageLogArray[7]['response'], true);
                    } else {
                        $aPPostInvoices = SagePayloadFactory::aPPostInvoices($postedResponse['BatchNumber']);
                        $resp = $this->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                        $postedResponse = json_decode($resp, true);
                    }

                    if (isset($postedResponse['error'])) {
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making AP invoices Posted to sage';
                        $this->logSageApiCall($aPPostInvoices, $postedResponse, $quote, 7, 13, 'fail');

                        return $returnMessage;
                    } else {
                        if ($isLiveApiCallStep7) {
                            $this->logSageApiCall($aPPostInvoices, $postedResponse, $quote, 7, 13);
                        }
                    }
                } else {
                    $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $quote, 5, 13, 'fail');
                    $returnMessage['message'] = 'Ap invoice prem failed from sage';
                    $returnMessage['status'] = false;

                    return $returnMessage;
                }
            }

            /* createARInvoiceDis */
            if ($sageRequest->discount > 0) {

                $isLiveApiCallStep8 = true;
                if (isset($sageLogArray[8]) && $sageLogArray[8]['status'] == 'success') {
                    $isLiveApiCallStep8 = false;
                    $postedResponse = json_decode($sageLogArray[8]['response'], true);
                } else {
                    $createARInvoiceDis = SagePayloadFactory::createARInvoiceDis($sageRequest);
                    $resp = $this->postToSage300($createARInvoiceDis['endPoint'], $createARInvoiceDis['payload']);
                    $postedResponse = json_decode($resp, true);
                }

                if (! empty($postedResponse['BatchNumber'])) {

                    if ($isLiveApiCallStep8) {
                        $this->logSageApiCall($createARInvoiceDis, $postedResponse, $quote, 8, 13);
                    }

                    $isLiveApiCallStep9 = true;
                    if (isset($sageLogArray[9]) && $sageLogArray[9]['status'] == 'success') {
                        $isLiveApiCallStep9 = false;
                        $readyToPostResponse = json_decode($sageLogArray[9]['response'], true);
                    } else {
                        $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr($postedResponse['BatchNumber']);
                        $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
                    }

                    if ($readyToPostResponse !== '') {
                        $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, 9, 13, 'fail');
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making Ar discount invoice ready to post to sage';

                        return $returnMessage;
                    } else {
                        if ($isLiveApiCallStep9) {
                            $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, 9, 13);
                        }
                    }

                    $isLiveApiCallStep10 = true;
                    if (isset($sageLogArray[10]) && $sageLogArray[10]['status'] == 'success') {
                        $isLiveApiCallStep10 = false;
                        $postedResponse = json_decode($sageLogArray[10]['response'], true);
                    } else {
                        $aRPostInvoices = SagePayloadFactory::aRPostInvoices($postedResponse['BatchNumber']);
                        $resp = $this->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                        $postedResponse = json_decode($resp, true);
                    }

                    if (isset($postedResponse['error'])) {
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making Ar discount invoice Posted to sage';
                        $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, 10, 13, 'fail');

                        return $returnMessage;
                    } else {
                        if ($isLiveApiCallStep10) {
                            $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, 10, 13);
                        }
                    }
                } else {
                    $this->logSageApiCall($createARInvoiceDis, $postedResponse, $quote, 8, 13, 'fail');
                    $returnMessage['message'] = 'Ar discount invoice failed from sage';
                    $returnMessage['status'] = false;

                    return $returnMessage;
                }
            }

            /* applypaymentInvoices */
            if (strtolower($sageRequest->invoicePaymentStatus) == 'paid') {

                $totalSteps = 13;
                $currentStep = 11;
                if ($payment->total_payments > 1) {
                    $totalSteps = 16;
                    $currentStep = 11;
                }
                $isLiveApiCallStep11 = true;
                if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                    $isLiveApiCallStep11 = false;
                    $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);

                // dd($postedResponse);
                } else {

                    if ($payment->total_payments > 1) {

                        $isLiveApiCallStep111 = true;
                        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                            $isLiveApiCallStep111 = false;
                            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
                        } else {

                            $createARInvoiceSplitPayments = SagePayloadFactory::createARInvoiceSplitPayments($sageRequest, $paymentSplits);
                            $resp = $this->postToSage300($createARInvoiceSplitPayments['endPoint'], $createARInvoiceSplitPayments['payload']);
                            $postedResponse = json_decode($resp, true);
                            info('=======resp '.json_encode($postedResponse));
                        }

                        if (! empty($postedResponse['BatchNumber'])) {
                            if ($isLiveApiCallStep111) {
                                $this->logSageApiCall($createARInvoiceSplitPayments, $postedResponse, $quote, $currentStep, $totalSteps);
                            }
                            $url = 'AR/ARInvoiceBatches('.$postedResponse['BatchNumber'].')';
                            $resp = $this->postToSage300($url, [], 'GET');
                            $postedResponse = json_decode($resp, true);

                            if (! empty($postedResponse['Invoices'][0]['InvoicePaymentSchedules'])) {
                                foreach ($postedResponse['Invoices'][0]['InvoicePaymentSchedules'] as $key => $value) {
                                    $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['AmountDue'] = $paymentSplits[$key]['collection_amount'];
                                    $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['DueDate'] = date('Y-m-d', strtotime($paymentSplits[$key]['due_date']));
                                }
                                if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                                    $isLiveApiCallStep11 = false;
                                    $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
                                } else {
                                    $createPaymontRecieptOneInvoice = $postedResponse;

                                    $resp = $this->postToSage300($url, $postedResponse, 'PATCH');
                                    $postedResponse = json_decode($resp, true);
                                }
                            }
                        } else {
                            $this->logSageApiCall($createARInvoiceSplitPayments, $postedResponse, $quote, $currentStep, $totalSteps, 'fail');
                            $returnMessage['message'] = 'split payment failed from sage';
                            $returnMessage['status'] = false;

                            return $returnMessage;
                        }
                    } else {

                        $createPaymontRecieptOneInvoice = SagePayloadFactory::createPaymontRecieptOneInvoice($sageRequest);
                        $resp = $this->postToSage300($createPaymontRecieptOneInvoice['endPoint'], $createPaymontRecieptOneInvoice['payload']);
                        $postedResponse = json_decode($resp, true);
                    }
                }

                // Starting from Here

                if (! empty($postedResponse['BatchNumber'])) {

                    SagePayloadFactory::readyToPostReceiptArPayment($postedResponse['BatchNumber']);

                    if ($isLiveApiCallStep11) {
                        $this->logSageApiCall($createPaymontRecieptOneInvoice, $postedResponse, $quote, $currentStep, $totalSteps);
                    }

                    $currentStep = 12;

                    $isLiveApiCallStep12 = true;
                    if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                        $isLiveApiCallStep12 = false;
                        $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
                    } else {
                        $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptArPayment($sageCustomerNumber, $paymentSplits);
                        $readyToPostResponse = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'POST');
                    }

                    if ($readyToPostResponse !== '') {
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps, 'fail');
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while posting to payment ';

                        return $returnMessage;
                    } else {
                        if ($isLiveApiCallStep12) {
                            $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps);
                        }
                    }

                    $currentStep = 13;

                    $isLiveApiCallStep13 = true;
                    if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                        $isLiveApiCallStep13 = false;
                        $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
                    } else {
                        $aRPostReceipts = SagePayloadFactory::aRPostReceipts($postedResponse['BatchNumber']);
                        $resp = $this->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                        $postedResponse = json_decode($resp, true);
                    }

                    if (isset($postedResponse['error'])) {
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making Apply payment Posted to sage';
                        $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $currentStep, $totalSteps, 'fail');

                        return $returnMessage;
                    } else {
                        if ($isLiveApiCallStep13) {
                            $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $currentStep, $totalSteps);
                        }
                    }

                    $currentStep = 14;

                    $isLiveApiCallStep14 = true;
                    if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                        $isLiveApiCallStep14 = false;
                        $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
                    } else {
                        $readyToPostReceiptAr = SagePayloadFactory::arSplitPrepaymentPayload($quote, $sageCustomerNumber, $payment, $paymentSplits);

                        $readyToPostResponse = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'POST');
                    }

                    if ($readyToPostResponse !== '') {
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps, 'fail');
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making Apply payment ready to post to sage';

                        return $returnMessage;
                    } else {
                        if ($isLiveApiCallStep14) {
                            $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps);
                        }
                    }

                } else {
                    $this->logSageApiCall($createPaymontRecieptOneInvoice, $postedResponse, $quote, $currentStep, $totalSteps, 'fail');
                    $returnMessage['message'] = 'Apply payment failed from sage';
                    $returnMessage['status'] = false;

                    return $returnMessage;
                }
            }

            return ['status' => true, 'message' => 'Policy Booked Successfully'];
        } else {
            return ['status' => false, 'message' => 'Customer not found in sage'];
        }
    }
}
