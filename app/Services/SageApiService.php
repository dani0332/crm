<?php

namespace App\Services;

use App\Factories\SagePayloadFactory;
use App\Models\Customer;
use App\Models\Lookup;
use App\Models\User;
use App\Traits\SageLoggable;
use Illuminate\Support\Facades\Auth;

class SageApiService
{
    use SageLoggable;

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
        $sageRequest = new \stdClass();

        // $sageRequest->discount = 22;
        $sageRequest->discount = floatval($payment->discount_value);
        $sageRequest->invoiceDescription = $payment->invoice_description;
        $sageRequest->bookingDate = date('Y-m-d', strtotime($quote['policy_booking_date']));
        $sageRequest->policyExpiryDate = date('Ymd', strtotime($quote['renewal_expiry_date']));
        $sageRequest->insurerInvoiceDate = date('Y-m-d', strtotime($payment->insurer_invoice_date));
        $sageRequest->paymentDueDate = date('Y-m-d', strtotime($paymentSplits->due_date));

        $sageRequest->mainClassInsurance = $modelType;
        $sageRequest->policyNumber = $quote->policy_number;

        $sageRequest->policyIssuer = Auth::user()->name;
        $sageRequest->requestType = Lookup::where('id', $quote->transaction_type_id)->first()->text;
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

        $sageRequest->insurerTaxInvoiceNumber = (string) $payment['tax_invoice_number'];
        $sageRequest->insurerPremiumTaxInvoiceNumber = (string) $payment['insurer_commmission_invoice_number'];
        // $sageRequest->insurerTaxInvoiceNumber = (string) rand(1000, 9999);
        // $sageRequest->insurerPremiumTaxInvoiceNumber = (string) rand(1000, 9999);
        if ($paymentSplits->first()) {
            $sageRequest->sage_reciept_id = $paymentSplits['sage_reciept_id'];
        }

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
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
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
}
