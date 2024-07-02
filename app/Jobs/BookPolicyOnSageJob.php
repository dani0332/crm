<?php

namespace App\Jobs;

use App\Enums\PaymentFrequency;
use App\Enums\PaymentStatusEnum;
use App\Factories\SagePayloadFactory;
use App\Services\SageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BookPolicyOnSageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;
    private $sageRequest;
    private $quote;
    private $payment;
    private $paymentSplits;
    private $skipAPInvoicePatchAndPosting;
    private $aPInvoicePatchAndPostingOnly;

    /**
     * Create a new job instance.
     */
    public function __construct($sageRequest, $quote, $payment, $paymentSplits, $skipAPInvoicePatchAndPosting, $aPInvoicePatchAndPostingOnly)
    {
        $this->sageRequest = $sageRequest;
        $this->quote = $quote;
        $this->payment = $payment;
        $this->paymentSplits = $paymentSplits;
        $this->skipAPInvoicePatchAndPosting = $skipAPInvoicePatchAndPosting;
        $this->aPInvoicePatchAndPostingOnly = $aPInvoicePatchAndPostingOnly;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        info('################################## Sage Book Policy started for : '.$this->quote->code.'##################################');
        info('Sage API - Payment frequency : '.$this->payment->frequency.' for '.$this->quote->uuid);
        if ($this->aPInvoicePatchAndPostingOnly) {
            info('########## Start of NON Upfront createAPInvoicePrem for : '.$this->quote->code.' which were skipped ########## ');
            if ($this->payment->frequency != 'upfront') {
                info('########## Start of NON Upfront createAPInvoicePrem for : '.$this->quote->code.' ##########');
                $isLiveApiCallStep6 = true;
                if (isset($sageLogArray[6]) && $sageLogArray[6]['status'] == 'success') {
                    info('SAGE API:  createAPInvoiceSplitPayments  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep6 = false;
                    $postedResponse = json_decode($sageLogArray[6]['response'], true);
                } else {
                    info('SAGE API:  Send createAPInvoiceSplitPayments  for '.$this->quote->uuid);
                    $createAPInvoicePrem = SagePayloadFactory::createAPInvoiceSplitPayments($this->sageRequest, $this->paymentSplits);
                    $resp = (new SageApiService())->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
                    $postedResponse = json_decode($resp, true);
                }

                if (! empty($postedResponse['BatchNumber'])) {
                    $url = 'AP/APInvoiceBatches('.$postedResponse['BatchNumber'].')';
                    info('SAGE API: '.$this->quote->uuid.' : createAPInvoiceSplitPayments - '.$postedResponse['BatchNumber'].' completed successfully');
                    if ($isLiveApiCallStep6) {
                        (new SageApiService())->logSageApiCall($createAPInvoicePrem, $postedResponse, $this->quote, 6, 15);
                    }

                    info('SAGE API:  Prepare Patch payload for SpitPayments  for '.$this->quote->uuid);
                    foreach ($postedResponse['Invoices'][0]['InvoicePaymentSchedules'] as $key => $value) {
                        // add discount amount to amount due for the first child payment in sage for balancing the amount
                        $dueAmount = roundNumber($this->paymentSplits[$key]['payment_amount'] + ($this->paymentSplits[$key]['sr_no'] == 1 ? $this->payment->discount_value : 0));
                        $invoicePaymentSchedulesDueDate = SagePayloadFactory::calculateDueDate(date('Y-m-d', strtotime($this->paymentSplits[$key]['due_date'])), $this->sageRequest->insurerInvoiceDate);
                        if ($this->payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                            $dueDate = $invoicePaymentSchedulesDueDate;
                        } else {
                            $dueDate = $this->paymentSplits[$key]['sr_no'] == 1 ? $invoicePaymentSchedulesDueDate : date('Y-m-d', strtotime($this->paymentSplits[$key]['due_date']));
                        }

                        $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['AmountDue'] = $dueAmount;
                        $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['DueDate'] = $dueDate;
                    }
                    //7
                    $isLiveApiCallStep7 = true;
                    if (isset($sageLogArray[7]) && $sageLogArray[7]['status'] == 'success') {
                        info('SAGE API:  Patch Request  Sent Already for '.$this->quote->uuid);
                        $isLiveApiCallStep7 = false;
                        $postedResponse = json_decode($sageLogArray[7]['response'], true);
                    } else {
                        info('SAGE API:  Send Patch Request  for '.$this->quote->uuid);
                        $resp = (new SageApiService())->postToSage300($url, $postedResponse, 'PATCH');
                        $postedResponse = json_decode($resp, true);
                    }

                    $postedResponse['endPoint'] = $url;
                    $postedResponse['payload'] = $postedResponse;
                    if (isset($postedResponse['error'])) {
                        Log::error('SAGE API: '.$this->quote->uuid.' : Patch Request failed');
                        (new SageApiService())->logSageApiCall($postedResponse, $postedResponse, $this->quote, 7, 15, 'fail');
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making AP split payments patch to sage';

                        Log::error('SAGE API: '.json_encode($returnMessage));
                    }
                    info('SAGE API: '.$this->quote->uuid.' : Patch Request completed successfully');
                    if ($isLiveApiCallStep7) {
                        (new SageApiService())->logSageApiCall($postedResponse, $postedResponse, $this->quote, 7, 15);
                    }

                    $isLiveApiCallStep8 = true;
                    if (isset($sageLogArray[8]) && $sageLogArray[8]['status'] == 'success') {
                        info('SAGE API:  readyToPostInvoiceAP  Sent Already for '.$this->quote->uuid);
                        $isLiveApiCallStep8 = false;
                        $readyToPostResponse = json_decode($sageLogArray[8]['response'], true);
                    } else {
                        info('SAGE API:  Send readyToPostInvoiceAP  for '.$this->quote->uuid);
                        $readyToPostInvoiceAP = SagePayloadFactory::readyToPostInvoiceAP($postedResponse['BatchNumber']);
                        $readyToPostResponse = (new SageApiService())->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
                    }

                    if ($readyToPostResponse !== '') {
                        Log::error('SAGE API: '.$this->quote->uuid.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' failed');
                        (new SageApiService())->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $this->quote, 8, 15, 'fail');
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making AP invoice ready to post to sage';

                        Log::error('SAGE API: '.json_encode($returnMessage));
                    } else {
                        info('SAGE API: '.$this->quote->uuid.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' completed successfully');
                        if ($isLiveApiCallStep8) {
                            (new SageApiService())->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $this->quote, 8, 15);
                        }
                    }

                    $isLiveApiCallStep9 = true;
                    if (isset($sageLogArray[9]) && $sageLogArray[9]['status'] == 'success') {
                        info('SAGE API:  aPPostInvoices  Sent Already for '.$this->quote->uuid);
                        $isLiveApiCallStep9 = false;
                        $postedResponse = json_decode($sageLogArray[9]['response'], true);
                    } else {
                        info('SAGE API:  Send aPPostInvoices  for '.$this->quote->uuid);
                        $aPPostInvoices = SagePayloadFactory::aPPostInvoices($postedResponse['BatchNumber']);
                        $resp = (new SageApiService())->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                        $postedResponse = json_decode($resp, true);
                    }

                    if (isset($postedResponse['error'])) {
                        Log::error('SAGE API: '.$this->quote->uuid.' : aPPostInvoices failed');
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making AP invoices Posted to sage';
                        (new SageApiService())->logSageApiCall($aPPostInvoices, $postedResponse, $this->quote, 9, 15, 'fail');

                        Log::error('SAGE API: '.json_encode($returnMessage));
                    } else {
                        info('SAGE API: '.$this->quote->uuid.' : aPPostInvoices completed successfully');
                        if ($isLiveApiCallStep9) {
                            (new SageApiService())->logSageApiCall($aPPostInvoices, $postedResponse, $this->quote, 9, 15);
                        }
                    }

                } else {
                    Log::error('SAGE API: '.$this->quote->uuid.' : createAPInvoicePrem  failed');
                    (new SageApiService())->logSageApiCall($createAPInvoicePrem, $postedResponse, $this->quote, 6, 14, 'fail');
                    $returnMessage['message'] = 'Ap invoice prem failed from sage';
                    $returnMessage['status'] = false;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                }
                info('  ########## End of NON Upfront createAPInvoicePrem for : '.$this->quote->code.' ########## ');
            } else {
                info('SAGE API:  createAPInvoiceSplitPayments  not done due to frequency issue for '.$this->quote->uuid);
            }
            info('########## End of NON Upfront createAPInvoicePrem for : '.$this->quote->code.' which were skipped ########## ');
        } else {
            // frequency  is 'upfront'
            if ($this->payment->frequency == PaymentFrequency::UPFRONT) {
                /* createARInvoicePremAndComm */
                $isLiveApiCallStep2 = true;
                if (isset($sageLogArray[2]) && $sageLogArray[2]['status'] == 'success') {
                    info('SAGE API:  createARInvoicePremAndComm  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep2 = false;
                    $sageResponse = json_decode($sageLogArray[2]['response'], true);
                } else {
                    info('SAGE API:  Send createARInvoicePremAndComm  for '.$this->quote->uuid);
                    $payLoadOptions = SagePayloadFactory::createARInvoicePremAndComm($this->sageRequest);
                    $resp = (new SageApiService())->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
                    $sageResponse = json_decode($resp, true);
                }

                if (! empty($sageResponse['BatchNumber'])) {
                    info('SAGE API: '.$this->quote->uuid.' :  Batch Number - '.$sageResponse['BatchNumber'].' for createARInvoicePremAndComm');
                    if ($isLiveApiCallStep2) {
                        (new SageApiService())->logSageApiCall($payLoadOptions, $sageResponse, $this->quote, 2, 13);
                    }
                    $isLiveApiCallStep3 = true;
                    if (isset($sageLogArray[3]) && $sageLogArray[3]['status'] == 'success') {
                        info('SAGE API:  readyToPostInvoiceAr  Sent Already for '.$this->quote->uuid);
                        $isLiveApiCallStep3 = false;
                        $readyToPostResponse = json_decode($sageLogArray[3]['response'], true);
                    } else {
                        info('SAGE API:  Send readyToPostInvoiceAr  for '.$this->quote->uuid);
                        $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr($sageResponse['BatchNumber']);
                        $readyToPostResponse = (new SageApiService())->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
                    }

                    if ($readyToPostResponse !== '') {
                        Log::error('SAGE API: '.$this->quote->uuid.' : readyToPostInvoiceAr - '.$sageResponse['BatchNumber'].' failed');
                        (new SageApiService())->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $this->quote, 3, 13, 'fail');
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making Ar invoice & prem ready to post to sage';

                        $readyToPostResponseArray = $this->convertResponseToArray($readyToPostResponse);
                        $errorMessage = $readyToPostResponseArray['error']['message']['value'] ?? null;
                        Log::error('SAGE API : '.$errorMessage);
                        $returnMessage['error'] = $errorMessage;

                        Log::error('SAGE API: '.json_encode($returnMessage));
                    }
                    info('SAGE API: '.$this->quote->uuid.' : readyToPostInvoiceAr - '.$sageResponse['BatchNumber'].' completed successfully');
                    if ($isLiveApiCallStep3) {
                        (new SageApiService())->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $this->quote, 3, 13);
                    }

                    $isLiveApiCallStep4 = true;
                    if (isset($sageLogArray[4]) && $sageLogArray[4]['status'] == 'success') {
                        info('SAGE API:  aRPostInvoices  Sent Already for '.$this->quote->uuid);
                        $isLiveApiCallStep4 = false;
                        $postedResponse = json_decode($sageLogArray[4]['response'], true);
                    } else {
                        info('SAGE API:  Send aRPostInvoices  for '.$this->quote->uuid);
                        $aRPostInvoices = SagePayloadFactory::aRPostInvoices($sageResponse['BatchNumber']);
                        $resp = (new SageApiService())->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                        $postedResponse = json_decode($resp, true);
                    }

                    if (isset($postedResponse['error'])) {
                        Log::error('SAGE API: '.$this->quote->uuid.' : aRPostInvoices - '.$sageResponse['BatchNumber'].' failed');
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making Ar invoice & prem Posted to sage';
                        $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                        Log::error('SAGE API : '.$errorMessage);
                        $returnMessage['error'] = $errorMessage;
                        (new SageApiService())->logSageApiCall($aRPostInvoices, $postedResponse, $this->quote, 4, 13, 'fail');

                        Log::error('SAGE API: '.json_encode($returnMessage));
                    }
                    info('SAGE API: '.$this->quote->uuid.' : aRPostInvoices - '.$sageResponse['BatchNumber'].' completed successfully');
                    if ($isLiveApiCallStep4) {
                        (new SageApiService())->logSageApiCall($aRPostInvoices, $postedResponse, $this->quote, 4, 13);
                    }
                } else {
                    Log::error('SAGE API: '.$this->quote->uuid.' : createARInvoicePremAndComm  failed');
                    (new SageApiService())->logSageApiCall($payLoadOptions, $sageResponse, $this->quote, 2, 13, 'fail');
                    $returnMessage['message'] = 'Ar invoice & prem failed from sage';
                    $returnMessage['status'] = false;

                    $errorMessage = $sageResponse['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                }
            } else {
                //2
                $isLiveApiCallStep2 = true;
                if (isset($sageLogArray[2]) && $sageLogArray[2]['status'] == 'success') {
                    info('SAGE API:  createARInvoiceSplitPayments  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep2 = false;
                    $postedResponse = json_decode($sageLogArray[2]['response'], true);
                } else {
                    info('SAGE API:  Send createARInvoiceSplitPayments  for '.$this->quote->uuid);
                    $createARInvoiceSplitPayments = SagePayloadFactory::createARInvoiceSplitPayments($this->sageRequest, $this->paymentSplits);
                    $resp = (new SageApiService())->postToSage300($createARInvoiceSplitPayments['endPoint'], $createARInvoiceSplitPayments['payload']);
                    $postedResponse = json_decode($resp, true);
                }

                if (empty($postedResponse['BatchNumber'])) {
                    Log::error('SAGE API: '.$this->quote->uuid.' : createARInvoiceSplitPayments  failed');
                    (new SageApiService())->logSageApiCall($createARInvoiceSplitPayments, $postedResponse, $this->quote, 2, 13, 'fail');
                    $returnMessage['message'] = 'ar split payment failed from sage';
                    $returnMessage['status'] = false;

                    $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                }
                info('SAGE API: '.$this->quote->uuid.' : createARInvoiceSplitPayments - BatchNumber : '.$postedResponse['BatchNumber'].' completed successfully');
                $batchNumber = $postedResponse['BatchNumber'];
                if ($isLiveApiCallStep2) {
                    (new SageApiService())->logSageApiCall($createARInvoiceSplitPayments, $postedResponse, $this->quote, 2, 13);
                }

                $url = 'AR/ARInvoiceBatches('.$batchNumber.')';
                info('SAGE API:  Send Post AR/ARInvoiceBatches  for '.$this->quote->uuid);
                $resp = (new SageApiService())->postToSage300($url, [], 'GET');
                $postedResponse = json_decode($resp, true);

                if (empty($postedResponse['Invoices'][0]['InvoicePaymentSchedules'])) {
                    Log::error($this->quote->uuid.' : Post AR/ARInvoiceBatches for batchNumber : '.$batchNumber.' failed');
                    $returnMessage['status'] = false;
                    $returnMessage['message'] = 'Error while get ar2 split payments from sage';

                    $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                }
                info('SAGE API:  Prepare Patch payload for SpitPayments  for '.$this->quote->uuid);
                foreach ($postedResponse['Invoices'][0]['InvoicePaymentSchedules'] as $key => $value) {
                    // add discount amount to amount due for the first child payment in sage for balancing the amount
                    $invoicePaymentSchedulesDueDate = SagePayloadFactory::calculateDueDate(date('Y-m-d', strtotime($this->paymentSplits[$key]['due_date'])), $this->sageRequest->insurerInvoiceDate);
                    $dueAmount = roundNumber($this->paymentSplits[$key]['payment_amount'] + ($this->paymentSplits[$key]['sr_no'] == 1 ? $this->payment->discount_value : 0));

                    if ($this->payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                        $dueDate = $invoicePaymentSchedulesDueDate;
                    } else {
                        $dueDate = $this->paymentSplits[$key]['sr_no'] == 1 ? $invoicePaymentSchedulesDueDate : date('Y-m-d', strtotime($this->paymentSplits[$key]['due_date']));
                    }

                    $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['AmountDue'] = $dueAmount;
                    $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['DueDate'] = $dueDate;
                }

                info('SAGE API:  Prepare Patch payload for Commission Spits  for '.$this->quote->uuid);
                /* Add Vat on commission to the first Installment of commission */
                $vatOnCommission = floatval($this->payment->commission_vat);
                $commission = floatval($this->payment->commission);
                $commissionWithoutVat = ($commission - $vatOnCommission);
                $commissionSplit = $commissionWithoutVat > 0 ? $commissionWithoutVat / count($this->paymentSplits) : 0;

                $commissionSplitSumWithoutLastSplit = 0;
                foreach ($postedResponse['Invoices'][1]['InvoicePaymentSchedules'] as $key => $value) {
                    // Add Vat on commission to the first installment of commission in sage for balancing the amount
                    $dueCommissionSplitAmount = roundNumber($commissionSplit);
                    if ($postedResponse['Invoices'][1]['InvoicePaymentSchedules'][$key]['PaymentNumber'] == 1) {
                        $dueCommissionSplitAmount = roundNumber(roundNumber($commissionSplit) + roundNumber($vatOnCommission));
                    }
                    /*
                     to prevent difference in amount due to rounding number, sum all the dueCommissionSplitAmount except the last one,
                     and then subtract that amount from the total commission with vat and use the result as dueAmount for last installment
                    */
                    if ($postedResponse['Invoices'][1]['InvoicePaymentSchedules'][$key]['PaymentNumber'] == count($this->paymentSplits)) {
                        $dueCommissionSplitAmount = floatval(sprintf('%.2f', $commission - $commissionSplitSumWithoutLastSplit));
                    } else {
                        $commissionSplitSumWithoutLastSplit += $dueCommissionSplitAmount;
                    }

                    $invoicePaymentSchedulesDueDate = SagePayloadFactory::calculateDueDate(date('Y-m-d', strtotime($this->paymentSplits[$key]['due_date'])), $this->sageRequest->insurerInvoiceDate);
                    if ($this->payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                        $dueDate = $invoicePaymentSchedulesDueDate;
                    } else {
                        $dueDate = $this->paymentSplits[$key]['sr_no'] == 1 ? $invoicePaymentSchedulesDueDate : date('Y-m-d', strtotime($this->paymentSplits[$key]['due_date']));
                    }
                    $postedResponse['Invoices'][1]['InvoicePaymentSchedules'][$key]['AmountDue'] = $dueCommissionSplitAmount;
                    $postedResponse['Invoices'][1]['InvoicePaymentSchedules'][$key]['DueDate'] = $dueDate;
                }
                //3
                $isLiveApiCallStep3 = true;
                if (isset($sageLogArray[3]) && $sageLogArray[3]['status'] == 'success') {
                    info('SAGE API:  Patch Request  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep3 = false;
                    $postedResponse = json_decode($sageLogArray[3]['response'], true);
                } else {
                    info('SAGE API:  Send Patch Request  for '.$this->quote->uuid);
                    $resp = (new SageApiService())->postToSage300($url, $postedResponse, 'PATCH');
                    $postedResponse = json_decode($resp, true);
                }
                $postedResponse['endPoint'] = $url;
                $postedResponse['payload'] = $postedResponse;
                if (isset($postedResponse['error'])) {
                    Log::error('SAGE API: '.$this->quote->uuid.' : Patch Request failed');
                    (new SageApiService())->logSageApiCall($postedResponse, $postedResponse, $this->quote, 3, 13, 'fail');
                    $returnMessage['status'] = false;
                    $returnMessage['message'] = 'Error while making ar2 split payments patch to sage';

                    $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                }
                if ($isLiveApiCallStep3) {
                    (new SageApiService())->logSageApiCall($postedResponse, $postedResponse, $this->quote, 3, 13);
                }

                // 4
                $isLiveApiCallStep4 = true;
                if (isset($sageLogArray[4]) && $sageLogArray[4]['status'] == 'success') {
                    info('SAGE API:  readyToPostInvoiceAr  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep4 = false;
                    $readyToPostResponse = json_decode($sageLogArray[4]['response'], true);
                } else {
                    info('SAGE API:  Send readyToPostInvoiceAr  for '.$this->quote->uuid);
                    $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr($batchNumber);
                    $readyToPostResponse = (new SageApiService())->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
                }

                if (isset($readyToPostResponse['error'])) {
                    Log::error('SAGE API: '.$this->quote->uuid.' : readyToPostInvoiceAr failed');
                    (new SageApiService())->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $this->quote, 4, 13, 'fail');
                    $returnMessage['status'] = false;
                    $returnMessage['message'] = 'Error while making ar2 Apply split payment ready to post to sage';

                    $errorMessage = $readyToPostResponse['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                }
                if ($isLiveApiCallStep4) {
                    (new SageApiService())->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $this->quote, 4, 13);
                }
                info('SAGE API: '.$this->quote->uuid.' : readyToPostInvoiceAr completed successfully');

                // 5
                $isLiveApiCallStep5 = true;
                if (isset($sageLogArray[5]) && $sageLogArray[5]['status'] == 'success') {
                    info('SAGE API:  aRPostInvoices  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep5 = false;
                    $postedResponse = json_decode($sageLogArray[5]['response'], true);
                } else {
                    info('SAGE API:  Send aRPostInvoices  for '.$this->quote->uuid);
                    $aRPostInvoices = SagePayloadFactory::aRPostInvoices($batchNumber);
                    $resp = (new SageApiService())->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                    $postedResponse = json_decode($resp, true);
                }
                if (isset($postedResponse['error'])) {
                    Log::error('SAGE API: '.$this->quote->uuid.' : aRPostInvoices  failed');
                    $returnMessage['status'] = false;
                    $returnMessage['message'] = 'Error while making ar2 Apply split payment Posted to sage';
                    (new SageApiService())->logSageApiCall($aRPostInvoices, $postedResponse, $this->quote, 5, 13, 'fail');

                    $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                } else {
                    info('SAGE API: '.$this->quote->uuid.' : aRPostInvoices completed successfully');
                    if ($isLiveApiCallStep5) {
                        (new SageApiService())->logSageApiCall($aRPostInvoices, $postedResponse, $this->quote, 5, 13);
                    }
                }
            }

            /* createAPInvoicePrem */

            // total_payments = 1 means upfront payment
            if ($this->payment->frequency == PaymentFrequency::UPFRONT) {
                info('########## Start of Upfront createAPInvoicePrem for : '.$this->quote->code.'##########');
                $isLiveApiCallStep5 = true;
                if (isset($sageLogArray[5]) && $sageLogArray[5]['status'] == 'success') {
                    info('SAGE API:  createAPInvoicePrem  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep5 = false;
                    $postedResponse = json_decode($sageLogArray[5]['response'], true);
                } else {
                    info('SAGE API:  Send createAPInvoicePrem  for '.$this->quote->uuid);
                    $createAPInvoicePrem = SagePayloadFactory::createAPInvoicePrem($this->sageRequest);
                    $resp = (new SageApiService())->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
                    $postedResponse = json_decode($resp, true);
                }

                if (! empty($postedResponse['BatchNumber'])) {
                    info('SAGE API: '.$this->quote->uuid.' : readyToPostInvoiceAr - '.$postedResponse['BatchNumber'].' completed successfully');
                    if ($isLiveApiCallStep5) {
                        (new SageApiService())->logSageApiCall($createAPInvoicePrem, $postedResponse, $this->quote, 5, 13);
                    }

                    $isLiveApiCallStep6 = true;
                    if (isset($sageLogArray[6]) && $sageLogArray[6]['status'] == 'success') {
                        info('SAGE API:  readyToPostInvoiceAP  Sent Already for '.$this->quote->uuid);
                        $isLiveApiCallStep6 = false;
                        $readyToPostResponse = json_decode($sageLogArray[6]['response'], true);
                    } else {
                        info('SAGE API:  Send readyToPostInvoiceAP  for '.$this->quote->uuid);
                        $readyToPostInvoiceAP = SagePayloadFactory::readyToPostInvoiceAP($postedResponse['BatchNumber']);
                        $readyToPostResponse = (new SageApiService())->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
                    }

                    if ($readyToPostResponse !== '') {
                        Log::error('SAGE API: '.$this->quote->uuid.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' failed');
                        (new SageApiService())->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $this->quote, 6, 13, 'fail');
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making AP invoice ready to post to sage';

                        $readyToPostResponseArray = $this->convertResponseToArray($readyToPostResponse);
                        $errorMessage = $readyToPostResponseArray['error']['message']['value'] ?? null;
                        Log::error('SAGE API : '.$errorMessage);
                        $returnMessage['error'] = $errorMessage;

                        Log::error('SAGE API: '.json_encode($returnMessage));
                    } else {
                        info('SAGE API: '.$this->quote->uuid.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' completed successfully');
                        if ($isLiveApiCallStep6) {
                            (new SageApiService())->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $this->quote, 6, 13);
                        }
                    }

                    $isLiveApiCallStep7 = true;
                    if (isset($sageLogArray[7]) && $sageLogArray[7]['status'] == 'success') {
                        info('SAGE API:  aPPostInvoices  Sent Already for '.$this->quote->uuid);
                        $isLiveApiCallStep7 = false;
                        $postedResponse = json_decode($sageLogArray[7]['response'], true);
                    } else {
                        info('SAGE API:  Send aPPostInvoices  for '.$this->quote->uuid);
                        $aPPostInvoices = SagePayloadFactory::aPPostInvoices($postedResponse['BatchNumber']);
                        $resp = (new SageApiService())->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                        $postedResponse = json_decode($resp, true);
                    }

                    if (isset($postedResponse['error'])) {
                        Log::error('SAGE API: '.$this->quote->uuid.' : aPPostInvoices failed');
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making AP invoices Posted to sage';
                        (new SageApiService())->logSageApiCall($aPPostInvoices, $postedResponse, $this->quote, 7, 13, 'fail');

                        $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                        Log::error('SAGE API : '.$errorMessage);
                        $returnMessage['error'] = $errorMessage;

                        Log::error('SAGE API: '.json_encode($returnMessage));
                    } else {
                        info('SAGE API: '.$this->quote->uuid.' : aPPostInvoices completed successfully');
                        if ($isLiveApiCallStep7) {
                            (new SageApiService())->logSageApiCall($aPPostInvoices, $postedResponse, $this->quote, 7, 13);
                        }
                    }
                } else {
                    Log::error('SAGE API: '.$this->quote->uuid.' : createAPInvoicePrem  failed');
                    (new SageApiService())->logSageApiCall($createAPInvoicePrem, $postedResponse, $this->quote, 5, 13, 'fail');
                    $returnMessage['message'] = 'Ap invoice prem failed from sage';
                    $returnMessage['status'] = false;

                    $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                }
                info('  ########## End of Upfront createAPInvoicePrem for : '.$this->quote->code.' ########## ');
            } else {
                info('  ########## Start of NON Upfront createAPInvoicePrem for : '.$this->quote->code.' ########## ');
                $isLiveApiCallStep6 = true;
                if (isset($sageLogArray[6]) && $sageLogArray[6]['status'] == 'success') {
                    info('SAGE API:  createAPInvoiceSplitPayments  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep6 = false;
                    $postedResponse = json_decode($sageLogArray[6]['response'], true);
                } else {
                    info('SAGE API:  Send createAPInvoiceSplitPayments  for '.$this->quote->uuid);
                    $createAPInvoicePrem = SagePayloadFactory::createAPInvoiceSplitPayments($this->sageRequest, $this->paymentSplits);
                    $resp = (new SageApiService())->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
                    $postedResponse = json_decode($resp, true);
                }

                if (! empty($postedResponse['BatchNumber'])) {
                    if (! $this->skipAPInvoicePatchAndPosting) {
                        $url = 'AP/APInvoiceBatches('.$postedResponse['BatchNumber'].')';
                        info('SAGE API: '.$this->quote->uuid.' : createAPInvoiceSplitPayments - '.$postedResponse['BatchNumber'].' completed successfully');
                        if ($isLiveApiCallStep6) {
                            (new SageApiService())->logSageApiCall($createAPInvoicePrem, $postedResponse, $this->quote, 6, 15);
                        }

                        info('SAGE API:  Prepare Patch payload for SpitPayments  for '.$this->quote->uuid);
                        foreach ($postedResponse['Invoices'][0]['InvoicePaymentSchedules'] as $key => $value) {
                            // add discount amount to amount due for the first child payment in sage for balancing the amount
                            $dueAmount = roundNumber($this->paymentSplits[$key]['payment_amount'] + ($this->paymentSplits[$key]['sr_no'] == 1 ? $this->payment->discount_value : 0));
                            $invoicePaymentSchedulesDueDate = SagePayloadFactory::calculateDueDate(date('Y-m-d', strtotime($this->paymentSplits[$key]['due_date'])), $this->sageRequest->insurerInvoiceDate);
                            if ($this->payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                                $dueDate = $invoicePaymentSchedulesDueDate;
                            } else {
                                $dueDate = $this->paymentSplits[$key]['sr_no'] == 1 ? $invoicePaymentSchedulesDueDate : date('Y-m-d', strtotime($this->paymentSplits[$key]['due_date']));
                            }

                            $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['AmountDue'] = $dueAmount;
                            $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['DueDate'] = $dueDate;
                        }
                        //7
                        $isLiveApiCallStep7 = true;
                        if (isset($sageLogArray[7]) && $sageLogArray[7]['status'] == 'success') {
                            info('SAGE API:  Patch Request  Sent Already for '.$this->quote->uuid);
                            $isLiveApiCallStep7 = false;
                            $postedResponse = json_decode($sageLogArray[7]['response'], true);
                        } else {
                            info('SAGE API:  Send Patch Request  for '.$this->quote->uuid);
                            $resp = (new SageApiService())->postToSage300($url, $postedResponse, 'PATCH');
                            $postedResponse = json_decode($resp, true);
                        }

                        $postedResponse['endPoint'] = $url;
                        $postedResponse['payload'] = $postedResponse;
                        if (isset($postedResponse['error'])) {
                            Log::error('SAGE API: '.$this->quote->uuid.' : Patch Request failed');
                            (new SageApiService())->logSageApiCall($postedResponse, $postedResponse, $this->quote, 7, 15, 'fail');
                            $returnMessage['status'] = false;
                            $returnMessage['message'] = 'Error while making AP split payments patch to sage';

                            $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                            Log::error('SAGE API : '.$errorMessage);
                            $returnMessage['error'] = $errorMessage;

                            Log::error('SAGE API: '.json_encode($returnMessage));
                        }
                        info('SAGE API: '.$this->quote->uuid.' : Patch Request completed successfully');
                        if ($isLiveApiCallStep7) {
                            (new SageApiService())->logSageApiCall($postedResponse, $postedResponse, $this->quote, 7, 15);
                        }

                        $isLiveApiCallStep8 = true;
                        if (isset($sageLogArray[8]) && $sageLogArray[8]['status'] == 'success') {
                            info('SAGE API:  readyToPostInvoiceAP  Sent Already for '.$this->quote->uuid);
                            $isLiveApiCallStep8 = false;
                            $readyToPostResponse = json_decode($sageLogArray[8]['response'], true);
                        } else {
                            info('SAGE API:  Send readyToPostInvoiceAP  for '.$this->quote->uuid);
                            $readyToPostInvoiceAP = SagePayloadFactory::readyToPostInvoiceAP($postedResponse['BatchNumber']);
                            $readyToPostResponse = (new SageApiService())->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
                        }

                        if ($readyToPostResponse !== '') {
                            Log::error('SAGE API: '.$this->quote->uuid.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' failed');
                            (new SageApiService())->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $this->quote, 8, 15, 'fail');
                            $returnMessage['status'] = false;
                            $returnMessage['message'] = 'Error while making AP invoice ready to post to sage';

                            $readyToPostResponseArray = $this->convertResponseToArray($readyToPostResponse);
                            $errorMessage = $readyToPostResponseArray['error']['message']['value'] ?? null;
                            Log::error('SAGE API : '.$errorMessage);
                            $returnMessage['error'] = $errorMessage;

                            Log::error('SAGE API: '.json_encode($returnMessage));
                        } else {
                            info('SAGE API: '.$this->quote->uuid.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' completed successfully');
                            if ($isLiveApiCallStep8) {
                                (new SageApiService())->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $this->quote, 8, 15);
                            }
                        }

                        $isLiveApiCallStep9 = true;
                        if (isset($sageLogArray[9]) && $sageLogArray[9]['status'] == 'success') {
                            info('SAGE API:  aPPostInvoices  Sent Already for '.$this->quote->uuid);
                            $isLiveApiCallStep9 = false;
                            $postedResponse = json_decode($sageLogArray[9]['response'], true);
                        } else {
                            info('SAGE API:  Send aPPostInvoices  for '.$this->quote->uuid);
                            $aPPostInvoices = SagePayloadFactory::aPPostInvoices($postedResponse['BatchNumber']);
                            $resp = (new SageApiService())->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                            $postedResponse = json_decode($resp, true);
                        }

                        if (isset($postedResponse['error'])) {
                            Log::error('SAGE API: '.$this->quote->uuid.' : aPPostInvoices failed');
                            $returnMessage['status'] = false;
                            $returnMessage['message'] = 'Error while making AP invoices Posted to sage';
                            (new SageApiService())->logSageApiCall($aPPostInvoices, $postedResponse, $this->quote, 9, 15, 'fail');

                            $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                            Log::error('SAGE API : '.$errorMessage);
                            $returnMessage['error'] = $errorMessage;

                            Log::error('SAGE API: '.json_encode($returnMessage));
                        } else {
                            info('SAGE API: '.$this->quote->uuid.' : aPPostInvoices completed successfully');
                            if ($isLiveApiCallStep9) {
                                (new SageApiService())->logSageApiCall($aPPostInvoices, $postedResponse, $this->quote, 9, 15);
                            }
                        }
                    } else {
                        info('  ########## AP Invoice creation is skipped for : '.$this->quote->code.' ########## ');
                    }

                } else {
                    Log::error('SAGE API: '.$this->quote->uuid.' : createAPInvoicePrem  failed');
                    (new SageApiService())->logSageApiCall($createAPInvoicePrem, $postedResponse, $this->quote, 6, 14, 'fail');
                    $returnMessage['message'] = 'Ap invoice prem failed from sage';
                    $returnMessage['status'] = false;

                    $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                }
                info('  ########## End of NON Upfront createAPInvoicePrem for : '.$this->quote->code.' ########## ');
            }

            /* createARInvoiceDis */
            if ($this->sageRequest->discount > 0) {
                info('########## Start createARInvoiceDis for : '.$this->quote->code.'##########');
                $isLiveApiCallStep10 = true;
                if (isset($sageLogArray[10]) && $sageLogArray[10]['status'] == 'success') {
                    info('SAGE API:  createARInvoiceDis  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep10 = false;
                    $postedResponse = json_decode($sageLogArray[10]['response'], true);
                } else {
                    info('SAGE API:  Send createARInvoiceDis  for '.$this->quote->uuid);
                    $createARInvoiceDis = SagePayloadFactory::createARInvoiceDis($this->sageRequest);
                    $resp = (new SageApiService())->postToSage300($createARInvoiceDis['endPoint'], $createARInvoiceDis['payload']);
                    $postedResponse = json_decode($resp, true);
                }

                if (! empty($postedResponse['BatchNumber'])) {
                    info('SAGE API: '.$this->quote->uuid.' : createARInvoiceDis - BatchNumber : '.$postedResponse['BatchNumber'].' completed successfully');
                    if ($isLiveApiCallStep10) {
                        (new SageApiService())->logSageApiCall($createARInvoiceDis, $postedResponse, $this->quote, 10, 15);
                    }

                    $isLiveApiCallStep11 = true;
                    if (isset($sageLogArray[11]) && $sageLogArray[11]['status'] == 'success') {
                        info('SAGE API:  readyToPostInvoiceAr  Sent Already for '.$this->quote->uuid);
                        $isLiveApiCallStep11 = false;
                        $readyToPostResponse = json_decode($sageLogArray[11]['response'], true);
                    } else {
                        info('SAGE API:  Send readyToPostInvoiceAr  for '.$this->quote->uuid);
                        $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr($postedResponse['BatchNumber']);
                        $readyToPostResponse = (new SageApiService())->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
                    }

                    if ($readyToPostResponse !== '') {
                        Log::error('SAGE API: '.$this->quote->uuid.' : readyToPostInvoiceAr failed');
                        (new SageApiService())->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $this->quote, 11, 15, 'fail');
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making Ar discount invoice ready to post to sage';

                        $readyToPostResponseArray = $this->convertResponseToArray($readyToPostResponse);
                        $errorMessage = $readyToPostResponseArray['error']['message']['value'] ?? null;
                        Log::error('SAGE API : '.$errorMessage);
                        $returnMessage['error'] = $errorMessage;

                        Log::error('SAGE API: '.json_encode($returnMessage));
                    } else {
                        info('SAGE API: '.$this->quote->uuid.' : readyToPostInvoiceAr completed successfully');
                        if ($isLiveApiCallStep11) {
                            (new SageApiService())->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $this->quote, 11, 15);
                        }
                    }

                    $isLiveApiCallStep12 = true;
                    if (isset($sageLogArray[12]) && $sageLogArray[12]['status'] == 'success') {
                        info('SAGE API:  aRPostInvoices  Sent Already for '.$this->quote->uuid);
                        $isLiveApiCallStep12 = false;
                        $postedResponse = json_decode($sageLogArray[12]['response'], true);
                    } else {
                        info('SAGE API:  Send aRPostInvoices  for '.$this->quote->uuid);
                        $aRPostInvoices = SagePayloadFactory::aRPostInvoices($postedResponse['BatchNumber']);
                        $resp = (new SageApiService())->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                        $postedResponse = json_decode($resp, true);
                    }

                    if (isset($postedResponse['error'])) {
                        Log::error('SAGE API: '.$this->quote->uuid.' : aRPostInvoices failed');
                        $returnMessage['status'] = false;
                        $returnMessage['message'] = 'Error while making Ar discount invoice Posted to sage';
                        (new SageApiService())->logSageApiCall($aRPostInvoices, $postedResponse, $this->quote, 12, 15, 'fail');

                        $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                        Log::error('SAGE API : '.$errorMessage);
                        $returnMessage['error'] = $errorMessage;

                        Log::error('SAGE API: '.json_encode($returnMessage));
                    } else {
                        info('SAGE API: '.$this->quote->uuid.' : aRPostInvoices  completed successfully');
                        if ($isLiveApiCallStep12) {
                            (new SageApiService())->logSageApiCall($aRPostInvoices, $postedResponse, $this->quote, 12, 15);
                        }
                    }
                } else {
                    Log::error('SAGE API: '.$this->quote->uuid.' : createARInvoiceDis failed');
                    (new SageApiService())->logSageApiCall($createARInvoiceDis, $postedResponse, $this->quote, 10, 15, 'fail');
                    $returnMessage['message'] = 'Ar discount invoice failed from sage';
                    $returnMessage['status'] = false;

                    $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                }
                info('  ########## End createARInvoiceDis for : '.$this->quote->code.' ########## ');
            }

            /* applyPaymentInvoices */
            $isTransactionPaidAndFrequencyUpfront = $this->sageRequest->invoicePaymentStatus == PaymentStatusEnum::PAID && $this->payment->frequency == PaymentFrequency::UPFRONT;

            if ($isTransactionPaidAndFrequencyUpfront) {
                info('########## Start applyPaymentInvoices for : '.$this->quote->code.'##########');
                $totalSteps = 15;

                //13
                $currentStep = 13;
                $isLiveApiCallStep13 = true;
                if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                    info('SAGE API:  createPaymentReceiptOneInvoice  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep13 = false;
                    $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
                } else {
                    info('SAGE API:  Send createPaymentReceiptOneInvoice  for '.$this->quote->uuid);
                    $payLoadOptions = SagePayloadFactory::createPaymentReceiptOneInvoice($this->quote, $this->sageRequest->customerId, $this->payment, $this->paymentSplits, true);
                    $resp = (new SageApiService())->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
                    $postedResponse = json_decode($resp, true);
                }

                if ($isLiveApiCallStep13) {
                    (new SageApiService())->logSageApiCall($payLoadOptions, $postedResponse, $this->quote, $currentStep, $totalSteps);
                }

                if (isset($postedResponse['error'])) {
                    Log::error('SAGE API: '.$this->quote->uuid.' : createPaymentReceiptOneInvoice failed');
                    (new SageApiService())->logSageApiCall($payLoadOptions, $postedResponse, $this->quote, $currentStep, $totalSteps, 'fail');
                    $returnMessage['status'] = false;
                    $returnMessage['message'] = 'Error while making split prepayments to sage';

                    $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                }

                $batchNumber = $postedResponse['BatchNumber'];
                info('SAGE API: '.$this->quote->uuid.' : createPaymentReceiptOneInvoice - BatchNumber : '.$batchNumber.' completed successfully');
                //14
                $currentStep = 14;
                $isLiveApiCallStep14 = true;
                if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                    info('SAGE API:  readyToPostReceiptAr  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep14 = false;
                    $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
                } else {
                    info('SAGE API:  Send readyToPostReceiptAr  for '.$this->quote->uuid);
                    $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr($batchNumber);
                    $readyToPostResponse = (new SageApiService())->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
                }

                if ($readyToPostResponse !== '') {
                    Log::error('SAGE API: '.$this->quote->uuid.' : readyToPostReceiptAr failed');
                    (new SageApiService())->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $this->quote, $currentStep, $totalSteps, 'fail');
                    $returnMessage['status'] = false;
                    $returnMessage['message'] = 'Error while making Apply payment ready to post to sage';

                    $readyToPostResponseArray = $this->convertResponseToArray($readyToPostResponse);
                    $errorMessage = $readyToPostResponseArray['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                } else {
                    info('SAGE API: '.$this->quote->uuid.' : readyToPostReceiptAr completed successfully');
                    if ($isLiveApiCallStep14) {
                        (new SageApiService())->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $this->quote, $currentStep, $totalSteps);
                    }
                }

                //15
                $currentStep = 15;
                $isLiveApiCallStep15 = true;
                if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                    $isLiveApiCallStep15 = false;
                    $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
                } else {
                    $aRPostReceipts = SagePayloadFactory::aRPostReceipts($batchNumber);
                    $resp = (new SageApiService())->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                    $postedResponse = json_decode($resp, true);
                }

                if (isset($postedResponse['error'])) {
                    Log::error(('SAGE API: '.$this->quote->uuid.' : aRPostReceipts failed'));
                    $returnMessage['status'] = false;
                    $returnMessage['message'] = 'Error while making Apply payment Posted to sage';
                    (new SageApiService())->logSageApiCall($aRPostReceipts, $postedResponse, $this->quote, $currentStep, $totalSteps, 'fail');

                    $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                }info('SAGE API: '.$this->quote->uuid.' : aRPostReceipts completed successfully');
                if ($isLiveApiCallStep15) {
                    (new SageApiService())->logSageApiCall($aRPostReceipts, $postedResponse, $this->quote, $currentStep, $totalSteps);
                }
                info('  ########## End applyPaymentInvoices for : '.$this->quote->code.' ########## ');
            }

            $isFrequencySplitAndFirstChildPaymentPaid = $this->sageRequest->invoicePaymentStatus == PaymentStatusEnum::PAID && $this->payment->frequency == PaymentFrequency::SPLIT_PAYMENTS;

            if ($isFrequencySplitAndFirstChildPaymentPaid) {
                info('########## Start arSplitPrepaymentPayload for : '.$this->quote->code.'##########');
                $totalSteps = 15;

                //12
                $currentStep = 13;
                $isLiveApiCallStep13 = true;
                if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                    info('SAGE API:  arSplitPrepaymentPayload  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep13 = false;
                    $response = json_decode($sageLogArray[$currentStep]['response'], true);
                } else {
                    info('SAGE API:  Send arSplitPrepaymentPayload  for '.$this->quote->uuid);
                    $readyToPostReceiptAr = SagePayloadFactory::arSplitPrepaymentPayload($this->quote, $this->sageRequest->customerId, $this->payment, $this->paymentSplits, true);

                    $resp = (new SageApiService())->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'POST');
                    $response = json_decode($resp, true);
                }

                if (isset($response['error'])) {
                    Log::error('SAGE API: '.$this->quote->uuid.' : arSplitPrepaymentPayload failed');
                    (new SageApiService())->logSageApiCall($readyToPostReceiptAr, $response, $this->quote, $currentStep, $totalSteps, 'fail');
                    $returnMessage['status'] = false;
                    $returnMessage['message'] = 'Error while making Apply split prepayments to sage';

                    $errorMessage = $response['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                }

                if ($isLiveApiCallStep13) {
                    (new SageApiService())->logSageApiCall($readyToPostReceiptAr, $response, $this->quote, $currentStep, $totalSteps);
                }

                $batchNumber = $response['BatchNumber'];
                info('SAGE API: '.$this->quote->uuid.' : readyToPostInvoiceAr - BatchNumber : '.$batchNumber.' completed successfully');
                //14
                $currentStep = 14;
                $isLiveApiCallStep14 = true;
                if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                    info('SAGE API:  readyToPostReceiptAr  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep14 = false;
                    $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
                } else {
                    info('SAGE API:  Send readyToPostReceiptAr  for '.$this->quote->uuid);
                    $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr($batchNumber);
                    $readyToPostResponse = (new SageApiService())->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
                }

                if ($readyToPostResponse !== '') {
                    Log::error('SAGE API: '.$this->quote->uuid.' : readyToPostReceiptAr - BatchNumber : '.$batchNumber.' failed');
                    (new SageApiService())->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $this->quote, $currentStep, $totalSteps, 'fail');
                    $returnMessage['status'] = false;
                    $returnMessage['message'] = 'Error while making Apply payment ready to post to sage';

                    $readyToPostResponseArray = $this->convertResponseToArray($readyToPostResponse);
                    $errorMessage = $readyToPostResponseArray['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                } else {
                    info('SAGE API: '.$this->quote->uuid.' :  readyToPostReceiptAr - BatchNumber : '.$batchNumber.' completed successfully');
                    if ($isLiveApiCallStep14) {
                        (new SageApiService())->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $this->quote, $currentStep, $totalSteps);
                    }
                }

                //15
                $currentStep = 15;
                $isLiveApiCallStep15 = true;
                if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                    info('SAGE API:  aRPostReceipts  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep15 = false;
                    $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
                } else {
                    info('SAGE API:  Send aRPostReceipts  for '.$this->quote->uuid);
                    $aRPostReceipts = SagePayloadFactory::aRPostReceipts($batchNumber);
                    $resp = (new SageApiService())->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                    $postedResponse = json_decode($resp, true);
                }

                if (isset($postedResponse['error'])) {
                    Log::error('SAGE API: '.$this->quote->uuid.' : aRPostReceipts failed');
                    $returnMessage['status'] = false;
                    $returnMessage['message'] = 'Error while making Apply payment Posted to sage';
                    (new SageApiService())->logSageApiCall($aRPostReceipts, $postedResponse, $this->quote, $currentStep, $totalSteps, 'fail');

                    $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                }
                info('SAGE API: '.$this->quote->uuid.' : aRPostReceipts completed successfully');
                if ($isLiveApiCallStep15) {
                    (new SageApiService())->logSageApiCall($aRPostReceipts, $postedResponse, $this->quote, $currentStep, $totalSteps);
                }
                info('  ########## End arSplitPrepaymentPayload for : '.$this->quote->code.' ########## ');
            }

            if (! in_array($this->payment->frequency, [PaymentFrequency::UPFRONT, PaymentFrequency::SPLIT_PAYMENTS]) && in_array($this->paymentSplits[0]['payment_status_id'], [PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED])) {
                info('########## Start arSplitPrepaymentPayload for : '.$this->quote->code.'##########');
                $totalSteps = 18;

                //15
                $currentStep = 16;
                $isLiveApiCallStep16 = true;
                if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                    info('SAGE API:  arSplitPrepaymentPayload  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep16 = false;
                    $response = json_decode($sageLogArray[$currentStep]['response'], true);
                } else {
                    info('SAGE API:  Send arSplitPrepaymentPayload  for '.$this->quote->uuid);
                    $readyToPostReceiptAr = SagePayloadFactory::arSplitPrepaymentPayload($this->quote, $this->sageRequest->customerId, $this->payment, $this->paymentSplits, false);

                    $resp = (new SageApiService())->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'POST');
                    $response = json_decode($resp, true);
                }

                if (isset($response['error'])) {
                    Log::error('SAGE API: '.$this->quote->uuid.' : arSplitPrepaymentPayload failed');
                    (new SageApiService())->logSageApiCall($readyToPostReceiptAr, $response, $this->quote, $currentStep, $totalSteps, 'fail');
                    $returnMessage['status'] = false;
                    $returnMessage['message'] = 'Error while making Apply split prepayments to sage';

                    $errorMessage = $response['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                }
                if ($isLiveApiCallStep16) {
                    (new SageApiService())->logSageApiCall($readyToPostReceiptAr, $response, $this->quote, $currentStep, $totalSteps);

                }
                $batchNumber = $response['BatchNumber'];
                info('SAGE API: '.$this->quote->uuid.' : readyToPostInvoiceAr - BatchNumber : '.$batchNumber.' completed successfully');
                //16
                $currentStep = 17;
                $isLiveApiCallStep17 = true;
                if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                    info('SAGE API:  readyToPostReceiptAr  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep17 = false;
                    $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
                } else {
                    info('SAGE API:  Send readyToPostReceiptAr  for '.$this->quote->uuid);
                    $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr($batchNumber);
                    $readyToPostResponse = (new SageApiService())->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
                }

                if ($readyToPostResponse !== '') {
                    Log::error('SAGE API: '.$this->quote->uuid.' : readyToPostReceiptAr  failed');
                    (new SageApiService())->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $this->quote, $currentStep, $totalSteps, 'fail');
                    $returnMessage['status'] = false;
                    $returnMessage['message'] = 'Error while making Apply payment ready to post to sage';

                    $readyToPostResponseArray = $this->convertResponseToArray($readyToPostResponse);
                    $errorMessage = $readyToPostResponseArray['error']['message']['value'] ?? null;
                    Log::error('SAGE API : '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;

                    Log::error('SAGE API: '.json_encode($returnMessage));
                } else {
                    info('SAGE API: '.$this->quote->uuid.' : readyToPostReceiptAr completed successfully');
                    if ($isLiveApiCallStep17) {
                        (new SageApiService())->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $this->quote, $currentStep, $totalSteps);
                    }
                }

                //17
                $currentStep = 18;
                $isLiveApiCallStep18 = true;
                if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == 'success') {
                    info('SAGE API:  aRPostReceipts  Sent Already for '.$this->quote->uuid);
                    $isLiveApiCallStep18 = false;
                    $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
                } else {
                    info('SAGE API:  Send aRPostReceipts  for '.$this->quote->uuid);
                    $aRPostReceipts = SagePayloadFactory::aRPostReceipts($batchNumber);
                    $resp = (new SageApiService())->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                    $postedResponse = json_decode($resp, true);
                }

                if (isset($postedResponse['error'])) {
                    Log::error('SAGE API: '.$this->quote->uuid.' : aRPostReceipts - BatchNumber '.$batchNumber.' failed');
                    $returnMessage['status'] = false;
                    $returnMessage['message'] = 'Error while making Apply payment Posted to sage';
                    (new SageApiService())->logSageApiCall($aRPostReceipts, $postedResponse, $this->quote, $currentStep, $totalSteps, 'fail');

                    $errorMessage = $postedResponse['error']['message']['value'] ?? null;
                    Log::error('SAGE API: '.$errorMessage);
                    $returnMessage['error'] = $errorMessage;
                    Log::error('SAGE API: '.json_encode($returnMessage));
                }
                if ($isLiveApiCallStep18) {
                    (new SageApiService())->logSageApiCall($aRPostReceipts, $postedResponse, $this->quote, $currentStep, $totalSteps);
                }
                info('SAGE API: '.$this->quote->uuid.' : aRPostReceipts - BatchNumber '.$batchNumber.' completed successfully');
                info('  ########## End arSplitPrepaymentPayload for : '.$this->quote->code.' ########## ');
            }
        }
        info('################################## Sage Policy Booked for : '.$this->quote->code.'##################################');

    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->quote->uuid))->dontRelease()];
    }

    private function convertResponseToArray($response)
    {
        if (is_array($response)) {
            return $response;
        }

        return json_decode($response, true);
    }
}
