<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Factories\SagePayloadFactory;
use App\Models\BusinessInsuranceType;
use App\Models\Customer;
use App\Models\Lookup;
use App\Models\User;
use App\Repositories\SendUpdateLogRepository;
use App\Traits\SageLoggable;
use App\Traits\TeamHierarchyTrait;

class SageApiService
{
    use SageLoggable, TeamHierarchyTrait;

    protected $sageLogin;
    protected $sagePassword;
    protected $sageRequestUrl;

    public function __construct()
    {
        //Guzzle was not working for post request
        $this->sageLogin = env('SAGE_300_LOGIN');
        $this->sagePassword = env('SAGE_300_PASSWORD');
        $this->sageRequestUrl = env('SAGE_300_BASE_URL').env('SAGE_300_VERSION');
    }

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
        $sageRequest->policyNumber = $quote->policy_number;
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

        $sageRequest->invoicePaymentStatus = $payment->transaction_payment_status;
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
        $sageRequest->vatOnPremium = $quote->vat ?: ($quote->price_with_vat ? (floatval($quote->price_with_vat) - floatval($quote->price_vat_applicable)) : 0);

        $sageRequest->premiumWithoutTax = floatval($quote->price_without_vat);
        $sageRequest->premiumWithTax = floatval($quote->price_with_vat);
        $sageRequest->vatOnCommission = floatval($payment->commission_vat);
        $sageRequest->totalAmount = floatval($payment->total_amount);
        $sageRequest->commission = floatval($payment->commission);
        $sageRequest->commissionIncludingVat = floatval($payment->commission_vat_applicable);
        $sageRequest->commissionWithOutVat = floatval($payment->commission_vat_not_applicable);
        $sageRequest->commissionPercentage = strval($payment->commmission_percentage);

        $sageRequest->insurerPremiumNumber = (string) $payment['insurer_tax_number'];
        $sageRequest->insurerCommissionNumber = (string) $payment['insurer_commmission_invoice_number'];
        if (count($paymentSplits) == 1) {
            $sageRequest->sage_reciept_id = $paymentSplits[0]['sage_reciept_id'];
            $sageRequest->collection_amount = $paymentSplits[0]['collection_amount'] + $sageRequest->discount;
        }

        //Insurer GL Account and Vendor Number
        $sageRequest->insurerGlLiaiblityAccount = $payment->insuranceProvider?->gl_liaiblity_account;
        $sageRequest->sageVenderId = $payment->insuranceProvider?->sage_vendor_id;

        return $sageRequest;
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

        info('response -------- : '.json_encode($response));
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

    public function postBookPolicyToSage($request, $payment, $quote, $paymentSplits, $data)
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

        if (! $sageRequest->insurerGlLiaiblityAccount) {
            return ['status' => false, 'message' => 'Insurance Provider not found'];
        }

