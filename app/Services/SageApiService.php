<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\SageEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Factories\SagePayloadFactory;
use App\Models\ApplicationStorage;
use App\Models\BusinessInsuranceType;
use App\Models\Customer;
use App\Models\Lookup;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\QuoteRequestEntityMapping;
use App\Models\SageApiLog;
use App\Models\SendUpdateLog;
use App\Models\User;
use App\Repositories\PaymentRepository;
use App\Repositories\SageApiLogRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\SageLoggable;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SageApiService
{
    use GenericQueriesAllLobs;
    use SageLoggable, TeamHierarchyTrait;

    protected $sageLogin;
    protected $sagePassword;
    protected $sageRequestUrl;
    protected $sageBatchNumber;
    protected $sageDBName;
    protected $recursiveCallStatus;

    public function __construct()
    {
        //Guzzle was not working for post request
        $this->sageLogin = env('SAGE_300_LOGIN');
        $this->sagePassword = env('SAGE_300_PASSWORD');
        $this->sageRequestUrl = env('SAGE_300_BASE_URL').env('SAGE_300_VERSION');
        $this->sageDBName = env('SAGE_300_CUSTOM_API_DB_NAME');
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
            $latestEndorsementCode = $latestEndorsement?->code;
        }

        $businessTypeOfInsuranceCode = '';
        if ($quote->business_type_of_insurance_id) {
            $businessTypeOfInsurance = BusinessInsuranceType::find($quote->business_type_of_insurance_id);
            $businessTypeOfInsuranceCode = $businessTypeOfInsurance->code;
        }

        $sageRequest = new \stdClass;

        // $sageRequest->discount = 2;
        $sageRequest->discount = floatval($payment->discount_value);
        $sageRequest->invoiceDescription = $payment->invoice_description;
        $sageRequest->bookingDate = $quote['policy_booking_date'] ? date(env('DATE_FORMAT_ONLY'), strtotime($quote['policy_booking_date'])) : Carbon::now()->format(env('DATE_FORMAT_ONLY'));
        $sageRequest->policyBookingDate = $quote['policy_booking_date'] ? date(env('SAGE_300_CUSTOM_API_DATE_FORMAT'), strtotime($quote['policy_booking_date'])) : Carbon::now()->format(env('SAGE_300_CUSTOM_API_DATE_FORMAT'));
        $sageRequest->policyExpiryDate = date(env('SAGE_300_CUSTOM_API_DATE_FORMAT'), strtotime($quote['policy_expiry_date']));
        $sageRequest->insurerInvoiceDate = date(env('DATE_FORMAT_ONLY'), strtotime($payment->insurer_invoice_date));

        if (! empty($paymentSplits)) {
            $sageRequest->paymentDueDate = date(env('DATE_FORMAT_ONLY'), strtotime($paymentSplits[0]['due_date']));
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
        $response = '';
        $customerPayload = [
            'sage_request_type' => SageEnum::SRT_CREATE_CUSTOMER,
            'entry_type' => SageEnum::SCT_STRAIGHT,
            'endPoint' => SageEnum::END_POINT_AR_CUSTOMER,
            'payload' => [],
        ];

        $sageCustomerNumber = false;
        $sageLogArray = $quote->sageApiLogs->keyBy('step')->toArray();

        $customer = Customer::find($quote->customer_id);
        $customerData = ['quoteTypeId' => $quoteTypeId, 'id' => $quote->id];

        $quoteEntityMapping = QuoteRequestEntityMapping::with('entity')->where(['quote_type_id' => $quoteTypeId, 'quote_request_id' => $quote->id])->first();
        $quoteEntity = $quoteEntityMapping?->entity;
        if ($quoteEntity) {
            $customerData['entity'] = $quoteEntity;
            if ($quoteEntity->sage_customer_number) {
                $this->logSageApiCall($customerPayload, $response, $quote, 1, $totalSteps);

                return $quoteEntity->sage_customer_number;
            }
        }

        if ($customer) {
            $customer->data = $customerData;

            if ($customer->sage_customer_number && ! $quoteEntity) {
                $this->logSageApiCall($customerPayload, $response, $quote, 1, $totalSteps);

                return $customer->sage_customer_number;
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
                    $sageCustomerNumber = $response['CustomerNumber'];
                }

                if ($sageCustomerNumber) {
                    if ($isLiveApiCallStep1) {
                        $this->logSageApiCall($customerPayload, $response, $quote, 1, $totalSteps);
                    }
                } else {
                    $this->logSageApiCall($customerPayload, $response, $quote, 1, $totalSteps, 'fail');
                }
            }
        }
        if ($sageCustomerNumber) {
            unset($customer->data);
            if ($quoteEntity) {
                $quoteEntity->sage_customer_number = $sageCustomerNumber;
                $quoteEntity->save();
            } elseif ($customer) {
                $customer->sage_customer_number = $sageCustomerNumber;
                $customer->save();
            }
        }

        return $sageCustomerNumber;
    }

    public function verifySageCustomer($customerId, $data = null, $logModal = null, $sageLogArray = [], $totalSteps = 4, $advisorId = null)
    {
        $customer = Customer::find($customerId);
        $sageCustomerNumber = false;
        $payLoadOptions['endPoint'] = 'AR/ARCustomers';
        $payLoadOptions['payload'] = [];
        $response = '';
        $quoteEntityMapping = QuoteRequestEntityMapping::with('entity')->where(['quote_type_id' => $data['quoteTypeId'], 'quote_request_id' => $data['id']])->first();
        $quoteEntity = $quoteEntityMapping?->entity;
        if ($quoteEntity) {
            $data['entity'] = $quoteEntity;
            if ($quoteEntity->sage_customer_number) {
                $this->logSageApiCall($payLoadOptions, $response, $logModal, 1, $totalSteps, SageEnum::STATUS_SUCCESS, $advisorId);

                return $quoteEntity->sage_customer_number;
            }
        }

        if ($customer) {
            $customer->data = ! empty($data) ? $data : [];
            if ($customer->sage_customer_number && ! $quoteEntity) {
                $this->logSageApiCall($payLoadOptions, $response, $logModal, 1, $totalSteps, SageEnum::STATUS_SUCCESS, $advisorId);

                return $customer->sage_customer_number;
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
                        $this->logSageApiCall($payLoadOptions, $response, $logModal, 1, $totalSteps, SageEnum::STATUS_SUCCESS, $advisorId);
                    }
                } else {
                    $this->logSageApiCall($payLoadOptions, $response, $logModal, 1, $totalSteps, SageEnum::STATUS_FAIL, $advisorId);
                }
            }
        }
        if ($sageCustomerNumber) {
            unset($customer->data);
            if ($quoteEntity) {
                $quoteEntity->sage_customer_number = $sageCustomerNumber;
                $quoteEntity->save();
            } elseif ($customer) {
                $customer->sage_customer_number = $sageCustomerNumber;
                $customer->save();
            }
        }

        return $sageCustomerNumber;
    }

    public function postToSage300($endPoint, $payLoad, $verb = 'POST')
    {
        $sageEndPoint = $this->sageRequestUrl.$endPoint;
        $verb = strtoupper($verb);
        try {
            $response = match ($verb) {
                'PATCH' => Http::withBasicAuth($this->sageLogin, $this->sagePassword)
                    ->patch($sageEndPoint, $payLoad),
                'POST' => Http::withBasicAuth($this->sageLogin, $this->sagePassword)
                    ->post($sageEndPoint, $payLoad),
                default => Http::withBasicAuth($this->sageLogin, $this->sagePassword)
                    ->get($sageEndPoint, $payLoad),
            };

            return is_array($response->json()) ? json_encode($response->json()) : $response->body();
        } catch (\Exception $e) {
            return json_encode(['error' => ['message' => ['value' => $e->getMessage()]], 'code' => 500]);
        }
    }

    /*
     * this function is renamed and a new function is created with laravel http request for calling sage api
     *
     * will be removed once the 2nd function is matured.
     * */
    public function postToSage300Curl($endPoint, $payLoad, $verb = 'POST')
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

            // Need to update this code after Mirza's Implemenntation
            if ($extras['type'] == SageEnum::PT_SEND_UPDATE) {
                $sendUpdateLog = SendUpdateLog::where('id', $request->sendUpdateId)->first();
                $quoteDetails = [
                    'policy_booking_date' => $sendUpdateLog->booking_date,
                    'policy_expiry_date' => $sendUpdateLog->expiry_date,
                    'policy_number' => $sendUpdateLog->policy_number,
                    'transaction_type_id' => $quote->transaction_type_id,
                    'advisor_id' => $sendUpdateLog->advisor_id,
                    'price_vat_applicable' => abs($getingPaymentDetails['payment']->total_price),
                    'price_with_vat' => abs($sendUpdateLog->price_with_vat),
                    'insly_migrated' => $quote->insly_migrated,
                    'insurance_provider_id' => $sendUpdateLog->insurance_provider_id,
                    'booking_filled_by' => $sendUpdateLog->booking_filled_by,
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
                $getQuoteDetails = $this->getQuoteObjectBy($request->quoteType, $request->quoteUuid, 'uuid');
                $checkInslyMigratedLead = $this->checkInslyMigratedLead($request);

                if ($checkInslyMigratedLead) {
                    info('Book Update - Creating payment details based on the send update - The lead originated from Insly. QuoteType: '.$request->quoteType.' - QuoteUUID: '.$request->quoteUuid.' - SendUpdateUUID: '.$extras['send_update_log']->uuid);
                    $payment = new Payment;
                    $splitPayments = collect([new PaymentSplits]);

                } else {
                    // If we don't have payment details then we fetched it from the Main Lead
                    info('Book Update - Fetching Payment details from Main Lead. QuoteType: '.$request->quoteType.' - QuoteUUID: '.$request->quoteUuid.' - SendUpdateUUID: '.$extras['send_update_log']->uuid);

                    $getQuoteDetails->load(['payments' => function ($query) {
                        $query->whereNull('send_update_log_id');
                    }, 'payments.paymentSplits']);

                    $payment = $getQuoteDetails->payments->first();
                    $splitPayments = $payment->paymentSplits;
                }

                $payment->fill([
                    'discount_value' => $extras['send_update_log']->discount,
                    'invoice_description' => $extras['send_update_log']->invoice_description,
                    'insurer_invoice_date' => $extras['send_update_log']->invoice_date,
                    'commission_vat' => abs($extras['send_update_log']->vat_on_commission),
                    'total_price' => abs($extras['send_update_log']->price_without_vat),
                    'total_amount' => abs($extras['send_update_log']->price_vat_applicable),
                    'commission' => abs($extras['send_update_log']->total_commission),
                    'commission_vat_applicable' => abs($extras['send_update_log']->commission_vat_applicable),
                    'commission_vat_not_applicable' => abs($extras['send_update_log']->commission_vat_not_applicable),
                    'commmission_percentage' => abs($extras['send_update_log']->commission_percentage),
                    'insurer_tax_number' => $extras['send_update_log']->insurer_tax_invoice_number,
                    'insurer_commmission_invoice_number' => $extras['send_update_log']->insurer_commission_invoice_number,
                    'policy_expiry_date' => $extras['send_update_log']->expiry_date,
                    'broker_invoice_number' => $extras['send_update_log']->broker_invoice_number,
                    'frequency' => PaymentFrequency::UPFRONT,
                ]);

                $splitPayments->first()->fill([
                    'due_date' => $extras['send_update_log']->invoice_date,
                    'payment_amount' => abs($extras['send_update_log']->price_vat_applicable),
                    'collection_amount' => abs($extras['send_update_log']->price_vat_applicable),
                    'commission_vat_applicable' => ($payment->commission - $payment->commission_vat),
                    'commission_vat' => $payment->commission_vat,
                ]);

                $mainLeadDetails = [
                    'payment' => [
                        'insurer_tax_number' => $payment->insurer_tax_number,
                        'insurer_commmission_invoice_number' => $payment->insurer_commmission_invoice_number,
                    ],
                ];

                $response = ['payment' => $payment, 'splitPayments' => $splitPayments, 'mainLeadDetails' => $mainLeadDetails];

                // Reminder: price_vat_applicable > 0 => Debit Note, if negative then should be Credit Note
                if ($checkInslyMigratedLead && ($extras['send_update_log']->price_vat_applicable > 0)) {
                    unset($response['mainLeadDetails']);
                }

                return $response;
            }
        }
    }

    public function checkInslyMigratedLead($request)
    {
        $inslyMigrated = false;
        if ($request->inslyMigrated) {
            $inslyMigrated = true;
        } else {
            $getQuoteDetails = $this->getQuoteDetailObject($request->quoteType, $request->quoteRefId);
            $inslyMigrated = ! empty($getQuoteDetails->insly_id) && $getQuoteDetails->insly_id != null;
        }

        return $inslyMigrated;
    }

    private function handleSendUpdateCalls($quote, $sageRequestPayload, $payment, $splitPayments, $extras)
    {
        $quoteModelObject = ! empty($extras['send_update_log']) ? $extras['send_update_log'] : $quote;

        if ($extras['send_update_type'] == SageEnum::SUT_REVE_CORR) {
            $getPaymentByInsurerInvoiceNumber = PaymentRepository::getPaymentByInsurerInvoiceNumber($quote, $extras['reverse_invoice']);
            $getReverseInvoiceRelation = [];

            if ($getPaymentByInsurerInvoiceNumber) {
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
            } else {
                $reverseSendUpdate = SendUpdateLog::where('insurer_tax_invoice_number', $extras['reverse_invoice'])->first();
                $getReverseInvoiceRelation = (! empty($reverseSendUpdate)) ? [
                    'section_type' => $quoteModelObject->getMorphClass(),
                    'section_id' => $reverseSendUpdate->id,
                ] : [];
            }

            if (empty($getReverseInvoiceRelation)) {
                logger()->error('Book Update - Reverse Invoice not Found. SendUpdateUUID: '.$extras['send_update_log']->uuid.' - ReverseInvoice: '.$extras['reverse_invoice']);

                return ['status' => false, 'message' => 'Reverse Invoice not found'];
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
                'extras' => [
                    'option_id' => $extras['option'] ?? null,
                    'authDetails' => $extras['authDetails'] ?? [],

                ],
            ]);

            if ($extras['option'] !== SendUpdateLogStatusEnum::ACB) {
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
                    'extras' => [
                        'option_id' => $extras['option'] ?? null,
                        'authDetails' => $extras['authDetails'] ?? [],
                    ],
                ]);
            }

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
                'mainLeadDetails' => $extras['mainLeadDetails'] ?? [],
                'extras' => [
                    'option_id' => $extras['option'] ?? null,
                    'authDetails' => $extras['authDetails'] ?? [],
                ],
            ]);

            info('Book Update - Creating AP Split Payment Invoice and mark as posted');
            $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                'iterator' => 0,
                'lastIteration' => 2,
                'startingStep' => ($startingStep + 4),
                'totalSteps' => $totalSteps,
                'entryType' => SageEnum::SCT_STRAIGHT,
                'requestType' => SageEnum::SRT_CREATE_AP_SPPAY_INV,
                'payment' => $payment,
                'splitPayments' => $splitPayments,
                'sendUpdateLog' => $extras['send_update_log'] ?? [],
                'mainLeadDetails' => $extras['mainLeadDetails'] ?? [],
                'extras' => [
                    'option_id' => $extras['option'] ?? null,
                    'authDetails' => $extras['authDetails'] ?? [],
                ],
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
                'mainLeadDetails' => $extras['mainLeadDetails'] ?? [],
                'extras' => [
                    'authDetails' => $extras['authDetails'] ?? [],
                ],
            ]);

            $totalSteps = $startingStep == 8 ? 13 : 15;
            $startingStep = ($startingStep + 3);
        }

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

        $isOnlyDiscountReversal = false;
        $isOnlyDiscount = false;
        $upFrontTotalSteps = 21;
        $nonUpFrontTotalSteps = 23;

        if (in_array(SageEnum::SRT_CREATE_AR_DISC_INV, $reverseSendUpdateTypes)) {
            $isOnlyDiscountReversal = $sendUpdateLog && (int) $sendUpdateLog->discount == 0;
            $upFrontTotalSteps = ($isOnlyDiscountReversal) ? 18 : 21;
            $nonUpFrontTotalSteps = ($isOnlyDiscountReversal) ? 20 : 23;
        } else {
            if ($sendUpdateLog->discount > 0) {
                $isOnlyDiscount = true;
                $upFrontTotalSteps = 17;
                $nonUpFrontTotalSteps = 19;
            }
        }

        foreach ($reverseSendUpdateTypes as $reverseSendUpdateTypeKey => $reverseSendUpdateType) {
            $invoiceResponse = json_decode($invoicesForReverse[$reverseSendUpdateTypeKey]['response']);

            if ($payment->frequency == SageEnum::SF_UPFRONT) {
                if (in_array($reverseSendUpdateType, $checkARInvoices)) {
                    info('Book Update - Creating AR Reverse and Correction Invoices and mark as posted');
                    $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                        'iterator' => 0,
                        'lastIteration' => 6,
                        'startingStep' => 1,
                        'totalSteps' => $upFrontTotalSteps,
                        'batchNumber' => $invoiceResponse->BatchNumber,
                        'entryType' => SageEnum::SCT_STRAIGHT,
                        'invoiceType' => SageEnum::SRT_GET_AR_INVOICE,
                        'requestType' => SageEnum::SRT_REV_CORR_AR_PREM_COMM_INV,
                        'sendUpdateLog' => $extras['send_update_log'] ?? [],
                        'reversalInvoice' => collect($invoicesForReverse)->whereIn('sage_request_type', $checkARInvoices)->first() ?? [],
                        'extras' => [
                            'option_id' => $extras['option'] ?? null,
                            'authDetails' => $extras['authDetails'] ?? [],
                        ],
                    ]);
                }

                if (in_array($reverseSendUpdateType, $checkAPInvoices)) {
                    info('Book Update - Creating AP Reverse and Correction Invoices and mark as posted');
                    $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                        'iterator' => 0,
                        'lastIteration' => 6,
                        'startingStep' => 8,
                        'totalSteps' => $upFrontTotalSteps,
                        'batchNumber' => $invoiceResponse->BatchNumber,
                        'entryType' => SageEnum::SCT_STRAIGHT,
                        'invoiceType' => SageEnum::SRT_GET_AP_INVOICE,
                        'requestType' => SageEnum::SRT_REV_CORR_AP_PREM_INV,
                        'sendUpdateLog' => $extras['send_update_log'] ?? [],
                        'reversalInvoice' => collect($invoicesForReverse)->whereIn('sage_request_type', $checkAPInvoices)->first() ?? [],
                        'extras' => [
                            'option_id' => $extras['option'] ?? null,
                            'authDetails' => $extras['authDetails'] ?? [],
                        ],
                    ]);
                }

                $startingStep = 15;
                $totalSteps = $upFrontTotalSteps;
            } else {
                if (in_array($reverseSendUpdateType, $checkARInvoices)) {
                    info('Book Update - Creating AR Reverse and Correction Split Payment Invoices and mark as posted');
                    $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                        'iterator' => 0,
                        'lastIteration' => 7,
                        'startingStep' => 1,
                        'totalSteps' => $nonUpFrontTotalSteps,
                        'batchNumber' => $invoiceResponse->BatchNumber,
                        'entryType' => SageEnum::SCT_STRAIGHT,
                        'invoiceType' => SageEnum::SRT_GET_AR_INVOICE,
                        'requestType' => SageEnum::SRT_REV_CORR_AR_SPPAY_INV,
                        'payment' => $payment,
                        'splitPayments' => $splitPayments,
                        'sendUpdateLog' => $extras['send_update_log'] ?? [],
                        'reversalInvoice' => collect($invoicesForReverse)->whereIn('sage_request_type', $checkARInvoices)->first() ?? [],
                        'extras' => [
                            'option_id' => $extras['option'] ?? null,
                            'authDetails' => $extras['authDetails'] ?? [],
                        ],
                    ]);
                }

                if (in_array($reverseSendUpdateType, $checkAPInvoices)) {
                    info('Book Update - Creating AP Reverse and Correction Split Payment Invoices and mark as posted');
                    // This Split Invoice for Reverse and Correction need to be tested
                    $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                        'iterator' => 0,
                        'lastIteration' => 7,
                        'startingStep' => 9,
                        'totalSteps' => $nonUpFrontTotalSteps,
                        'batchNumber' => $invoiceResponse->BatchNumber,
                        'entryType' => SageEnum::SCT_STRAIGHT,
                        'invoiceType' => SageEnum::SRT_GET_AP_INVOICE,
                        'requestType' => SageEnum::SRT_REV_CORR_AP_SPPAY_INV,
                        'payment' => $payment,
                        'splitPayments' => $splitPayments,
                        'sendUpdateLog' => $extras['send_update_log'] ?? [],
                        'reversalInvoice' => collect($invoicesForReverse)->whereIn('sage_request_type', $checkAPInvoices)->first() ?? [],
                        'extras' => [
                            'option_id' => $extras['option'] ?? null,
                            'authDetails' => $extras['authDetails'] ?? [],
                        ],
                    ]);
                }

                $startingStep = 17;
                $totalSteps = $nonUpFrontTotalSteps;
            }

            if ($reverseSendUpdateType == SageEnum::SRT_CREATE_AR_DISC_INV) {
                $lastIteration = $isOnlyDiscountReversal ? 3 : 6;
                info('Book Update - Creating AR '.($isOnlyDiscountReversal ? 'Reversal Invoice' : 'Reversal and Correction Invoices').' for Discount and mark as posted');
                $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                    'iterator' => 0,
                    'lastIteration' => $lastIteration,
                    'startingStep' => $startingStep,
                    'totalSteps' => $totalSteps,
                    'batchNumber' => $invoiceResponse->BatchNumber,
                    'entryType' => SageEnum::SCT_STRAIGHT,
                    'invoiceType' => SageEnum::SRT_GET_AR_INVOICE,
                    'requestType' => SageEnum::SRT_REV_CORR_AR_DIS_INV,
                    'sendUpdateLog' => $extras['send_update_log'] ?? [],
                    'reversalInvoice' => collect($invoicesForReverse)->where('sage_request_type', SageEnum::SRT_CREATE_AR_DISC_INV)->first() ?? [],
                    'extras' => [
                        'authDetails' => $extras['authDetails'] ?? [],
                    ],
                ]);
            }
        }

        if ($isOnlyDiscount) {
            info('Book Update - Creating AR Discount Invoice and mark as posted');
            $this->sageRecursiveCalls($quote, $sageRequestPayload, $sageLogArray, [
                'iterator' => 0,
                'lastIteration' => 2,
                'startingStep' => $startingStep,
                'totalSteps' => $totalSteps,
                'entryType' => SageEnum::SCT_STRAIGHT,
                'requestType' => SageEnum::SRT_CREATE_AR_DISC_INV,
                'sendUpdateLog' => $extras['send_update_log'] ?? [],
                'reversalInvoice' => [],
                'extras' => [
                    'authDetails' => $extras['authDetails'] ?? [],
                    'only_correction' => true,
                ],
            ]);
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

            // $invoiceType = in_array($extraParams['requestType'], [SageEnum::SRT_CREATE_AR_SPPAY_INV, SageEnum::SRT_REV_CORR_AR_SPPAY_INV]) ? SageEnum::AR_INVOICE : SageEnum::AP_INVOICE;
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
                    $payLoadOptions = SagePayloadFactory::{$methodName}($requestParms, $sageEntryType, $sageInvResponse, [
                        'mainLeadDetails' => $extraParams['mainLeadDetails'] ?? [],
                        'extras' => $extraParams['extras'] ?? [],
                    ]);
                    break;

                case 'arSplitPrepaymentPayload':
                    $payLoadOptions = SagePayloadFactory::{$methodName}($quote, $sageRequestPayload->customerId, $extraParams['payment'], $extraParams['splitPayments'], true);
                    break;

                default:
                    if (in_array($extraParams['requestType'], [SageEnum::SRT_CREATE_AR_DISC_INV, SageEnum::SRT_REV_CORR_AR_DIS_INV])) {
                        $payLoadOptions = SagePayloadFactory::{$methodName}($requestParms, $sageEntryType, SageEnum::SCT_DISCOUNT, [
                            'sage_request_type' => $extraParams['requestType'],
                            'extras' => $extraParams['extras'] ?? [],
                        ]);
                    } else {
                        if ($methodName == 'createPaymontRecieptOneInvoice') {
                            $payLoadOptions = SagePayloadFactory::{$methodName}($quote, $sageRequestPayload->customerId, $extraParams['payment'], $extraParams['splitPayments'], true);
                        } else {
                            $payLoadOptions = SagePayloadFactory::{$methodName}($requestParms, $sageEntryType, SageEnum::SCT_STRAIGHT, [
                                'sage_request_type' => $extraParams['requestType'],
                                'extras' => $extraParams['extras'] ?? [],
                            ]);
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
            // Reminder:: Retrigger Post Invoice if it's not already posted
            $isPostingCall = false;
            $batchStatus = SageEnum::SAGE_STATUS_OPEN;
            if (isset($sageLogArray[$sageLogKey]) && $sageLogArray[$sageLogKey]['status'] == SageEnum::STATUS_FAIL && in_array($methodName, ['aRPostInvoices', 'aPPostInvoices'])) {
                $urlForGetInvoice = ($methodName == 'aRPostInvoices') ? 'AR/ARInvoiceBatches('.$payLoadOptions['payload']['PostBatchFrom'].')' : 'AP/APInvoiceBatches('.$payLoadOptions['payload']['FromBatch'].')';
                $invoiceResponse = json_decode($this->postToSage300($urlForGetInvoice, [], 'GET'), true);
                $batchStatus = $invoiceResponse['BatchStatus'];
                $isPostingCall = true;
            }

            if ($isPostingCall && $batchStatus == SageEnum::SAGE_STATUS_POSTED) {
                $sageResponse = $payLoadOptions['payload'];
                info('Book Update - Sage API Call By Pass (Status already '.SageEnum::SAGE_STATUS_POSTED.') - Method Name ('.$methodName.') - QuoteUUID: '.$quote->uuid.' - SendUpdateUUID: '.$extraParams['sendUpdateLog']->uuid);
            } else {
                $resp = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload'] ?? [], $sageAPIsParams['extraDetails'][$methodName]['verb'] ?? 'POST');
                $sageResponse = json_decode($resp, true);
                info('Book Update - Sage API Call - Method Name ('.$methodName.') - QuoteUUID: '.$quote->uuid.' - SendUpdateUUID: '.$extraParams['sendUpdateLog']->uuid);
            }
        }

        if (in_array($methodName, ['createARInvoicePremAndComm', 'createAPInvoicePrem', 'createARInvoiceDis', 'createPaymontRecieptOneInvoice', 'arSplitPrepaymentPayload']) && isset($sageResponse['BatchNumber'])) {
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
                $this->logSageApiCall($payLoadOptions, $respParams, $quoteObject, $extraParams['startingStep'], $extraParams['totalSteps'], SageEnum::STATUS_FAIL, $extraParams['extras']['authDetails']->id);

                $responseMessage = isset($respParams['error']['message']['value']) ?
                    $respParams['error']['message']['value'] : $sageAPIsParams['extraDetails'][$methodName]['errorMessage'];
                $_REQUEST['status'] = false;
                $_REQUEST['message'] = $responseMessage;
                $this->recursiveCallStatus = SageEnum::STATUS_FAIL;
                logger()->error('Book Update - Sage API Failed - Response: '.$responseMessage);

                return $_REQUEST;
            } else {
                if ($isLiveApiCall) {
                    $this->logSageApiCall($payLoadOptions, $respParams, $quoteObject, $extraParams['startingStep'], $extraParams['totalSteps'], SageEnum::STATUS_SUCCESS, $extraParams['extras']['authDetails']->id);
                }
            }
        }

        $extraParams['iterator'] = $extraParams['iterator'] + 1;
        $arrayKey = $arrayKey + 1;

        if ($methodName == 'getInvoiceDetails') {
            if (isset($sageResponse['BatchNumber'])) {
                $isFollowUpCondition = $sageResponse['BatchNumber'] == $extraParams['batchNumber'];
            } else {
                logger()->error('Book Update - Sage API Failed - Batch Number not found in response'.json_encode($sageResponse));
            }
        } else {
            $isFollowUpCondition = isset($sageAPIsParams['extraDetails'][$methodName]['nextCondition']) ?
                ! empty($sageResponse[$sageAPIsParams['extraDetails'][$methodName]['nextCondition']]) : true;
        }

        if ($isFollowUpCondition) {
            if ($isLiveApiCall) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $quoteObject, $extraParams['startingStep'], $extraParams['totalSteps'], SageEnum::STATUS_SUCCESS, $extraParams['extras']['authDetails']->id);
            }

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
                'extras' => $extraParams['extras'] ?? [],
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
            $this->logSageApiCall($payLoadOptions, $sageResponse, $quoteObject, $extraParams['startingStep'], $extraParams['totalSteps'], SageEnum::STATUS_FAIL, $extraParams['extras']['authDetails']->id);

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
        $isARInvoicesCalls = in_array($extras['requestType'], [SageEnum::SRT_CREATE_AR_SPPAY_INV, SageEnum::SRT_REV_CORR_AR_SPPAY_INV]);
        $processDetails = (in_array($extras['requestType'], [SageEnum::SRT_CREATE_AR_SPPAY_INV, SageEnum::SRT_REV_CORR_AR_SPPAY_INV])) ?
            ['methodName' => 'createARInvoiceSplitPayments', 'invoiceType' => SageEnum::AR_INVOICE, 'url' => 'AR/ARInvoiceBatches', 'requestType' => SageEnum::SRT_AR_SPPAY_PAY_SCDULE_PATCH, 'entryType' => ($extras['requestType'] == SageEnum::SRT_CREATE_AR_SPPAY_INV) ? SageEnum::SCT_STRAIGHT : $extras['entryType']] :
            ['methodName' => 'createAPInvoiceSplitPayments', 'invoiceType' => SageEnum::AP_INVOICE, 'url' => 'AP/APInvoiceBatches', 'requestType' => SageEnum::SRT_AP_SPPAY_PAY_SCDULE_PATCH, 'entryType' => ($extras['requestType'] == SageEnum::SRT_CREATE_AP_SPPAY_INV) ? SageEnum::SCT_STRAIGHT : $extras['entryType']];

        if (isset($sageLogArray[$extras['startingStep']]) && $sageLogArray[$extras['startingStep']]['status'] == SageEnum::STATUS_SUCCESS) {
            $isLiveApiCall = false;
            $postedResponse = json_decode($sageLogArray[$extras['startingStep']]['response'], true);
            info('Book Update - Sage API Call - Method Name ('.$processDetails['methodName'].') Already called - '.(! empty($extras['sendUpdateLog']) ? 'SendUpdateUUID' : 'QuoteUUID').': '.$quoteObject->uuid);

        } else {
            ${$processDetails['methodName']} = SagePayloadFactory::{$processDetails['methodName']}(
                $sageRequestPayload,
                $extras['splitPayments'],
                $extras['entryType'],
                $reverseInvoiceResponse,
                [
                    'mainLeadDetails' => $extras['mainLeadDetails'] ?? [],
                    'extras' => $extras['extras'] ?? [],
                ]
            );
            $resp = $this->postToSage300(${$processDetails['methodName']}['endPoint'], ${$processDetails['methodName']}['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if (! empty($postedResponse['BatchNumber'])) {
            $this->sageBatchNumber = $postedResponse['BatchNumber'];

            if ($isLiveApiCall) {
                $this->logSageApiCall(${$processDetails['methodName']}, $postedResponse, $quoteObject, $extras['startingStep'], $extras['totalSteps'], SageEnum::STATUS_SUCCESS, $extras['extras']['authDetails']->id);
                info('Book Update - Sage API Call - Method Name ('.$processDetails['methodName'].') - '.(! empty($extras['sendUpdateLog']) ? 'SendUpdateUUID' : 'QuoteUUID').': '.$quoteObject->uuid);
            }

            $url = $processDetails['url'].'('.$this->sageBatchNumber.')';
            if ($isARInvoicesCalls) {
                $resp = $this->postToSage300($url, [], 'GET');
                $postedResponse = json_decode($resp, true);
            } else {
                $postedResponse = (new SageCustomApiService)->getAPInvoicePaymentScheduleByBatchNumber($this->sageBatchNumber);
            }

            if (($isARInvoicesCalls && empty($postedResponse['Invoices'][0]['InvoicePaymentSchedules'])) || (! $isARInvoicesCalls && $postedResponse['status'] == false)) {
                $returnMessage['status'] = false;
                $returnMessage['message'] = 'Error while getting '.$processDetails['invoiceType'].' Split paymets from sage';
                if (! $isARInvoicesCalls) {
                    $returnMessage['error'] = $postedResponse['error'];
                }
                logger()->error('Book Update - Sage API Failed - Response: Error while getting '.$processDetails['invoiceType'].' Split paymets from sage');

                return $returnMessage;

            } else {
                $extras['startingStep'] = $extras['startingStep'] + 1;

                info('Book Update - Prepare Patch payload for Split Payments');
                $invoicePaymentSchedules = ($isARInvoicesCalls) ? $postedResponse['Invoices'][0]['InvoicePaymentSchedules'] : $postedResponse['response'];
                foreach ($invoicePaymentSchedules as $key => $invoicePaymentSchedule) {
                    // Add discount value to the first installment of payment in sage for balancing the amount
                    $amountDue = roundNumber($extras['splitPayments'][$key]['payment_amount'] + ($extras['splitPayments'][$key]['sr_no'] == 1 ? $extras['payment']->discount_value : 0));
                    $invoicePaymentSchedulesDueDate = SagePayloadFactory::calculateDueDate(date('Y-m-d', strtotime($extras['splitPayments'][$key]['due_date'])), $sageRequestPayload->insurerInvoiceDate);

                    if ($extras['payment']->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                        $dueDate = $invoicePaymentSchedulesDueDate;
                    } else {
                        $dueDate = $extras['splitPayments'][$key]['sr_no'] == 1 ? $invoicePaymentSchedulesDueDate : date('Y-m-d', strtotime($extras['splitPayments'][$key]['due_date']));
                    }

                    if ($isARInvoicesCalls) {
                        $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['AmountDue'] = $amountDue;
                        $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['DueDate'] = $dueDate;
                    } else {
                        $invoicePaymentSchedule->datedue = Carbon::parse($dueDate)->format(env('SAGE_300_CUSTOM_API_DATE_FORMAT'));
                        $invoicePaymentSchedule->amtdue = $amountDue;
                        $invoicePaymentSchedule->amtduehc = $amountDue;
                        $invoicePaymentSchedule->audtorg = $this->sageDBName;
                    }
                }

                if (in_array($extras['requestType'], [SageEnum::SRT_CREATE_AR_SPPAY_INV, SageEnum::SRT_REV_CORR_AR_SPPAY_INV])) {
                    info('Book Update - Prepare Patch payload for Commission Split Payments');

                    foreach ($postedResponse['Invoices'][1]['InvoicePaymentSchedules'] as $key => $value) {
                        // Add vat on commission split payments to the first installment of commission in sage for balancing the amount
                        $commissionSplit = floatval($extras['splitPayments'][$key]['commission_vat_applicable']);
                        $vatOnCommission = floatval($extras['splitPayments'][$key]['commission_vat']);

                        $dueCommissionSplitAmount = roundNumber(roundNumber($commissionSplit) + roundNumber($vatOnCommission));

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

                if (isset($sageLogArray[$extras['startingStep']]) && $sageLogArray[$extras['startingStep']]['status'] == SageEnum::STATUS_SUCCESS) {
                    $isLiveApiCall = false;
                    $payLoadOptions =
                    $postedResponse = json_decode($sageLogArray[$extras['startingStep']]['response'], true);
                } else {
                    $isLiveApiCall = true;
                    if ($isARInvoicesCalls) {
                        $payLoadOptions = $postedResponse;
                        $resp = $this->postToSage300($url, $postedResponse, 'PATCH');
                        $postedResponse = json_decode($resp, true);
                    } else {
                        $payLoadOptions = $invoicePaymentSchedules;
                        $resp = (new SageCustomApiService)->updateAPInvoicePaymentSchedule($this->sageBatchNumber, $invoicePaymentSchedules);
                        $postedResponse['response'] = $resp;
                    }
                }

                $postedResponse = ($postedResponse == '') ? [] : $postedResponse;
                $postedResponse['endPoint'] = ($isARInvoicesCalls) ? $url : $resp['url'];
                $postedResponse['payload'] = $payLoadOptions;
                $postedResponse['sage_request_type'] = $processDetails['requestType'];
                $postedResponse['entry_type'] = $processDetails['entryType'];

                if (($isARInvoicesCalls && isset($postedResponse['error'])) || (! $isARInvoicesCalls && $resp['status'] == false)) {
                    $this->logSageApiCall($postedResponse, (($isARInvoicesCalls) ? $resp : $postedResponse), $quoteObject, $extras['startingStep'], $extras['totalSteps'], SageEnum::STATUS_FAIL, $extras['extras']['authDetails']->id);
                    $responseMessage = ($isARInvoicesCalls && isset(json_decode($resp, true)['error']['message']['value'])) ?
                        json_decode($resp, true)['error']['message']['value'] : 'Error while making '.$processDetails['invoiceType'].' Split payments patch to sage';
                    $returnMessage['status'] = false;
                    $returnMessage['message'] = $responseMessage;
                    if (! $isARInvoicesCalls) {
                        $returnMessage['error'] = $postedResponse['error'] ?? null;
                    }
                    logger()->error('Book Update - Sage API Failed - Response: '.$responseMessage);

                    return $returnMessage;
                }

                if ($isLiveApiCall) {
                    $this->logSageApiCall($postedResponse, (($isARInvoicesCalls) ? $resp : $postedResponse), $quoteObject, $extras['startingStep'], $extras['totalSteps'], SageEnum::STATUS_SUCCESS, $extras['extras']['authDetails']->id);
                }
            }

        } else {
            $this->logSageApiCall(${$processDetails['methodName']}, $postedResponse, $quoteObject, $extras['startingStep'], $extras['totalSteps'], SageEnum::STATUS_FAIL, $extras['extras']['authDetails']->id);

            $responseMessage = isset($postedResponse['error']['message']['value']) ?
                    $postedResponse['error']['message']['value'] : $processDetails['invoiceType'].' Split payment failed from Sage';
            $returnMessage['message'] = $responseMessage;
            $returnMessage['status'] = false;
            logger()->error('Book Update - Sage API Failed - Response: '.$responseMessage);

            return $returnMessage;
        }
    }

    public function postBookPolicyToSage($request, $payment, $quote, $paymentSplits, $data)
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        // check sage is enabled or not
        $isSageEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::SAGE_ENABLED);

        if (! $isSageEnabled) {
            $returnMessage['message'] = 'Sage is not enabled';

            return $returnMessage;
        }
        //Booking of Policies with zero price is only allowed for the policies having Credit Approval as Payment Method.
        $isPaymentFrequencyUpfront = $payment->frequency == PaymentFrequency::UPFRONT;
        $isPaymentMethodCreditApproved = $payment->payment_methods_code == PaymentMethodsEnum::CreditApproval;
        $isTotalPriceZero = $payment->total_price == 0;
        if (! $isPaymentMethodCreditApproved && $isTotalPriceZero && $isPaymentFrequencyUpfront) {
            return ['status' => false, 'message' => 'Please check the payment as total price is set to zero while Payment Method is '.PaymentMethodsEnum::CreditApproval.' and Frequency is '.$payment->frequency.'. Please Select Credit Approval as your payment method and Upfront as Payment Frequency to Proceed!'];
        }

        // payload
        $sageRequest = $this->sagePayLoad($request->model_type, $payment, $quote, $paymentSplits);

        $sageLogArray = $quote->sageApiLogs->keyBy('step')->toArray();
        // sage customer number generation
        $sageRequest->customerId = $this->verifySageCustomer($quote->customer_id, $data, $quote, $sageLogArray, 13);

        /* Check Sage Vendor ID, GL Account ID, Insurer Customer ID, and Sage Customer ID*/
        $checkRequiredSageIds = $this->checkRequiredSageIds($sageRequest);
        if (! $checkRequiredSageIds['status']) {
            return $checkRequiredSageIds;
        }

        return $this->bookPolicyOnSage([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray]);

    }

    private function checkRequiredSageIds($sageRequest): array
    {
        $missingFields = [];
        if (empty($sageRequest->customerId)) {
            $missingFields[] = 'Customer Sage ID';
        }

        if (! $sageRequest->insurerGlLiaiblityAccount) {
            $missingFields[] = 'GL Account for Insurance Provider';
        }
        if (! $sageRequest->sageVenderId) {
            $missingFields[] = 'Sage Vendor ID for Insurance Provider';
        }
        if (! $sageRequest->sageInsurerCustomerId) {
            $missingFields[] = 'Sage Insurer Customer ID for Insurance Provider';
        }

        if (! empty($missingFields)) {
            $message = implode(', ', $missingFields).' not found.';

            return ['status' => false, 'message' => $message];
        }

        return ['status' => true, 'message' => ''];
    }

    private function bookPolicyOnSage($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        info('################################## Sage Book Policy started for : '.$quote->code.'##################################');
        info('Sage API : Payment frequency : '.$payment->frequency.' for '.$quote->code);

        //Create AR Commission and Premium Invoice
        $createARInvoicePremAndComm = $this->createARInvoicePremAndComm([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray]);
        if (! $createARInvoicePremAndComm['status']) {
            return $createARInvoicePremAndComm;
        }

        // Create AP Premium Invoice
        $createAPInvoicePrem = $this->createAPInvoicePrem([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray]);
        if (! $createAPInvoicePrem['status']) {
            return $createAPInvoicePrem;
        }

        // Create AR Discount Invoice
        $createARInvoiceDis = $this->createARInvoiceDis([$sageRequest, $quote, $sageLogArray]);
        if (! $createARInvoiceDis['status']) {
            return $createARInvoiceDis;
        }

        // Apply Prepayments
        $applyPaymentInvoices = $this->applyPaymentInvoices([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray]);
        if (! $applyPaymentInvoices['status']) {
            return $applyPaymentInvoices;
        }

        info('################################## Sage Policy Booked for : '.$quote->code.'##################################');

        return ['status' => true, 'message' => 'Policy Booked'];
    }

    private function createARInvoicePremAndComm($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $isPaymentFrequencyUpfront = $payment->frequency == PaymentFrequency::UPFRONT;
        if ($isPaymentFrequencyUpfront) {
            return $this->createUpfrontARInvoicePremAndComm($sageRequestDataArray);
        } else {
            return $this->createNonUpfrontARInvoicePremAndComm($sageRequestDataArray);
        }

    }

    private function createUpfrontARInvoicePremAndComm($sageRequestDataArray)
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $isLiveApiCallStep2 = true;
        if (isset($sageLogArray[2]) && $sageLogArray[2]['status'] == 'success') {
            info('SAGE API :  createARInvoicePremAndComm  Sent Already for '.$quote->code);
            $isLiveApiCallStep2 = false;
            $sageResponse = json_decode($sageLogArray[2]['response'], true);
        } else {
            info('SAGE API :  Send createARInvoicePremAndComm  for '.$quote->code);
            $payLoadOptions = SagePayloadFactory::createARInvoicePremAndComm($sageRequest);
            $resp = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($resp, true);
        }

        if (! empty($sageResponse['BatchNumber'])) {
            info('SAGE API : '.$quote->code.' :  Batch Number - '.$sageResponse['BatchNumber'].' for createARInvoicePremAndComm');
            if ($isLiveApiCallStep2) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $quote, 2, 13);
            }
            $isLiveApiCallStep3 = true;
            if (isset($sageLogArray[3]) && $sageLogArray[3]['status'] == 'success') {
                info('SAGE API :  readyToPostInvoiceAr  Sent Already for '.$quote->code);
                $isLiveApiCallStep3 = false;
                $readyToPostResponse = json_decode($sageLogArray[3]['response'], true);
            } else {
                info('SAGE API :  Send readyToPostInvoiceAr  for '.$quote->code);
                $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr($sageResponse['BatchNumber']);
                $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $errorMessage = 'Error while making Ar invoice & prem ready to post to sage';
                $message = 'readyToPostInvoiceAr - '.$sageResponse['BatchNumber'].' failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, 3, 13, 'fail']);
            }
            info('SAGE API : '.$quote->code.' : readyToPostInvoiceAr - '.$sageResponse['BatchNumber'].' completed successfully');
            if ($isLiveApiCallStep3) {
                $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, 3, 13);
            }

            $isLiveApiCallStep4 = true;
            if (isset($sageLogArray[4]) && $sageLogArray[4]['status'] == 'success') {
                info('SAGE API :  aRPostInvoices  Sent Already for '.$quote->code);
                $isLiveApiCallStep4 = false;
                $postedResponse = json_decode($sageLogArray[4]['response'], true);
            } else {
                $aRPostInvoices = SagePayloadFactory::aRPostInvoices($sageResponse['BatchNumber']);

                $isAlreadyPosted = false;
                if (isset($sageLogArray[4]) && $sageLogArray[4]['status'] == SageEnum::STATUS_FAIL) {
                    info('SAGE API :  Check status of  AR invoice batch '.$sageResponse['BatchNumber'].'  for '.$quote->code);
                    $arInvoiceBatch = $this->postToSage300('AR/ARInvoiceBatches('.$sageResponse['BatchNumber'].')', [], 'GET');
                    info('SAGE API :  Status of  AR invoice batch '.$arInvoiceBatch);
                    $arInvoiceBatch = json_decode($arInvoiceBatch, true);
                    if ($arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        info('SAGE API : AR invoice bacth '.$sageResponse['BatchNumber'].' already posted for '.$quote->code);
                        $postedResponse = $aRPostInvoices['payload'];
                        $isAlreadyPosted = true;
                    }
                }

                if (! $isAlreadyPosted) {
                    info('SAGE API :  Send aRPostInvoices  for '.$quote->code);
                    $resp = $this->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                    $postedResponse = json_decode($resp, true);
                }

            }

            if (isset($postedResponse['error'])) {
                $errorMessage = 'Error while making Ar invoice & prem Posted to sage';
                $message = 'aRPostInvoices - '.$sageResponse['BatchNumber'].' failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostInvoices, $postedResponse, 4, 13, 'fail']);
            }
            info('SAGE API : '.$quote->code.' : aRPostInvoices - '.$sageResponse['BatchNumber'].' completed successfully');
            if ($isLiveApiCallStep4) {
                $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, 4, 13);
            }
        } else {
            $errorMessage = 'Ar invoice & prem failed from sage';
            $message = 'createARInvoicePremAndComm  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $payLoadOptions, $sageResponse, 2, 13, 'fail']);
        }
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AR Premium and Commission invoice created on sage';

        return $returnMessage;
    }
    private function createNonUpfrontARInvoicePremAndComm($sageRequestDataArray)
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        //2
        $isLiveApiCallStep2 = true;
        if (isset($sageLogArray[2]) && $sageLogArray[2]['status'] == 'success') {
            info('SAGE API :  createARInvoiceSplitPayments  Sent Already for '.$quote->code);
            $isLiveApiCallStep2 = false;
            $postedResponse = json_decode($sageLogArray[2]['response'], true);
        } else {
            info('SAGE API :  Send createARInvoiceSplitPayments  for '.$quote->code);
            $createARInvoiceSplitPayments = SagePayloadFactory::createARInvoiceSplitPayments($sageRequest, $paymentSplits);
            $resp = $this->postToSage300($createARInvoiceSplitPayments['endPoint'], $createARInvoiceSplitPayments['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if (empty($postedResponse['BatchNumber'])) {
            $errorMessage = 'ar split payment failed from sage';
            $message = 'createARInvoiceSplitPayments  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $createARInvoiceSplitPayments, $postedResponse, 2, 13, 'fail']);
        }
        info('SAGE API : '.$quote->code.' : createARInvoiceSplitPayments - BatchNumber : '.$postedResponse['BatchNumber'].' completed successfully');
        $batchNumber = $postedResponse['BatchNumber'];
        if ($isLiveApiCallStep2) {
            $this->logSageApiCall($createARInvoiceSplitPayments, $postedResponse, $quote, 2, 13);
        }

        $url = 'AR/ARInvoiceBatches('.$batchNumber.')';
        info('SAGE API :  Send Post AR/ARInvoiceBatches  for '.$quote->code);
        $resp = $this->postToSage300($url, [], 'GET');
        $postedResponse = json_decode($resp, true);

        if (empty($postedResponse['Invoices'][0]['InvoicePaymentSchedules'])) {
            $errorMessage = 'Error while get ar2 split paymets from sage';
            $message = 'get AR/ARInvoiceBatches for batchNumber : '.$batchNumber.' failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, [], $postedResponse, 2, 13, 'fail'], false);
        }
        info('SAGE API :  Prepare Patch payload for SpitPayments  for '.$quote->code);
        foreach ($postedResponse['Invoices'][0]['InvoicePaymentSchedules'] as $key => $value) {
            $paymentSplit = $paymentSplits[$key];
            // add discount amount to amount due for the first child payment in sage for balancing the amount
            $invoicePaymentSchedulesDueDate = SagePayloadFactory::calculateDueDate(date('Y-m-d', strtotime($paymentSplit['due_date'])), $sageRequest->insurerInvoiceDate);
            $dueAmount = roundNumber($paymentSplit['payment_amount'] + ($paymentSplit['sr_no'] == 1 ? $payment->discount_value : 0));

            if ($payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                $dueDate = $invoicePaymentSchedulesDueDate;
            } else {
                $dueDate = $paymentSplit['sr_no'] == 1 ? $invoicePaymentSchedulesDueDate : date('Y-m-d', strtotime($paymentSplit['due_date']));
            }

            $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['AmountDue'] = $dueAmount;
            $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['DueDate'] = $dueDate;
        }

        info('SAGE API :  Prepare Patch payload for Commission Spits  for '.$quote->code);

        foreach ($postedResponse['Invoices'][1]['InvoicePaymentSchedules'] as $key => $value) {
            $paymentSplit = $paymentSplits[$key];
            $commissionSplit = $paymentSplit['commission_vat_applicable'];
            $vatOnCommission = $paymentSplit['commission_vat'];

            $dueCommissionSplitAmount = roundNumber(roundNumber($commissionSplit) + roundNumber($vatOnCommission));

            $invoicePaymentSchedulesDueDate = SagePayloadFactory::calculateDueDate(date('Y-m-d', strtotime($paymentSplit['due_date'])), $sageRequest->insurerInvoiceDate);
            // for upfront and split, due date should always be insurer invoice date for all child payment, for other frequencies, it should be the due date of the first child payment
            if ($payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                $dueDate = $invoicePaymentSchedulesDueDate;
            } else {
                $dueDate = $paymentSplit['sr_no'] == 1 ? $invoicePaymentSchedulesDueDate : date('Y-m-d', strtotime($paymentSplit['due_date']));
            }
            $postedResponse['Invoices'][1]['InvoicePaymentSchedules'][$key]['AmountDue'] = $dueCommissionSplitAmount;
            $postedResponse['Invoices'][1]['InvoicePaymentSchedules'][$key]['DueDate'] = $dueDate;
        }
        $patchPayload = $postedResponse;
        //3
        $isLiveApiCallStep3 = true;
        if (isset($sageLogArray[3]) && $sageLogArray[3]['status'] == 'success') {
            info('SAGE API :  Patch Request  Sent Already for '.$quote->code);
            $isLiveApiCallStep3 = false;
            $postedResponse = json_decode($sageLogArray[3]['response'], true);
        } else {
            info('SAGE API :  Send Patch Request  for '.$quote->code);
            $resp = $this->postToSage300($url, $postedResponse, 'PATCH');
            $postedResponse = json_decode($resp, true);
        }
        $postedResponse['endPoint'] = $url;
        $postedResponse['payload'] = $patchPayload;
        if (isset($postedResponse['error'])) {
            info('SAGE API : '.$quote->code.' : AR Patch Request failed '.json_encode($postedResponse['error']));
            $errorMessage = 'Error while making ar2 split payments patch to sage';
            $message = 'AR Patch Request failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $postedResponse, $postedResponse, 3, 13, 'fail']);
        }
        if ($isLiveApiCallStep3) {
            $this->logSageApiCall($postedResponse, $postedResponse, $quote, 3, 13);
        }

        // 4
        $isLiveApiCallStep4 = true;
        if (isset($sageLogArray[4]) && $sageLogArray[4]['status'] == 'success') {
            info('SAGE API :  readyToPostInvoiceAr  Sent Already for '.$quote->code);
            $isLiveApiCallStep4 = false;
            $readyToPostResponse = json_decode($sageLogArray[4]['response'], true);
        } else {
            info('SAGE API :  Send readyToPostInvoiceAr  for '.$quote->code);
            $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr($batchNumber);
            $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
        }

        if (isset($readyToPostResponse['error'])) {
            $errorMessage = 'Error while making ar2 Apply split payment ready to post to sage';
            $message = 'readyToPostInvoiceAr failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, 4, 13, 'fail']);
        }
        if ($isLiveApiCallStep4) {
            $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, 4, 13);
        }
        info('SAGE API : '.$quote->code.' : readyToPostInvoiceAr completed successfully');

        // 5
        $isLiveApiCallStep5 = true;
        if (isset($sageLogArray[5]) && $sageLogArray[5]['status'] == 'success') {
            info('SAGE API :  aRPostInvoices  Sent Already for '.$quote->code);
            $isLiveApiCallStep5 = false;
            $postedResponse = json_decode($sageLogArray[5]['response'], true);
        } else {
            $aRPostInvoices = SagePayloadFactory::aRPostInvoices($batchNumber);

            $isAlreadyPosted = false;
            if (isset($sageLogArray[5]) && $sageLogArray[5]['status'] == SageEnum::STATUS_FAIL) {
                info('SAGE API :  Check status of  AR invoice bacth '.$batchNumber.'  for '.$quote->code);
                $arInvoiceBatch = $this->postToSage300('AR/ARInvoiceBatches('.$batchNumber.')', [], 'GET');
                info('SAGE API :  Status of  AR invoice batch '.$arInvoiceBatch);
                $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                if ($arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    info('SAGE API : AR invoice batch '.$batchNumber.' already posted for '.$quote->code);
                    $postedResponse = $aRPostInvoices['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                info('SAGE API :  Send aRPostInvoices  for '.$quote->code);
                $resp = $this->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                $postedResponse = json_decode($resp, true);
            }
        }
        if (isset($postedResponse['error'])) {
            $errorMessage = 'Error while making ar2 Apply split payment Posted to sage';
            $message = 'aRPostInvoices  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostInvoices, $postedResponse, 5, 13, 'fail']);
        } else {
            info('SAGE API : '.$quote->code.' : aRPostInvoices completed successfully');
            if ($isLiveApiCallStep5) {
                $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, 5, 13);
            }
        }
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AR Premium and Commission invoice created on sage';

        return $returnMessage;
    }

    private function createAPInvoicePrem($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $isPaymentFrequencyUpfront = $payment->frequency == PaymentFrequency::UPFRONT;
        if ($isPaymentFrequencyUpfront) {
            return $this->createUpfrontAPInvoicePrem($sageRequestDataArray);
        } else {
            return $this->createNonUpfrontAPInvoicePrem($sageRequestDataArray);
        }
    }
    private function createUpfrontAPInvoicePrem($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        $isTotalPriceZero = $payment->total_price == 0;
        info('########## Start of Upfront createAPInvoicePrem for : '.$quote->code.' ##########');
        if (! $isTotalPriceZero) {
            $isLiveApiCallStep5 = true;
            if (isset($sageLogArray[5]) && $sageLogArray[5]['status'] == 'success') {
                info('SAGE API :  createAPInvoicePrem  Sent Already for '.$quote->code);
                $isLiveApiCallStep5 = false;
                $postedResponse = json_decode($sageLogArray[5]['response'], true);
            } else {
                info('SAGE API :  Send createAPInvoicePrem  for '.$quote->code);
                $createAPInvoicePrem = SagePayloadFactory::createAPInvoicePrem($sageRequest);
                $resp = $this->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
                $postedResponse = json_decode($resp, true);
            }

            if (! empty($postedResponse['BatchNumber'])) {
                info('SAGE API : '.$quote->code.' : readyToPostInvoiceAr - '.$postedResponse['BatchNumber'].' completed successfully');
                if ($isLiveApiCallStep5) {
                    $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $quote, 5, 13);
                }

                $isLiveApiCallStep6 = true;
                if (isset($sageLogArray[6]) && $sageLogArray[6]['status'] == 'success') {
                    info('SAGE API :  readyToPostInvoiceAP  Sent Already for '.$quote->code);
                    $isLiveApiCallStep6 = false;
                    $readyToPostResponse = json_decode($sageLogArray[6]['response'], true);
                } else {
                    info('SAGE API :  Send readyToPostInvoiceAP  for '.$quote->code);
                    $readyToPostInvoiceAP = SagePayloadFactory::readyToPostInvoiceAP($postedResponse['BatchNumber']);
                    $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
                }

                if ($readyToPostResponse !== '') {
                    $errorMessage = 'Error while making AP invoice ready to post to sage';
                    $message = 'readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAP, $readyToPostResponse, 6, 13, 'fail']);
                } else {
                    info('SAGE API : '.$quote->code.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' completed successfully');
                    if ($isLiveApiCallStep6) {
                        $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $quote, 6, 13);
                    }
                }

                $isLiveApiCallStep7 = true;
                if (isset($sageLogArray[7]) && $sageLogArray[7]['status'] == 'success') {
                    info('SAGE API :  aPPostInvoices  Sent Already for '.$quote->code);
                    $isLiveApiCallStep7 = false;
                    $postedResponse = json_decode($sageLogArray[7]['response'], true);
                } else {
                    $aPPostInvoices = SagePayloadFactory::aPPostInvoices($postedResponse['BatchNumber']);

                    $isAlreadyPosted = false;
                    if (isset($sageLogArray[7]) && $sageLogArray[7]['status'] == SageEnum::STATUS_FAIL) {
                        info('SAGE API :  Check status of  AP invoice batch '.$postedResponse['BatchNumber'].'  for '.$quote->code);
                        $aPInvoiceBatch = $this->postToSage300('AP/APInvoiceBatches('.$postedResponse['BatchNumber'].')', [], 'GET');
                        info('SAGE API :  Status of  AP invoice batch '.$aPInvoiceBatch);
                        $aPInvoiceBatch = json_decode($aPInvoiceBatch, true);

                        if ($aPInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                            info('SAGE API : AP invoice batch '.$postedResponse['BatchNumber'].' already posted for '.$quote->code);
                            $postedResponse = $aPPostInvoices['payload'];
                            $isAlreadyPosted = true;
                        }
                    }

                    if (! $isAlreadyPosted) {
                        info('SAGE API :  Send aPPostInvoices  for '.$quote->code);
                        $resp = $this->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                        $postedResponse = json_decode($resp, true);
                    }
                }

                if (isset($postedResponse['error'])) {
                    $errorMessage = 'Error while making AP invoices Posted to sage';
                    $message = 'aPPostInvoices failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aPPostInvoices, $postedResponse, 7, 13, 'fail']);
                } else {
                    info('SAGE API : '.$quote->code.' : aPPostInvoices completed successfully');
                    if ($isLiveApiCallStep7) {
                        $this->logSageApiCall($aPPostInvoices, $postedResponse, $quote, 7, 13);
                    }
                }
            } else {
                $errorMessage = 'Ap invoice prem failed from sage';
                $message = 'createAPInvoicePrem  failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $createAPInvoicePrem, $postedResponse, 5, 13, 'fail']);
            }
        } else {
            info('########## skipping of createAPInvoicePrem for : '.$quote->code.' dye to Zero Pricing ########## ');
        }
        info('  ########## End of Upfront createAPInvoicePrem for : '.$quote->code.' ########## ');

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AP Premium invoice created on sage';

        return $returnMessage;
    }
    private function createNonUpfrontAPInvoicePrem($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        info('########## Start of NON Upfront createAPInvoicePrem for : '.$quote->code.' ########## ');
        $isLiveApiCallStep6 = true;
        if (isset($sageLogArray[6]) && $sageLogArray[6]['status'] == 'success') {
            info('SAGE API :  createAPInvoiceSplitPayments  Sent Already for '.$quote->code);
            $isLiveApiCallStep6 = false;
            $postedResponse = json_decode($sageLogArray[6]['response'], true);
        } else {
            info('SAGE API :  Send createAPInvoiceSplitPayments  for '.$quote->code);
            $createAPInvoicePrem = SagePayloadFactory::createAPInvoiceSplitPayments($sageRequest, $paymentSplits);
            $resp = $this->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if (! empty($postedResponse['BatchNumber'])) {
            $apBatchNumber = $postedResponse['BatchNumber'];
            $url = 'AP/APInvoiceBatches('.$apBatchNumber.')';
            info('SAGE API : '.$quote->code.' : createAPInvoiceSplitPayments - '.$apBatchNumber.' completed successfully');
            if ($isLiveApiCallStep6) {
                $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $quote, 6, 15);
            }

            info('SAGE API :  Prepare Patch payload for SpitPayments  for '.$quote->code);
            $aPInvoicePaymentsScheduleResponse = (new SageCustomApiService)->getAPInvoicePaymentScheduleByBatchNumber($postedResponse['BatchNumber']);

            if ($aPInvoicePaymentsScheduleResponse['status']) {
                $aPInvoicePaymentsSchedule = $aPInvoicePaymentsScheduleResponse['response'];
                foreach ($aPInvoicePaymentsSchedule as $key => $aPInvoicePaymentSchedule) {
                    // add discount amount to amount due for the first child payment in sage for balancing the amount
                    $dueAmount = roundNumber($paymentSplits[$key]['payment_amount'] + ($paymentSplits[$key]['sr_no'] == 1 ? $payment->discount_value : 0));
                    $invoicePaymentSchedulesDueDate = SagePayloadFactory::calculateDueDate(date('Y-m-d', strtotime($paymentSplits[$key]['due_date'])), $sageRequest->insurerInvoiceDate);
                    if ($payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                        $dueDate = $invoicePaymentSchedulesDueDate;
                    } else {
                        $dueDate = $paymentSplits[$key]['sr_no'] == 1 ? $invoicePaymentSchedulesDueDate : date('Y-m-d', strtotime($paymentSplits[$key]['due_date']));
                    }

                    $aPInvoicePaymentSchedule->datedue = Carbon::parse($dueDate)->format(env('SAGE_300_CUSTOM_API_DATE_FORMAT'));
                    $aPInvoicePaymentSchedule->amtdue = $dueAmount;
                    $aPInvoicePaymentSchedule->amtduehc = $dueAmount;
                    $aPInvoicePaymentSchedule->audtorg = $this->sageDBName;
                }
            } else {
                $errorMessage = 'Error while getting split payment schedule from sage';
                $message = $aPInvoicePaymentsScheduleResponse['error'];

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, [], [], 6, 15, 'fail'], false);
            }
            //7
            $isLiveApiCallStep7 = true;
            if (isset($sageLogArray[7]) && $sageLogArray[7]['status'] == 'success') {
                info('SAGE API :  Patch Request  Sent Already for '.$quote->code);
                $isLiveApiCallStep7 = false;
                $postedResponse = json_decode($sageLogArray[7]['response'], true);
            } else {
                info('SAGE API :  Send Patch Request  for '.$quote->code);
                $resp = (new SageCustomApiService)->updateAPInvoicePaymentSchedule($postedResponse['BatchNumber'], $aPInvoicePaymentsSchedule);
                $postedResponse['response'] = $resp;
            }

            $postedResponse['endPoint'] = $resp['url'] ?? $postedResponse['endPoint'] ?? null;
            $postedResponse['payload'] = $aPInvoicePaymentsSchedule;
            if (! $postedResponse['response']['status']) {
                info('SAGE API : '.$quote->code.' : AP Patch Request failed '.json_encode($postedResponse['response']));
                $errorMessage = 'Error while making AP split payments patch to sage';
                $message = 'AP Patch Request failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $postedResponse, $resp, 7, 15, 'fail']);
            }
            info('SAGE API : '.$quote->code.' : Patch Request completed successfully');
            if ($isLiveApiCallStep7) {
                $this->logSageApiCall($postedResponse, $postedResponse, $quote, 7, 15);
            }

            $isLiveApiCallStep8 = true;
            if (isset($sageLogArray[8]) && $sageLogArray[8]['status'] == 'success') {
                info('SAGE API :  readyToPostInvoiceAP  Sent Already for '.$quote->code);
                $isLiveApiCallStep8 = false;
                $readyToPostResponse = json_decode($sageLogArray[8]['response'], true);
            } else {
                info('SAGE API :  Send readyToPostInvoiceAP  for '.$quote->code);
                $readyToPostInvoiceAP = SagePayloadFactory::readyToPostInvoiceAP($apBatchNumber);
                $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $errorMessage = 'Error while making AP invoice ready to post to sage';
                $message = 'readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAP, $readyToPostResponse, 8, 15, 'fail']);
            } else {
                info('SAGE API : '.$quote->code.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' completed successfully');
                if ($isLiveApiCallStep8) {
                    $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $quote, 8, 15);
                }
            }

            $isLiveApiCallStep9 = true;
            if (isset($sageLogArray[9]) && $sageLogArray[9]['status'] == 'success') {
                info('SAGE API :  aPPostInvoices  Sent Already for '.$quote->code);
                $isLiveApiCallStep9 = false;
                $postedResponse = json_decode($sageLogArray[9]['response'], true);
            } else {
                $aPPostInvoices = SagePayloadFactory::aPPostInvoices($postedResponse['BatchNumber']);

                $isAlreadyPosted = false;
                if (isset($sageLogArray[9]) && $sageLogArray[9]['status'] == SageEnum::STATUS_FAIL) {
                    info('SAGE API :  Check status of  AP invoice batch '.$postedResponse['BatchNumber'].'  for '.$quote->code);
                    $aPInvoiceBatch = $this->postToSage300('AP/APInvoiceBatches('.$postedResponse['BatchNumber'].')', [], 'GET');
                    info('SAGE API :  Status of  AP invoice batch '.$aPInvoiceBatch);
                    $aPInvoiceBatch = json_decode($aPInvoiceBatch, true);

                    if ($aPInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        info('SAGE API : AP invoice batch '.$postedResponse['BatchNumber'].' already posted for '.$quote->code);
                        $postedResponse = $aPPostInvoices['payload'];
                        $isAlreadyPosted = true;
                    }
                }

                if (! $isAlreadyPosted) {
                    info('SAGE API :  Send aPPostInvoices  for '.$quote->code);
                    $resp = $this->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $errorMessage = 'Error while making AP invoices Posted to sage';
                $message = 'aPPostInvoices failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aPPostInvoices, $postedResponse, 9, 15, 'fail']);
            } else {
                info('SAGE API : '.$quote->code.' : aPPostInvoices completed successfully');
                if ($isLiveApiCallStep9) {
                    $this->logSageApiCall($aPPostInvoices, $postedResponse, $quote, 9, 15);
                }
            }

        } else {
            $errorMessage = 'Ap invoice prem failed from sage';
            $message = 'createAPInvoicePrem  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $createAPInvoicePrem, $postedResponse, 6, 15, 'fail']);
        }
        info('  ########## End of NON Upfront createAPInvoicePrem for : '.$quote->code.' ########## ');

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AP Premium invoice created on sage';

        return $returnMessage;
    }

    public function createARInvoiceDis($sageRequestDataArray)
    {
        [$sageRequest, $quote, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        $isDiscountApplied = $sageRequest->discount > 0;
        /* createARInvoiceDis */
        if ($isDiscountApplied) {
            info('########## Start createARInvoiceDis for : '.$quote->code.' ##########');
            $isLiveApiCallStep10 = true;
            if (isset($sageLogArray[10]) && $sageLogArray[10]['status'] == 'success') {
                info('SAGE API:  createARInvoiceDis  Sent Already for '.$quote->code);
                $isLiveApiCallStep10 = false;
                $postedResponse = json_decode($sageLogArray[10]['response'], true);
            } else {
                info('SAGE API :  Send createARInvoiceDis  for '.$quote->code);
                $createARInvoiceDis = SagePayloadFactory::createARInvoiceDis($sageRequest);
                $resp = $this->postToSage300($createARInvoiceDis['endPoint'], $createARInvoiceDis['payload']);
                $postedResponse = json_decode($resp, true);
            }

            if (! empty($postedResponse['BatchNumber'])) {
                info('SAGE API : '.$quote->code.' : createARInvoiceDis - BatchNumber : '.$postedResponse['BatchNumber'].' completed successfully');
                if ($isLiveApiCallStep10) {
                    $this->logSageApiCall($createARInvoiceDis, $postedResponse, $quote, 10, 15);
                }

                $isLiveApiCallStep11 = true;
                if (isset($sageLogArray[11]) && $sageLogArray[11]['status'] == 'success') {
                    info('SAGE API :  readyToPostInvoiceAr  Sent Already for '.$quote->code);
                    $isLiveApiCallStep11 = false;
                    $readyToPostResponse = json_decode($sageLogArray[11]['response'], true);
                } else {
                    info('SAGE API :  Send readyToPostInvoiceAr  for '.$quote->code);
                    $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr($postedResponse['BatchNumber']);
                    $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
                }

                if ($readyToPostResponse !== '') {
                    $errorMessage = 'Error while making Ar discount invoice ready to post to sage';
                    $message = 'readyToPostInvoiceAr failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, 11, 15, 'fail']);
                } else {
                    info('SAGE API : '.$quote->code.' : readyToPostInvoiceAr completed successfully');
                    if ($isLiveApiCallStep11) {
                        $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, 11, 15);
                    }
                }

                $isLiveApiCallStep12 = true;
                if (isset($sageLogArray[12]) && $sageLogArray[12]['status'] == 'success') {
                    info('SAGE API :  aRPostInvoices  Sent Already for '.$quote->code);
                    $isLiveApiCallStep12 = false;
                    $postedResponse = json_decode($sageLogArray[12]['response'], true);
                } else {
                    $aRPostInvoices = SagePayloadFactory::aRPostInvoices($postedResponse['BatchNumber']);

                    $isAlreadyPosted = false;
                    if (isset($sageLogArray[12]) && $sageLogArray[12]['status'] == SageEnum::STATUS_FAIL) {
                        info('SAGE API :  Check status of  AR invoice batch '.$postedResponse['BatchNumber'].'  for '.$quote->code);
                        $arInvoiceBatch = $this->postToSage300('AR/ARInvoiceBatches('.$postedResponse['BatchNumber'].')', [], 'GET');
                        info('SAGE API :  Status of  AR invoice batch '.$arInvoiceBatch);
                        $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                        if ($arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                            info('SAGE API : AR invoice batch '.$postedResponse['BatchNumber'].' already posted for '.$quote->code);
                            $postedResponse = $aRPostInvoices['payload'];
                            $isAlreadyPosted = true;
                        }
                    }

                    if (! $isAlreadyPosted) {
                        info('SAGE API :  Send aRPostInvoices  for '.$quote->code);
                        $resp = $this->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                        $postedResponse = json_decode($resp, true);
                    }
                }

                if (isset($postedResponse['error'])) {
                    $errorMessage = 'Error while making Ar discount invoice Posted to sage';
                    $message = ' aRPostInvoices failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostInvoices, $postedResponse, 12, 15, 'fail']);
                } else {
                    info('SAGE API : '.$quote->code.' : aRPostInvoices  completed successfully');
                    if ($isLiveApiCallStep12) {
                        $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, 12, 15);
                    }
                }
            } else {
                $errorMessage = 'Ar discount invoice failed from sage';
                $message = ' createARInvoiceDis failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $createARInvoiceDis, $postedResponse, 10, 15, 'fail']);
            }
            info('  ########## End createARInvoiceDis for : '.$quote->code.' ########## ');
        }

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AR Discount invoice created on sage';

        return $returnMessage;
    }
    public function applyPaymentInvoices($sageRequestDataArray)
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $isTotalPriceZero = $payment->total_price == 0;

        /* Start: Temporary code for historic data to allow book polciy after m2 launch */
        $isQuoteFallUnderSkippableCriteria = $this->skipApplyPrepaymentsForSpecificLeads($quote, $payment, $paymentSplits);
        if ($isQuoteFallUnderSkippableCriteria['status']) {
            return $isQuoteFallUnderSkippableCriteria;
        }
        /* End: Temporary code for historic data to allow book polciy after m2 launch */

        /* applyPaymentInvoices */
        $isTransactionPaidAndFrequencyUpfront = $sageRequest->invoicePaymentStatus == PaymentStatusEnum::PAID && $payment->frequency == PaymentFrequency::UPFRONT;

        if ($isTransactionPaidAndFrequencyUpfront && ! $isTotalPriceZero) {
            return $this->applyUpfrontPaymentInvoices($sageRequestDataArray);
        } elseif ($isTotalPriceZero) {
            info('########## applyUpfrontPaymentInvoices skipped  for : '.$quote->code.' due to zero price ########## ');
        }

        $isFrequencySplitAndFirstChildPaymentPaid = $sageRequest->invoicePaymentStatus == PaymentStatusEnum::PAID && $payment->frequency == PaymentFrequency::SPLIT_PAYMENTS;

        if ($isFrequencySplitAndFirstChildPaymentPaid) {
            return $this->applySplitPaymentInvoices($sageRequestDataArray);
        }

        $isFrequencyUpfrontOrSplit = in_array($payment->frequency, [PaymentFrequency::UPFRONT, PaymentFrequency::SPLIT_PAYMENTS]);
        $isFirstPaymentPaidOrCaptured = in_array($paymentSplits[0]['payment_status_id'], [PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED]);
        if (! $isFrequencyUpfrontOrSplit && $isFirstPaymentPaidOrCaptured) {
            return $this->applyNonSplitNonUpfrontPaymentInvoices($sageRequestDataArray);
        }

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Apply Prepayment completed';

        return $returnMessage;

    }

    private function applyUpfrontPaymentInvoices($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        info('########## Start applypaymentInvoices for : '.$quote->code.' ##########');
        $totalSteps = 15;
        //13
        $currentStep = 13;
        $isLiveApiCallStep13 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
            info('SAGE API : createPaymontRecieptOneInvoice  Sent Already for '.$quote->code);
            $isLiveApiCallStep13 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            info('SAGE API :  Send createPaymontRecieptOneInvoice  for '.$quote->code);
            $payLoadOptions = SagePayloadFactory::createPaymontRecieptOneInvoice($quote, $sageRequest->customerId, $payment, $paymentSplits, true);
            $resp = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if ($isLiveApiCallStep13) {
            $this->logSageApiCall($payLoadOptions, $postedResponse, $quote, $currentStep, $totalSteps);
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = 'Error while making split prepayments to sage';
            $message = 'createPaymontRecieptOneInvoice failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $payLoadOptions, $postedResponse, $currentStep, $totalSteps, 'fail']);
        }

        $batchNumber = $postedResponse['BatchNumber'];
        info('SAGE API : '.$quote->code.' : createPaymontRecieptOneInvoice - BatchNumber : '.$batchNumber.' completed successfully');
        //14
        $currentStep = 14;
        $isLiveApiCallStep14 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
            info('SAGE API :  readyToPostReceiptAr  Sent Already for '.$quote->code);
            $isLiveApiCallStep14 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            info('SAGE API :  Send readyToPostReceiptAr  for '.$quote->code);
            $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr($batchNumber);
            $readyToPostResponse = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = 'Error while making Apply payment ready to post to sage';
            $message = 'readyToPostReceiptAr failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAr, $readyToPostResponse, $currentStep, $totalSteps, 'fail']);
        } else {
            info('SAGE API : '.$quote->code.' : readyToPostReceiptAr completed successfully');
            if ($isLiveApiCallStep14) {
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps);
            }
        }

        //15
        $currentStep = 15;
        $isLiveApiCallStep15 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
            info('SAGE API :  aRPostReceipts  Sent Already for '.$quote->code);
            $isLiveApiCallStep15 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            $aRPostReceipts = SagePayloadFactory::aRPostReceipts($batchNumber);

            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                info('SAGE API :  Check status of  AR Prepayment Receipts batch '.$batchNumber.'  for '.$quote->code);
                $aRReceiptBatch = $this->postToSage300('AR/ARReceiptAndAdjustmentBatches(BatchRecordType="CA",BatchNumber='.$batchNumber.')', [], 'GET');
                info('SAGE API :  Status of  AR Prepayment Receipts batch '.$aRReceiptBatch);
                $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                if ($aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    info('SAGE API : AR Prepayment Receipts batch '.$batchNumber.' already posted for '.$quote->code);
                    $postedResponse = $aRPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                info('SAGE API :  Send aRPostReceipts  for '.$quote->code);
                $resp = $this->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }

        }

        if (isset($postedResponse['error'])) {
            $errorMessage = 'Error while making Apply payment Posted to sage';
            $message = 'aRPostReceipts failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostReceipts, $postedResponse, $currentStep, $totalSteps, 'fail']);
        }
        info('SAGE API : '.$quote->code.' : aRPostReceipts completed successfully');
        if ($isLiveApiCallStep15) {
            $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $currentStep, $totalSteps);
        }
        info('  ########## End applypaymentInvoices for : '.$quote->code.' ########## ');
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Prepayments applied on sage';

        return $returnMessage;
    }
    private function applySplitPaymentInvoices($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        info('########## Start arSplitPrepaymentPayload for : '.$quote->code.'##########');
        $totalSteps = 15;

        //12
        $currentStep = 13;
        $isLiveApiCallStep13 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
            info('SAGE API :  arSplitPrepaymentPayload  Sent Already for '.$quote->code);
            $isLiveApiCallStep13 = false;
            $response = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            info('SAGE API :  Send arSplitPrepaymentPayload  for '.$quote->code);
            $readyToPostReceiptAr = SagePayloadFactory::arSplitPrepaymentPayload($quote, $sageRequest->customerId, $payment, $paymentSplits, true);

            $resp = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'POST');
            $response = json_decode($resp, true);
        }

        if (isset($response['error'])) {
            $errorMessage = 'Error while making Apply split prepayments to sage';
            $message = ' arSplitPrepaymentPayload failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAr, $response, $currentStep, $totalSteps, 'fail']);
        }

        if ($isLiveApiCallStep13) {
            $this->logSageApiCall($readyToPostReceiptAr, $response, $quote, $currentStep, $totalSteps);
        }

        $batchNumber = $response['BatchNumber'];
        info('SAGE API : '.$quote->code.' : readyToPostInvoiceAr - BatchNumber : '.$batchNumber.' completed successfully');
        //14
        $currentStep = 14;
        $isLiveApiCallStep14 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
            info('SAGE API :  readyToPostReceiptAr  Sent Already for '.$quote->code);
            $isLiveApiCallStep14 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            info('SAGE API :  Send readyToPostReceiptAr  for '.$quote->code);
            $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr($batchNumber);
            $readyToPostResponse = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = 'Error while making Apply payment ready to post to sage';
            $message = ' readyToPostReceiptAr - BatchNumber : '.$batchNumber.' failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAr, $readyToPostResponse, $currentStep, $totalSteps, 'fail']);
        } else {
            info('SAGE API : '.$quote->code.' :  readyToPostReceiptAr - BatchNumber : '.$batchNumber.' completed successfully');
            if ($isLiveApiCallStep14) {
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps);
            }
        }

        //15
        $currentStep = 15;
        $isLiveApiCallStep15 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
            info('SAGE API : aRPostReceipts  Sent Already for '.$quote->code);
            $isLiveApiCallStep15 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            $aRPostReceipts = SagePayloadFactory::aRPostReceipts($batchNumber);

            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                info('SAGE API :  Check status of  AR Prepayment Receipts batch '.$batchNumber.'  for '.$quote->code);
                $aRReceiptBatch = $this->postToSage300('AR/ARReceiptAndAdjustmentBatches(BatchRecordType="CA",BatchNumber='.$batchNumber.')', [], 'GET');
                info('SAGE API :  Status of  AR Prepayment Receipts batch '.$aRReceiptBatch);
                $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                if ($aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    info('SAGE API : AP Prepayment Receipts batch '.$batchNumber.' already posted for '.$quote->code);
                    $postedResponse = $aRPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                info('SAGE API : Send aRPostReceipts  for '.$quote->code);
                $resp = $this->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = 'Error while making Apply payment Posted to sage';
            $message = ' aRPostReceipts failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostReceipts, $postedResponse, $currentStep, $totalSteps, 'fail']);
        }
        info('SAGE API : '.$quote->code.' : aRPostReceipts completed successfully');
        if ($isLiveApiCallStep15) {
            $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $currentStep, $totalSteps);
        }
        info('########## End arSplitPrepaymentPayload for : '.$quote->code.' ##########');

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Prepayments applied on sage';

        return $returnMessage;
    }
    private function applyNonSplitNonUpfrontPaymentInvoices($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        info('########## Start arSplitPrepaymentPayload for : '.$quote->code.'##########');
        $totalSteps = 18;

        //15
        $currentStep = 16;
        $isLiveApiCallStep16 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
            info('SAGE API : arSplitPrepaymentPayload  Sent Already for '.$quote->code);
            $isLiveApiCallStep16 = false;
            $response = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            info('SAGE API : Send arSplitPrepaymentPayload  for '.$quote->code);
            $readyToPostReceiptAr = SagePayloadFactory::arSplitPrepaymentPayload($quote, $sageRequest->customerId, $payment, $paymentSplits, false);

            $resp = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'POST');
            $response = json_decode($resp, true);
        }

        if (isset($response['error'])) {
            $errorMessage = 'Error while making Apply split prepayments to sage';
            $message = 'arSplitPrepaymentPayload failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAr, $response, $currentStep, $totalSteps, 'fail']);
        }
        if ($isLiveApiCallStep16) {
            $this->logSageApiCall($readyToPostReceiptAr, $response, $quote, $currentStep, $totalSteps);

        }
        $batchNumber = $response['BatchNumber'];
        info('SAGE API : '.$quote->code.' : readyToPostInvoiceAr - BatchNumber : '.$batchNumber.' completed successfully');
        //16
        $currentStep = 17;
        $isLiveApiCallStep17 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
            info('SAGE API :  readyToPostReceiptAr  Sent Already for '.$quote->code);
            $isLiveApiCallStep17 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            info('SAGE API :  Send readyToPostReceiptAr  for '.$quote->code);
            $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr($batchNumber);
            $readyToPostResponse = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = 'Error while making Apply payment ready to post to sage';
            $message = 'readyToPostReceiptAr  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAr, $readyToPostResponse, $currentStep, $totalSteps, 'fail']);
        } else {
            info('SAGE API : '.$quote->code.' : readyToPostReceiptAr completed successfully');
            if ($isLiveApiCallStep17) {
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps);
            }
        }

        //17
        $currentStep = 18;
        $isLiveApiCallStep18 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
            info('SAGE API :  aRPostReceipts  Sent Already for '.$quote->code);
            $isLiveApiCallStep18 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            $aRPostReceipts = SagePayloadFactory::aRPostReceipts($batchNumber);

            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                info('SAGE API :  Check status of  AR Prepayment Receipts batch '.$batchNumber.'  for '.$quote->code);
                $aRReceiptBatch = $this->postToSage300('AR/ARReceiptAndAdjustmentBatches(BatchRecordType="CA",BatchNumber='.$batchNumber.')', [], 'GET');
                info('SAGE API :  Status of  AR Prepayment Receipts batch '.$aRReceiptBatch);
                $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                if ($aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    info('SAGE API : AP Prepayment Receipts batch '.$batchNumber.' already posted for '.$quote->code);
                    $postedResponse = $aRPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                info('SAGE API :  Send aRPostReceipts  for '.$quote->code);
                $resp = $this->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }

        }

        if (isset($postedResponse['error'])) {
            $errorMessage = 'Error while making Apply payment Posted to sage';
            $message = 'aRPostReceipts - BatchNumber '.$batchNumber.' failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostReceipts, $postedResponse, $currentStep, $totalSteps, 'fail']);
        }
        if ($isLiveApiCallStep18) {
            $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $currentStep, $totalSteps);
        }
        info('SAGE API : '.$quote->code.' : aRPostReceipts - BatchNumber '.$batchNumber.' completed successfully');
        info('  ########## End arSplitPrepaymentPayload for : '.$quote->code.' ########## ');

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Prepayments applied on sage';

        return $returnMessage;
    }
    private function logErrorAndReturn($logDataArray, $storeSageApiLog = true): array
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        [$quote, $message, $errorMessage, $payload, $response, $currentStep, $totalSteps, $status] = $logDataArray;

        Log::error("SAGE API : $quote->code : $message");
        Log::error("SAGE API : $quote->code : $errorMessage");

        $returnMessage['message'] = $errorMessage;
        $responseArray = $this->convertResponseToArray($response);
        $sageErrorMessage = $responseArray['error']['message']['value'] ?? $responseArray['error'] ?? null;
        Log::error("SAGE API : $quote->uuid  : ".json_encode($sageErrorMessage));
        $returnMessage['error'] = $sageErrorMessage;
        if (str_contains($sageErrorMessage, 'Processing conflict')) {
            $returnMessage['message'] = 'Please wait for 1 minute before booking again.';
        }

        if ($storeSageApiLog) {
            $this->logSageApiCall($payload, $response, $quote, $currentStep, $totalSteps, $status);
        }

        return $returnMessage;
    }
    public function convertResponseToArray($response)
    {
        if (is_array($response)) {
            return $response;
        }

        return json_decode($response, true);
    }

    public function skipApplyPrepaymentsForSpecificLeads($quote, $payment, $paymentSplits)
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        $startDate = Carbon::parse('2024-04-23')->startOfDay();
        $endDate = Carbon::parse('2024-08-15')->endOfDay();
        $quoteCreatedAt = Carbon::parse($quote->created_at);

        $isQuoteCreatedWithInDateRange = $quoteCreatedAt->between($startDate, $endDate);
        $isPaymentMethodCreditApproval = $payment->payment_methods_code == PaymentMethodsEnum::CreditApproval;
        $isPaymentFrequencySplitPayment = $payment->frequency == PaymentFrequency::SPLIT_PAYMENTS;

        if ($isQuoteCreatedWithInDateRange && ! $isPaymentMethodCreditApproval) {
            if ($isPaymentFrequencySplitPayment) {
                $paymentSplitsWithNoSageReceipt = $paymentSplits->whereNull('sage_reciept_id')->count();
                if ($paymentSplitsWithNoSageReceipt) {
                    info('  ########## applyUpfrontPaymentInvoices skipped  for : '.$quote->code.' due to sage receipt not generated on sage ########## ');
                    $returnMessage['status'] = true;
                    $returnMessage['message'] = 'Apply Prepayment skipped due to sage receipt not generated on sage';

                    return $returnMessage;
                }

                return $returnMessage;
            } else {
                $firstPaymentSplitWithNoSageReceipt = $paymentSplits->where('sr_no', 1)->whereNull('sage_reciept_id')->count();
                if ($firstPaymentSplitWithNoSageReceipt) {
                    info('  ########## applyUpfrontPaymentInvoices skipped  for : '.$quote->code.' due to sage receipt not generated on sage ########## ');
                    $returnMessage['status'] = true;
                    $returnMessage['message'] = 'Apply Prepayment skipped due to sage receipt not generated on sage';

                    return $returnMessage;
                }
            }

            return $returnMessage;
        }

        return $returnMessage;
    }
}
