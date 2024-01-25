<?php

namespace App\Services;

use App\Factories\SagePayloadFactory;
use App\Models\Customer;
use App\Models\Lookup;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SageApiService
{
    protected $sageLogin;
    protected $sagePassword;
    protected $sageRequestUrl;

    public function __construct()
    {
        //Guzzle was not working for post request
        $this->sageLogin = env('SAGE_300_LOGIN');
        $this->sagePassword = env('SAGE_300_PASSWORD');
        $this->sageRequestUrl = env('SAGE_300_BASE_URL') . env('SAGE_300_VERSION');
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
        if (!empty($quote->advisor_id)) {

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
        // $sageRequest->insurerTaxInvoiceNumber = (string) rand(1000, 9999);
        // $sageRequest->insurerPremiumTaxInvoiceNumber = (string) rand(1000, 9999);
        if ($paymentSplits->first()) {
            $sageRequest->sage_reciept_id = $paymentSplits['sage_reciept_id'];
            $sageRequest->collection_amount = $paymentSplits['collection_amount'];
        }

        return $sageRequest;
    }

    public function verifySageCustomer($customerId, $data = null)
    {
        $customer = Customer::find($customerId);
        $customer->data = !empty($data) ? $data : [];
        if ($customer) {

            $payLoadOptions = SagePayloadFactory::createCustomerPayload($customer);
            info('custimerApi===payload===' . json_encode($payLoadOptions['payload']));
            $jsonResponse = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            info('custimerApi===resp===' . $jsonResponse);
            $response = json_decode($jsonResponse, true);

            if (isset($response['error']['code']) && $response['error']['code'] == 'RecordDuplicate') {
                return $payLoadOptions['customerNumber'];
            } elseif (isset($response['CustomerNumber'])) {
                return $response['CustomerNumber'];
            } else {
                return false;
            }
        }
    }

    /*
    public function verifySageCustomer($customerId)
    {
        $customer = Customer::find($customerId);
        if ($customer) {
            $payLoadOptions = SagePayloadFactory::createCustomerPayload($customer);
            $jsonResponse = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);

            $response = json_decode($jsonResponse, true);
            //dd($response);
            if (isset($response['error']['code']) && $response['error']['code'] == 'RecordDuplicate') {
                return $payLoadOptions['customerNumber'];
            } elseif (isset($response['CustomerNumber'])) {
                return $response['CustomerNumber'];
            } else {
                return false;
            }
        }
    }*/

    public function postToSage300($endPoint, $payLoad, $verb = 'POST')
    {
        // Create the payload data for the POST request
        $sageEndPoint = $this->sageRequestUrl . $endPoint;
        info('endpoint-----------' . json_encode($sageEndPoint));
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
        info('response-----------' . json_encode($response));
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

        // sape customer number generation
        // $sageCustomerNumber = $this->verifySageCustomer(57, $data);
        $sageCustomerNumber = $this->verifySageCustomer($quote->customer_id, $data);
        // $sageCustomerNumber = "P30581";


        if ($sageCustomerNumber) {

            $sageRequest->customerId = $sageCustomerNumber;

            // /* createARInvoicePremAndComm */
            $createARInvoicePremAndCommPayload = SagePayloadFactory::createARInvoicePremAndComm($sageRequest);

            info('createARInvoicePremAndComm===payload===' . json_encode($createARInvoicePremAndCommPayload));

            $resp = $this->postToSage300($createARInvoicePremAndCommPayload['endPoint'], $createARInvoicePremAndCommPayload['payload']);

            info('createARInvoicePremAndComm===resp===' . $resp);

            $response = json_decode($resp, true);

            if (!empty($response['BatchNumber'])) {
                /* readyToPostInvoiceAr */
                $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr($response['BatchNumber']);

                info('readyToPostInvoiceAr===payload===' . json_encode($readyToPostInvoiceAr));

                $resp = $this->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');

                info('readyToPostInvoiceAr===resp===' . $resp);

                /* aRPostInvoices */
                $aRPostInvoices = SagePayloadFactory::aRPostInvoices($response['BatchNumber']);

                info('aRPostInvoices===payload===' . json_encode($aRPostInvoices));

                $resp = $this->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);

                info('aRPostInvoices===resp===' . $resp);
            }

            /* createAPInvoicePrem */
            $createAPInvoicePrem = SagePayloadFactory::createAPInvoicePrem($sageRequest);

            info('createAPInvoicePrem===payload===' . json_encode($createAPInvoicePrem));

            $resp = $this->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);

            info('createAPInvoicePrem===resp===' . $resp);

            $response = json_decode($resp, true);

            if (!empty($response['BatchNumber'])) {

                /* readyToPostInvoiceAP */
                $readyToPostInvoiceAP = SagePayloadFactory::readyToPostInvoiceAP($response['BatchNumber']);

                info('readyToPostInvoiceAP===payload===' . json_encode($readyToPostInvoiceAP));

                $resp = $this->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');

                info('readyToPostInvoiceAP===resp===' . $resp);

                /* aPPostInvoices */
                $aPPostInvoices = SagePayloadFactory::aPPostInvoices($response['BatchNumber']);

                info('aPPostInvoices===payload===' . json_encode($aPPostInvoices));
                $resp = $this->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);

                info('aPPostInvoices===resp===' . $resp);
            }

            if ($sageRequest->discount > 0) {
                /* createARInvoiceDis */
                $createARInvoiceDis = SagePayloadFactory::createARInvoiceDis($sageRequest);
                info('createARInvoiceDis===payload===' . json_encode($createARInvoiceDis));
                $resp = $this->postToSage300($createARInvoiceDis['endPoint'], $createARInvoiceDis['payload']);
                info('createARInvoiceDis===resp===' . $resp);

                $response = json_decode($resp, true);

                if (!empty($response['BatchNumber'])) {
                    /* readyToPostInvoiceAr */
                    $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr($response['BatchNumber']);

                    info('readyToPostInvoiceAr===disc===payload===' . json_encode($readyToPostInvoiceAr));

                    $resp = $this->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');

                    info('readyToPostInvoiceAr===disc====resp===' . $resp);

                    /* aRPostInvoices */
                    $aRPostInvoices = SagePayloadFactory::aRPostInvoices($response['BatchNumber']);

                    info('aRPostInvoices===disc===payload===' . json_encode($aRPostInvoices));

                    $resp = $this->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);

                    info('aRPostInvoices===disc===resp===' . $resp);
                }
            }

            if (strtolower($sageRequest->invoicePaymentStatus) == 'paid') {
                /* createPaymontRecieptOneInvoice */
                $createPaymontRecieptOneInvoice = SagePayloadFactory::createPaymontRecieptOneInvoice($sageRequest);
                info('createPaymontRecieptOneInvoice===payload===' . json_encode($createPaymontRecieptOneInvoice));

                $resp = $this->postToSage300($createPaymontRecieptOneInvoice['endPoint'], $createPaymontRecieptOneInvoice['payload']);

                info('createPaymontRecieptOneInvoice===resp===' . $resp);

                $response = json_decode($resp, true);
                if (!empty($response['BatchNumber'])) {
                    /* readyToPostReceiptAr */
                    $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr($response['BatchNumber']);

                    info('readyToPostReceiptAr===payload===' . json_encode($readyToPostReceiptAr));

                    $resp = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');

                    info('readyToPostReceiptAr===resp===' . $resp);

                    /* aRPostInvoices */
                    $aRPostReceipts = SagePayloadFactory::aRPostReceipts($response['BatchNumber']);

                    info('aRPostReceipts===payload===' . json_encode($aRPostReceipts));

                    $resp = $this->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);

                    info('aRPostReceipts===resp===' . $resp);
                }
            }
            return ['status' => true, 'message' => 'posted to sage'];
        } else {
            return ['status' => false, 'message' => 'Customer not found in sage'];
        }
    }
}