        // frequency  is 'upfront'
        if ($payment->frequency == 'upfront') {

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
                }
                if ($isLiveApiCallStep3) {
                    $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, 3, 13);
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
                }
                if ($isLiveApiCallStep4) {
                    $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, 4, 13);
                }
            } else {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $quote, 2, 13, 'fail');
                $returnMessage['message'] = 'Ar invoice & prem failed from sage';
                $returnMessage['status'] = false;

                return $returnMessage;
            }
        } else {

            //2
            $isLiveApiCallStep2 = true;
            if (isset($sageLogArray[2]) && $sageLogArray[2]['status'] == 'success') {
                $isLiveApiCallStep2 = false;
                $postedResponse = json_decode($sageLogArray[2]['response'], true);
            } else {

                $createARInvoiceSplitPayments = SagePayloadFactory::createARInvoiceSplitPayments($sageRequest, $paymentSplits);

                $resp = $this->postToSage300($createARInvoiceSplitPayments['endPoint'], $createARInvoiceSplitPayments['payload']);
                $postedResponse = json_decode($resp, true);
            }

            if (empty($postedResponse['BatchNumber'])) {
                $this->logSageApiCall($createARInvoiceSplitPayments, $postedResponse, $quote, 2, 16, 'fail');
                $returnMessage['message'] = 'ar split payment failed from sage';
                $returnMessage['status'] = false;

                return $returnMessage;
            }
            $batchNumber = $postedResponse['BatchNumber'];
            if ($isLiveApiCallStep2) {
                $this->logSageApiCall($createARInvoiceSplitPayments, $postedResponse, $quote, 2, 16);
            }

            $url = 'AR/ARInvoiceBatches('.$batchNumber.')';
            $resp = $this->postToSage300($url, [], 'GET');
            $postedResponse = json_decode($resp, true);

            if (empty($postedResponse['Invoices'][0]['InvoicePaymentSchedules'])) {
                $returnMessage['status'] = false;
                $returnMessage['message'] = 'Error while get ar2 split paymets from sage';

                return $returnMessage;
            }
            foreach ($postedResponse['Invoices'][0]['InvoicePaymentSchedules'] as $key => $value) {
                // add discount amount to amount due for the first child payment in sage for balancing the amount
                $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['AmountDue'] = $paymentSplits[$key]['collection_amount'] + ($paymentSplits[$key]['sr_no'] == 1 ? $payment->discount_value : 0);
                $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['DueDate'] = date('Y-m-d', strtotime($paymentSplits[$key]['due_date']));
            }
            //3
            $isLiveApiCallStep3 = true;
            if (isset($sageLogArray[3]) && $sageLogArray[3]['status'] == 'success') {
                $isLiveApiCallStep3 = false;
                $postedResponse = json_decode($sageLogArray[3]['response'], true);
            } else {
                $resp = $this->postToSage300($url, $postedResponse, 'PATCH');
                $postedResponse = json_decode($resp, true);
            }
            $postedResponse['endPoint'] = $url;
            $postedResponse['payload'] = $postedResponse;
            if (isset($postedResponse['error'])) {
                $this->logSageApiCall($postedResponse, $postedResponse, $quote, 3, 16, 'fail');
                $returnMessage['status'] = false;
                $returnMessage['message'] = 'Error while making ar2 split paymets patch to sage';

                return $returnMessage;
            }
            if ($isLiveApiCallStep3) {
                $this->logSageApiCall($postedResponse, $postedResponse, $quote, 3, 16);
            }

            // 4
            $isLiveApiCallStep4 = true;
            if (isset($sageLogArray[4]) && $sageLogArray[4]['status'] == 'success') {
                $isLiveApiCallStep4 = false;
                $readyToPostResponse = json_decode($sageLogArray[4]['response'], true);
            } else {
                $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr($batchNumber);

                $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
            }

            if (isset($readyToPostResponse['error'])) {
                $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, 4, 16, 'fail');
                $returnMessage['status'] = false;
                $returnMessage['message'] = 'Error while making ar2 Apply split payment ready to post to sage';

                return $returnMessage;
            }
            if ($isLiveApiCallStep4) {
                $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, 4, 16);
            }

            // 5
            $isLiveApiCallStep5 = true;
            if (isset($sageLogArray[5]) && $sageLogArray[5]['status'] == 'success') {
                $isLiveApiCallStep5 = false;
                $postedResponse = json_decode($sageLogArray[5]['response'], true);
            } else {
                $aRPostInvoices = SagePayloadFactory::aRPostInvoices($batchNumber);
                $resp = $this->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                $postedResponse = json_decode($resp, true);
            }
            if (isset($postedResponse['error'])) {
                $returnMessage['status'] = false;
                $returnMessage['message'] = 'Error while making ar2 Apply split payment Posted to sage';
                $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, 5, 16, 'fail');

                return $returnMessage;
            } else {
                if ($isLiveApiCallStep5) {
                    $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, 5, 16);
                }
            }
        }

        /* createAPInvoicePrem */

        // total_payments = 1 means upfront payment

        if ($payment->frequency == 'upfront') {
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

        if (strtolower($sageRequest->invoicePaymentStatus) == 'paid' && $payment->frequency == 'upfront') {
            $totalSteps = 13;

            //11
            $currentStep = 11;
            $isLiveApiCallStep11 = true;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                $isLiveApiCallStep11 = false;
                $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
            } else {

                $payLoadOptions = SagePayloadFactory::createPaymontRecieptOneInvoice($sageRequest);
                $resp = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
                $postedResponse = json_decode($resp, true);
            }

            if ($isLiveApiCallStep11) {
                $this->logSageApiCall($payLoadOptions, $postedResponse, $quote, $currentStep, $totalSteps);
            }

            if (isset($postedResponse['error'])) {
                $this->logSageApiCall($payLoadOptions, $postedResponse, $quote, $currentStep, $totalSteps, 'fail');
                $returnMessage['status'] = false;
                $returnMessage['message'] = 'Error while making split prepayments to sage';

                return $returnMessage;
            }

            $batchNumber = $postedResponse['BatchNumber'];

            //12
            $currentStep = 12;
            $isLiveApiCallStep12 = true;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                $isLiveApiCallStep12 = false;
                $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
            } else {
                $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr($batchNumber);
                $readyToPostResponse = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps, 'fail');
                $returnMessage['status'] = false;
                $returnMessage['message'] = 'Error while making Apply payment ready to post to sage';

                return $returnMessage;
            } else {
                if ($isLiveApiCallStep12) {
                    $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps);
                }
            }

            //13
            $currentStep = 13;
            $isLiveApiCallStep13 = true;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                $isLiveApiCallStep13 = false;
                $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
            } else {
                $aRPostReceipts = SagePayloadFactory::aRPostReceipts($batchNumber);
                $resp = $this->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }

            if (isset($postedResponse['error'])) {
                $returnMessage['status'] = false;
                $returnMessage['message'] = 'Error while making Apply payment Posted to sage';
                $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $currentStep, $totalSteps, 'fail');

                return $returnMessage;
            }
            if ($isLiveApiCallStep13) {
                $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $currentStep, $totalSteps);
            }
        }

        if (strtolower($sageRequest->invoicePaymentStatus) == 'paid' && $payment->frequency == 'split_payments') {
            $totalSteps = 13;

            //11
            $currentStep = 11;
            $isLiveApiCallStep11 = true;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                $isLiveApiCallStep11 = false;
                $response = json_decode($sageLogArray[$currentStep]['response'], true);
            } else {
                $readyToPostReceiptAr = SagePayloadFactory::arSplitPrepaymentPayload($quote, $sageCustomerNumber, $payment, $paymentSplits, true);

                $resp = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'POST');
                $response = json_decode($resp, true);
            }

            if (isset($response['error'])) {
                $this->logSageApiCall($readyToPostReceiptAr, $response, $quote, $currentStep, $totalSteps, 'fail');
                $returnMessage['status'] = false;
                $returnMessage['message'] = 'Error while making Apply split prepayments to sage';

                return $returnMessage;
            }
            if ($isLiveApiCallStep11) {
                $this->logSageApiCall($readyToPostReceiptAr, $response, $quote, $currentStep, $totalSteps);
            }

            $batchNumber = $response['BatchNumber'];
            //12
            $currentStep = 12;
            $isLiveApiCallStep12 = true;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                $isLiveApiCallStep12 = false;
                $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
            } else {
                $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr($batchNumber);
                $readyToPostResponse = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps, 'fail');
                $returnMessage['status'] = false;
                $returnMessage['message'] = 'Error while making Apply payment ready to post to sage';

                return $returnMessage;
            } else {
                if ($isLiveApiCallStep12) {
                    $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps);
                }
            }

            //13
            $currentStep = 13;
            $isLiveApiCallStep13 = true;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                $isLiveApiCallStep13 = false;
                $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
            } else {
                $aRPostReceipts = SagePayloadFactory::aRPostReceipts($batchNumber);
                $resp = $this->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }

            if (isset($postedResponse['error'])) {
                $returnMessage['status'] = false;
                $returnMessage['message'] = 'Error while making Apply payment Posted to sage';
                $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $currentStep, $totalSteps, 'fail');

                return $returnMessage;
            }
            if ($isLiveApiCallStep13) {
                $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $currentStep, $totalSteps);
            }
        }

        if (! in_array($payment->frequency, ['upfront', 'split_payments']) && in_array($paymentSplits[0]['payment_status_id'], [PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED])) {

            $totalSteps = 13;

            //11
            $currentStep = 11;
            $isLiveApiCallStep11 = true;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                $isLiveApiCallStep11 = false;
                $response = json_decode($sageLogArray[$currentStep]['response'], true);
            } else {
                $readyToPostReceiptAr = SagePayloadFactory::arSplitPrepaymentPayload($quote, $sageCustomerNumber, $payment, $paymentSplits, false);

                $resp = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'POST');
                $response = json_decode($resp, true);
            }

            if (isset($response['error'])) {
                $this->logSageApiCall($readyToPostReceiptAr, $response, $quote, $currentStep, $totalSteps, 'fail');
                $returnMessage['status'] = false;
                $returnMessage['message'] = 'Error while making Apply split prepayments to sage';

                return $returnMessage;
            }
            if ($isLiveApiCallStep11) {
                $this->logSageApiCall($readyToPostReceiptAr, $response, $quote, $currentStep, $totalSteps);
            }

            $batchNumber = $response['BatchNumber'];
            //12
            $currentStep = 12;
            $isLiveApiCallStep12 = true;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                $isLiveApiCallStep12 = false;
                $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
            } else {
                $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr($batchNumber);
                $readyToPostResponse = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps, 'fail');
                $returnMessage['status'] = false;
                $returnMessage['message'] = 'Error while making Apply payment ready to post to sage';

                return $returnMessage;
            } else {
                if ($isLiveApiCallStep12) {
                    $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps);
                }
            }

            //13
            $currentStep = 13;
            $isLiveApiCallStep13 = true;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                $isLiveApiCallStep13 = false;
                $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
            } else {
                $aRPostReceipts = SagePayloadFactory::aRPostReceipts($batchNumber);
                $resp = $this->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }

            if (isset($postedResponse['error'])) {
                $returnMessage['status'] = false;
                $returnMessage['message'] = 'Error while making Apply payment Posted to sage';
                $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $currentStep, $totalSteps, 'fail');

                return $returnMessage;
            }
            if ($isLiveApiCallStep13) {
                $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $currentStep, $totalSteps);
            }
        }

        return ['status' => true, 'message' => 'Policy Booked'];
    }
}
