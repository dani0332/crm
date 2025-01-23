<?php

namespace App\Services;

use App\Enums\PaymentFrequency;
use App\Enums\SageEnum;
use App\Factories\SagePayloadFactory;
use App\Models\Payment;
use App\Traits\SageLoggable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SageCustomApiService
{
    use SageLoggable;

    private mixed $sageCustomApiPassword;
    private mixed $sageCustomApiUserName;
    private string $sageBaseUrl;
    private string $sageCustomApiVersion;
    private string $sageDBName;
    private mixed $bearerToken = null;
    private mixed $maxAttempts = 10;

    public function __construct()
    {
        $this->sageCustomApiUserName = config('constants.SAGE_300_CUSTOM_API_USERNAME');
        $this->sageCustomApiPassword = config('constants.SAGE_300_CUSTOM_API_USER_PASSWORD');
        $this->sageBaseUrl = config('constants.SAGE_300_BASE_URL');
        $this->sageCustomApiVersion = config('constants.SAGE_300_CUSTOM_API_VERSION');
        $this->sageDBName = config('constants.SAGE_300_CUSTOM_API_DB_NAME');
        $this->bearerToken = Cache::store('redis')->get(SageEnum::SAGE_CUSTOM_API_AUTH_TOKEN_CACHE_KEY) ?? $this->getToken();
    }

    public function getToken()
    {
        if ($this->bearerToken) {
            return $this->bearerToken;
        }

        $loginUrl = $this->sageBaseUrl.$this->sageCustomApiVersion.SageEnum::SAGE_CUSTOM_API_GET_AUTH_TOKEN_ENDPOINT;
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Database-Name' => $this->sageDBName,
        ])->post($loginUrl, [
            'username' => $this->sageCustomApiUserName,
            'password' => $this->sageCustomApiPassword,
        ]);
        if ($response->successful()) {
            Cache::store('redis')->put(SageEnum::SAGE_CUSTOM_API_AUTH_TOKEN_CACHE_KEY, $response->body(), 60 * 30);
            $this->bearerToken = Cache::store('redis')->get(SageEnum::SAGE_CUSTOM_API_AUTH_TOKEN_CACHE_KEY);

            return $this->bearerToken;
        } else {
            return null;
        }
    }

    public function getAPInvoicePaymentScheduleByBatchNumber($batchNumber, $currentAttempts = 0)
    {
        $responseData = [
            'error' => null,
            'response' => null,
            'status' => false,
        ];

        while (! $this->bearerToken && $this->maxAttempts >= $currentAttempts) {
            $this->bearerToken = $this->getToken();
            $currentAttempts++;
        }

        if ($this->maxAttempts < $currentAttempts) {
            $responseData['error'] = 'execution timeout! Please try again later.';

            return $responseData;
        }

        $getAPInvoicePaymentScheduleUrl = $this->sageBaseUrl.$this->sageCustomApiVersion.SageEnum::SAGE_CUSTOM_API_GET_AP_PAYMENT_SCHEDULE_ENDPOINT.$batchNumber;
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->bearerToken,
            'X-Database-Name' => $this->sageDBName,
        ])->get($getAPInvoicePaymentScheduleUrl);

        if ($response->successful()) {
            $responseData['response'] = $response->object();
            $responseData['status'] = true;

            return $responseData;
        } elseif ($response->failed()) {
            $responseData['response'] = $response->object();
            $responseData['error'] = $response->object()?->message;
            if ($responseData['error'] == SageEnum::SAGE_CUSTOM_API_INVALID_TOKEN_MESSAGE) {
                $this->bearerToken = null;
                $currentAttempts++;

                return $this->getAPInvoicePaymentScheduleByBatchNumber($batchNumber, $currentAttempts);
            }

            return $responseData;
        }
    }

    public function updateAPInvoicePaymentSchedule($batchNumber, $aPInvoicePaymentsSchedule, $currentAttempts = 0)
    {
        $responseData = [
            'error' => null,
            'response' => null,
            'url' => null,
            'status' => false,
        ];

        while (! $this->bearerToken && $this->maxAttempts >= $currentAttempts) {
            $this->bearerToken = $this->getToken();
            $currentAttempts++;
        }

        if ($this->maxAttempts < $currentAttempts) {
            $responseData['error'] = 'execution timeout! Please try again later.';

            return $responseData;
        }
        $endPoint = $this->sageCustomApiVersion.SageEnum::SAGE_CUSTOM_API_UPDATE_AP_PAYMENT_SCHEDULE_ENDPOINT.$batchNumber;
        $updateAPInvoicePaymentScheduleUrl = $this->sageBaseUrl.$endPoint;
        $responseData['url'] = $endPoint;
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->bearerToken,
            'Content-Type' => 'application/json',
            'X-Database-Name' => $this->sageDBName,
        ])->put($updateAPInvoicePaymentScheduleUrl, $aPInvoicePaymentsSchedule);

        if ($response->successful()) {
            $responseData['response'] = $response->object();
            $responseData['status'] = true;

            return $responseData;
        } elseif ($response->failed()) {
            if ($response->badRequest()) {
                $responseData['response'] = $response->object();
                $responseData['error'] = $response->object()?->title ?? $response->object()?->message ?? 'Something went wrong!';

                return $responseData;
            }
            $responseData['response'] = $response->object();
            $responseData['error'] = $response->object()?->title ?? 'Something went wrong!';
            if ($responseData['error'] == SageEnum::SAGE_CUSTOM_API_INVALID_TOKEN_MESSAGE) {
                $this->bearerToken = null;
                $currentAttempts++;

                return $this->updateAPInvoicePaymentSchedule($batchNumber, $aPInvoicePaymentsSchedule, $currentAttempts);
            }

            return $responseData;
        }
    }

    /*
     * This function is temporarily created to resolve the issue related to the Patch and Posting of Skipped AP Invoices
     * */
    public function postOpenedAPInvoices()
    {
        $quotes = [];
        $payments = Payment::whereNot('frequency', PaymentFrequency::UPFRONT)->whereNull('send_update_log_id')->whereIn('insurer_tax_number', $this->getOpenAPInvoicesDocumentNumbers())->get();
        echo $payments->count().'<br/>';
        try {
            foreach ($payments as $index => $payment) {
                $paymentSplits = $payment->paymentSplits;
                $modelClass = $payment->paymentable_type;
                $modelId = $payment->paymentable_id;
                $quote = $modelClass::find($modelId);
                echo $quote->id.'<br/>';
                $quotes[$index] = $quote->id;
                $quoteType = $quote?->quoteType?->code;
                if (! $quoteType) {
                    $quoteType = str_replace('Quote', '', class_basename($modelClass));
                }
                $sageRequest = app(SagePayloadFactory::class)->sagePayLoad($quoteType, $payment, $quote, $paymentSplits);
                $sageLogArray = $quote->sageApiLogs->keyBy('step')->toArray();

                // createAPInvoicePrem
                // total_payments = 1 means upfront payment
                if ($payment->frequency == PaymentFrequency::UPFRONT) {
                    echo '########## Start of Upfront createAPInvoicePrem for : '.$quote->code.'##########';
                    info('########## Start of Upfront createAPInvoicePrem for : '.$quote->code.'##########');
                    $isLiveApiCallStep5 = true;
                    if (isset($sageLogArray[5]) && $sageLogArray[5]['status'] == 'success') {
                        info('SAGE API:  createAPInvoicePrem  Sent Already for '.$quote->uuid);
                        $isLiveApiCallStep5 = false;
                        $postedResponse = json_decode($sageLogArray[5]['response'], true);
                    } else {
                        info('SAGE API:  Send createAPInvoicePrem  for '.$quote->uuid);
                        $createAPInvoicePrem = SagePayloadFactory::createAPInvoicePrem($sageRequest);
                        $resp = (new SageApiService)->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
                        $postedResponse = json_decode($resp, true);
                    }

                    if (! empty($postedResponse['BatchNumber'])) {
                        info('SAGE API: '.$quote->uuid.' : readyToPostInvoiceAr - '.$postedResponse['BatchNumber'].' completed successfully');
                        if ($isLiveApiCallStep5) {
                            $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $quote, 5, 13);
                        }

                        $isLiveApiCallStep6 = true;
                        if (isset($sageLogArray[6]) && $sageLogArray[6]['status'] == 'success') {
                            info('SAGE API:  readyToPostInvoiceAP  Sent Already for '.$quote->uuid);
                            $isLiveApiCallStep6 = false;
                            $readyToPostResponse = json_decode($sageLogArray[6]['response'], true);
                        } else {
                            info('SAGE API:  Send readyToPostInvoiceAP  for '.$quote->uuid);
                            $readyToPostInvoiceAP = SagePayloadFactory::readyToPostInvoiceAP($postedResponse['BatchNumber']);
                            $readyToPostResponse = (new SageApiService)->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
                        }

                        if ($readyToPostResponse !== '') {
                            Log::error('SAGE API: '.$quote->uuid.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' failed');
                            $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $quote, 6, 13, 'fail');
                            $returnMessage['status'] = false;
                            $returnMessage['message'] = 'Error while making AP invoice ready to post to sage';

                            $readyToPostResponseArray = (new SageApiService)->convertResponseToArray($readyToPostResponse);
                            $errorMessage = $readyToPostResponseArray['error']['message']['value'] ?? null;
                            Log::error('SAGE API : '.$errorMessage);
                            $returnMessage['error'] = $errorMessage;

                            return $returnMessage;
                        } else {
                            info('SAGE API: '.$quote->uuid.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' completed successfully');
                            if ($isLiveApiCallStep6) {
                                $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $quote, 6, 13);
                            }
                        }

                        $isLiveApiCallStep7 = true;
                        if (isset($sageLogArray[7]) && $sageLogArray[7]['status'] == 'success') {
                            info('SAGE API:  aPPostInvoices  Sent Already for '.$quote->uuid);
                            $isLiveApiCallStep7 = false;
                            $postedResponse = json_decode($sageLogArray[7]['response'], true);
                        } else {
                            info('SAGE API:  Send aPPostInvoices  for '.$quote->uuid);
                            $aPPostInvoices = SagePayloadFactory::aPPostInvoices($postedResponse['BatchNumber']);
                            $resp = (new SageApiService)->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                            $postedResponse = json_decode($resp, true);
                        }

                        if (isset($postedResponse['error'])) {
                            Log::error('SAGE API: '.$quote->uuid.' : aPPostInvoices failed');
                            $returnMessage['status'] = false;
                            $returnMessage['message'] = 'Error while making AP invoices Posted to sage';
                            $this->logSageApiCall($aPPostInvoices, $postedResponse, $quote, 7, 13, 'fail');

                            $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                            Log::error('SAGE API : '.$errorMessage);
                            $returnMessage['error'] = $errorMessage;

                            return $returnMessage;
                        } else {
                            info('SAGE API: '.$quote->uuid.' : aPPostInvoices completed successfully');
                            if ($isLiveApiCallStep7) {
                                $this->logSageApiCall($aPPostInvoices, $postedResponse, $quote, 7, 13);
                            }
                        }
                    } else {
                        Log::error('SAGE API: '.$quote->uuid.' : createAPInvoicePrem  failed');
                        $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $quote, 5, 13, 'fail');
                        $returnMessage['message'] = 'Ap invoice prem failed from sage';
                        $returnMessage['status'] = false;

                        $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                        Log::error('SAGE API : '.$errorMessage);
                        $returnMessage['error'] = $errorMessage;

                        return $returnMessage;
                    }
                    info('  ########## End of Upfront createAPInvoicePrem for : '.$quote->code.' ########## ');
                    echo '  ########## End of Upfront createAPInvoicePrem for : '.$quote->code.' ########## ';
                } else {
                    echo '  ########## Start of NON Upfront createAPInvoicePrem for : '.$quote->code.' ########## ';
                    info('  ########## Start of NON Upfront createAPInvoicePrem for : '.$quote->code.' ########## ');
                    $isLiveApiCallStep6 = true;
                    if (isset($sageLogArray[6]) && $sageLogArray[6]['status'] == 'success') {
                        info('SAGE API:  createAPInvoiceSplitPayments  Sent Already for '.$quote->uuid);
                        $isLiveApiCallStep6 = false;
                        $postedResponse = json_decode($sageLogArray[6]['response'], true);
                    } else {
                        info('SAGE API:  Send createAPInvoiceSplitPayments  for '.$quote->uuid);
                        $createAPInvoicePrem = SagePayloadFactory::createAPInvoiceSplitPayments($sageRequest, $paymentSplits);
                        $resp = (new SageApiService)->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
                        $postedResponse = json_decode($resp, true);
                    }

                    if (! empty($postedResponse['BatchNumber'])) {
                        $apBatchNumber = $postedResponse['BatchNumber'];
                        echo $apBatchNumber.'<br/>';
                        $url = 'AP/APInvoiceBatches('.$apBatchNumber.')';
                        info('SAGE API: '.$quote->uuid.' : createAPInvoiceSplitPayments - '.$apBatchNumber.' completed successfully');
                        if ($isLiveApiCallStep6) {
                            $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $quote, 6, 15);
                        }

                        info('SAGE API:  Prepare Patch payload for SpitPayments  for '.$quote->uuid);
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
                            }
                        } else {
                            $returnMessage['status'] = false;
                            $returnMessage['message'] = 'Error while getting split payment schedule from sage';
                            $returnMessage['error'] = $aPInvoicePaymentsScheduleResponse['error'];

                            return $returnMessage;
                        }
                        // 7
                        $isLiveApiCallStep7 = true;
                        if (isset($sageLogArray[7]) && $sageLogArray[7]['status'] == 'success') {
                            info('SAGE API:  Patch Request  Sent Already for '.$quote->uuid);
                            $isLiveApiCallStep7 = false;
                            $postedResponse = json_decode($sageLogArray[7]['response'], true);
                        } else {
                            info('SAGE API:  Send Patch Request  for '.$quote->uuid);
                            $resp = (new SageCustomApiService)->updateAPInvoicePaymentSchedule($postedResponse['BatchNumber'], $aPInvoicePaymentsSchedule);
                            $postedResponse['response'] = $resp;
                        }

                        $postedResponse['endPoint'] = $resp['url'];
                        $postedResponse['payload'] = $aPInvoicePaymentsSchedule;
                        if (! $resp['status']) {
                            Log::error('SAGE API: '.$quote->uuid.' : Patch Request failed');
                            $this->logSageApiCall($postedResponse, $postedResponse, $quote, 7, 15, 'fail');
                            $returnMessage['status'] = false;
                            $returnMessage['message'] = 'Error while making AP split payments patch to sage';

                            $errorMessage = $resp['error'] ?? null;
                            Log::error('SAGE API : '.$errorMessage);
                            $returnMessage['error'] = $errorMessage;

                            return $returnMessage;
                        }
                        info('SAGE API: '.$quote->uuid.' : Patch Request completed successfully');
                        if ($isLiveApiCallStep7) {
                            $this->logSageApiCall($postedResponse, $postedResponse, $quote, 7, 15);
                        }

                        $isLiveApiCallStep8 = true;
                        if (isset($sageLogArray[8]) && $sageLogArray[8]['status'] == 'success') {
                            info('SAGE API:  readyToPostInvoiceAP  Sent Already for '.$quote->uuid);
                            $isLiveApiCallStep8 = false;
                            $readyToPostResponse = json_decode($sageLogArray[8]['response'], true);
                        } else {
                            info('SAGE API:  Send readyToPostInvoiceAP  for '.$quote->uuid);
                            $readyToPostInvoiceAP = SagePayloadFactory::readyToPostInvoiceAP($apBatchNumber);
                            $readyToPostResponse = (new SageApiService)->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
                        }

                        if ($readyToPostResponse !== '') {
                            Log::error('SAGE API: '.$quote->uuid.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' failed');
                            $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $quote, 8, 15, 'fail');
                            $returnMessage['status'] = false;
                            $returnMessage['message'] = 'Error while making AP invoice ready to post to sage';

                            $readyToPostResponseArray = (new SageApiService)->convertResponseToArray($readyToPostResponse);
                            $errorMessage = $readyToPostResponseArray['error']['message']['value'] ?? null;
                            Log::error('SAGE API : '.$errorMessage);
                            $returnMessage['error'] = $errorMessage;

                            return $returnMessage;
                        } else {
                            info('SAGE API: '.$quote->uuid.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' completed successfully');
                            if ($isLiveApiCallStep8) {
                                $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $quote, 8, 15);
                            }
                        }

                        $isLiveApiCallStep9 = true;
                        if (isset($sageLogArray[9]) && $sageLogArray[9]['status'] == 'success') {
                            info('SAGE API:  aPPostInvoices  Sent Already for '.$quote->uuid);
                            $isLiveApiCallStep9 = false;
                            $postedResponse = json_decode($sageLogArray[9]['response'], true);
                        } else {
                            info('SAGE API:  Send aPPostInvoices  for '.$quote->uuid);
                            $aPPostInvoices = SagePayloadFactory::aPPostInvoices($postedResponse['BatchNumber']);
                            $resp = (new SageApiService)->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                            $postedResponse = json_decode($resp, true);
                        }

                        if (isset($postedResponse['error'])) {
                            Log::error('SAGE API: '.$quote->uuid.' : aPPostInvoices failed');
                            $returnMessage['status'] = false;
                            $returnMessage['message'] = 'Error while making AP invoices Posted to sage';
                            $this->logSageApiCall($aPPostInvoices, $postedResponse, $quote, 9, 15, 'fail');

                            $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                            Log::error('SAGE API : '.$errorMessage);
                            $returnMessage['error'] = $errorMessage;

                            return $returnMessage;
                        } else {
                            info('SAGE API: '.$quote->uuid.' : aPPostInvoices completed successfully');
                            if ($isLiveApiCallStep9) {
                                $this->logSageApiCall($aPPostInvoices, $postedResponse, $quote, 9, 15);
                            }
                        }

                    } else {
                        Log::error('SAGE API: '.$quote->uuid.' : createAPInvoicePrem  failed');
                        $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $quote, 6, 14, 'fail');
                        $returnMessage['message'] = 'Ap invoice prem failed from sage';
                        $returnMessage['status'] = false;

                        $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                        Log::error('SAGE API : '.$errorMessage);
                        $returnMessage['error'] = $errorMessage;

                        return $returnMessage;
                    }
                    info('  ########## End of NON Upfront createAPInvoicePrem for : '.$quote->code.' ########## ');
                    echo '  ########## End of NON Upfront createAPInvoicePrem for : '.$quote->code.' ########## ';
                }
            }
        } catch (Throwable $e) {
            echo $e->getMessage();
            info($e->getMessage());
        }

        // return $quotes;

    }

    private function getOpenAPInvoicesDocumentNumbers()
    {
        return [
            'SHMOU23000123834',
        ];
    }
}
