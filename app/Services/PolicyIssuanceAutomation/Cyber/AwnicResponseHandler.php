<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Payment;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;

class AwnicResponseHandler
{
    private const API_FAILED = "API Failed";
    /**
     * Update quote with issue policy response data
     *
     * @param mixed $quote
     * @param \stdClass $issuePolicyResult
     * @return void
     */
    public function updateQuoteFromIssuePolicyResponse($quote, $issuePolicyResult): void
    {
        $quote->update([
            'policy_number' => $issuePolicyResult?->policyInfo?->policyNo,
            'policy_issuance_date' => $issuePolicyResult?->policyInfo?->policyIssuedDate,
            'policy_start_date' => $issuePolicyResult?->policyInfo?->policyStartDate,
            'policy_expiry_date' => $issuePolicyResult?->policyInfo?->policyEndDate,
            'price_vat_applicable' => $issuePolicyResult?->policyInfo?->premiumAmount,
            'vat' => $issuePolicyResult?->policyInfo?->prmVatAmt,
            'price_with_vat' => $issuePolicyResult?->policyInfo?->prmPayableAmt,
            'insurer_quote_number' => $issuePolicyResult?->QuoteRefNo ?? null,
            'quote_status_id' => QuoteStatusEnum::PolicyIssued,
            'policy_issuance_status_id' => PolicyIssuanceStatusEnum::PolicyIssued,
            'quote_status_date' => now(),
            'insurer_debit_note_doc_id' => $issuePolicyResult?->policyInfo?->drcrDocId,
            'insurer_tax_invoice_doc_id' => $issuePolicyResult?->policyInfo?->taxInvoiceDocId,
            'insurer_policy_doc_id' => $issuePolicyResult?->policyInfo?->policyDocId,
        ]);
    }

    /**
     * Update payment with commission data from issue policy response
     *
     * @param string $quoteCode
     * @param \stdClass $issuePolicyResult
     * @return void
     */
    public function updatePaymentFromIssuePolicyResponse(string $quoteCode, $issuePolicyResult): void
    {
        Payment::where('code', $quoteCode)->update([
            'commission_vat_applicable' => $issuePolicyResult?->policyInfo?->commissionPayableAmt,
            'commission' => $issuePolicyResult?->policyInfo?->commissionAmt,
            'commission_vat' => $issuePolicyResult?->policyInfo?->commissionVatAmt,
            'commmission_percentage' => $issuePolicyResult?->policyInfo?->CommissionPercentage ?? 0,
            'insurer_tax_number' => $issuePolicyResult?->policyInfo?->invoiceNo ?? null,
            'insurer_invoice_date' => $issuePolicyResult?->policyInfo?->policyIssuedDate ?? null,
            'insurer_commmission_invoice_number' => $issuePolicyResult?->policyInfo?->creditNoteNo ?? null,
        ]);
    }

    /**
     * Build step response array
     */
    public function buildStepResponse(string $step, bool $status = false, ?string $message = null, $error = null, $data = null): array
    {
        return [
            'status' => $status,
            'completed_step' => $step,
            'message' => $message,
            'error' => $error,
            'data' => $data,
        ];
    }

    /**
     * Normalize HTTP response from AWNI into consistent structure
     */
    public function parseHttpResponse(Response $response, string $apiKey): array
    {
        $responseObject = $response->object();
        $result = [
            'status' => false,
            'error' => $apiKey . ' ' . self::API_FAILED,
            'message' => 'There is an Exception on AWNI API call.',
            'data' => null,
            'completed_step' => null,
        ];

        if ($response->successful()) {
            if ($this->hasApiErrors($responseObject)) {
                $result = [
                    'status' => false,
                    'error' => $responseObject?->errorList ?? $apiKey . ' ' . self::API_FAILED,
                    'message' => $this->extractErrorMessage($responseObject, $apiKey),
                    'data' => $responseObject == null ? null : '',
                    'completed_step' => null,
                ];
            } else {
                $result = [
                    'status' => true,
                    'message' => 'API call successfully executed.',
                    'error' => null,
                    'data' => $responseObject,
                    'completed_step' => null,
                ];
            }
        } elseif ($response->status() === JsonResponse::HTTP_NOT_FOUND) {
            $result = [
                'status' => false,
                'error' => '404 Not Found',
                'message' => '404 Not Found',
                'data' => null,
                'completed_step' => null,
            ];
        }

        return $result;
    }

    private function hasApiErrors($responseObject): bool
    {
        return $responseObject == null
            || isset($responseObject->errorList)
            || (isset($responseObject->isSuccess) && strtoupper((string) $responseObject->isSuccess) === 'N');
    }

    private function extractErrorMessage($responseObject, string $apiKey)
    {
        if (isset($responseObject?->errorList)) {
            return json_encode($responseObject->errorList);
        }

        if (isset($responseObject?->message)) {
            return $responseObject->message;
        }

        return $responseObject ?? $apiKey . ' ' . self::API_FAILED;
    }
}

