<?php

namespace App\Services\PolicyIssuanceAutomation\Travel;

use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\TravelQuoteEnum;
use App\Interfaces\PolicyIssuanceInterface;
use App\Models\Payment;
use App\Services\SplitPaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class AllianceInsuranceService implements PolicyIssuanceInterface
{
    private mixed $baseUrl;
    private mixed $agencyId;
    private mixed $agencyCode;
    protected string $ISSUE_POLICY = 'Issue Policy';
    protected string $PURCHASE_POLICY = 'Purchase Policy';
    protected string $UPLOAD_POLICY_DOCUMENTS = 'Upload Policy Documents';

    protected string $FILL_POLICY_BOOKING_DETAILS = 'Fill Policy Booking Details';
    protected string $BOOK_POLICY = 'Book Policy';

    public function __construct()
    {
        $this->baseUrl = config('constants.ALLIANCE_API_BASE_URL');
        $this->agencyId = config('constants.ALLIANCE_AGENCY_ID');
        $this->agencyCode = config('constants.ALLIANCE_AGENCY_CODE');
    }
    public function handle($process)
    {
        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' PID : '.$process->id.' started');

        $response = ['status' => false, 'error' => null, 'message' => null];

        $quote = $process->model;
        $selectedPlan = $quote->travelQuotePlanDetails()->where('plan_id', $quote->plan_id)->first();

        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' PID : '.$process->id.' - TravelQuotePlanDetails ID : '.$selectedPlan->id);

        $isDuplicateOrCIRLead = ! empty($quote->parent_duplicate_quote_id);
        $payment = Payment::where('code', $quote->code)->mainLeadPayment()->with('paymentSplits')->first();

        if ($isDuplicateOrCIRLead && empty($payment)) {
            $payment = Payment::where(['paymentable_id' => $quote->id, 'paymentable_type' => $quote->getMorphClass()])->mainLeadPayment()->with('paymentSplits')->first();
        }

        $travelType = TravelQuoteEnum::ALLIANCE_IN_BOUND;
        $directionCode = $quote->direction_code;
        if ($directionCode === TravelQuoteEnum::TRAVEL_UAE_OUTBOUND) {
            $travelType = TravelQuoteEnum::ALLIANCE_OUT_BOUND;
        }

        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' PID : '.$process->id.' - Travel Type : '.$travelType);

        $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' PID : '.$process->id.' - Next Step To Be Executed : '.$nextStepToBeExecuted);

        if ($nextStepToBeExecuted === $this->ISSUE_POLICY) {
            $policyIssuanceResponse = $this->issuePolicyAndFillPolicyDetails($quote, $selectedPlan, $travelType);
            if (! $policyIssuanceResponse['status']) {
                return $policyIssuanceResponse;
            }
            $process->update(['completed_step' => $policyIssuanceResponse['completed_step']]);
        }

        $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        if ($nextStepToBeExecuted === $this->PURCHASE_POLICY) {
            $policyPurchaseResponse = $this->policyPurchase($quote, $travelType);
            if (! $policyPurchaseResponse['status']) {
                return $policyPurchaseResponse;
            }
            $process->update(['completed_step' => $policyPurchaseResponse['completed_step']]);
        }

        $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        if ($nextStepToBeExecuted === $this->UPLOAD_POLICY_DOCUMENTS) {
            $uploadPolicyDocumentResponse = $this->fetchAndUploadDocument($quote);
            if (! $uploadPolicyDocumentResponse['status']) {
                return $uploadPolicyDocumentResponse;
            }
        }

        $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        if ($nextStepToBeExecuted === $this->FILL_POLICY_BOOKING_DETAILS) {
            $fillPolicyDetailsResponse = $this->fillBookingDetails($quote, $payment);
            if (! $fillPolicyDetailsResponse['status']) {
                return $fillPolicyDetailsResponse;
            }
        }

        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' PID : '.$process->id.' ended');
        $response['status'] = true;
        $response['message'] = 'Sage Booking of policy triggered successfully';

        return $response;
    }

    public function issuePolicyAndFillPolicyDetails($quote, $selectedPlan, $travelType): array
    {
        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $response = ['status' => false, 'completed_step' => $this->ISSUE_POLICY, 'error' => null, 'message' => null];

        $title = 'Mr';
        $dateOfBirth = $quote->dob ? Carbon::parse($quote->dob)->format('Y-m-d') : null;
        if (in_array($quote->gender, [GenericRequestEnum::FEMALE, strtolower(GenericRequestEnum::FEMALE), GenericRequestEnum::FEMALE_SHORT_VALUE])) {
            $title = 'Mrs';
        }
        $endpoint = $this->baseUrl.'/v1/quote/'.$travelType.'/finalise';
        $payLoad = [
            'agency_id' => $this->agencyId,
            'agency_code' => $this->agencyCode,
            'quote_id' => $selectedPlan->insurer_quote_id,
            'scheme_id' => $selectedPlan->alliance_scheme_id ?? 55,
            'title_customer' => $title,
            'first_name_customer' => $quote->first_name,
            'last_name_customer' => $quote->last_name,
            'title_traveller' => [$title],
            'first_name_traveller' => [$quote->first_name],
            'last_name_traveller' => [$quote->last_name],
            'dob' => [$dateOfBirth],
            'passport_number' => ['1122334455'],
            'nationality_traveller' => [12],
            'email' => $quote->email,
            'mobile' => $quote->mobile_no,
            'agency_reference' => 'asc',
        ];
        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PayLoad : '.json_encode($payLoad));

        $issuePolicy = Http::post($endpoint, $payLoad);
        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Response : '.$issuePolicy);

        $issuePolicyResponse = $issuePolicy->object();
        if ($issuePolicyResponse?->status === 'failed') {
            $response['error'] = $issuePolicyResponse?->errors;

            return $response;
        }
        $issuePolicyResult = $issuePolicyResponse?->result;
        $insurerPolicyId = $issuePolicyResult->policy_id;
        $premium = $issuePolicyResult->premium;
        $priceVatApplicable = $premium / 1.05;
        $policyIssuanceDate = Carbon::now();
        $policyExpiryDate = Carbon::parse($quote->policy_start_date)->addDays($quote->days_cover_for);

        $quote->update([
            'insurer_policy_id' => $insurerPolicyId,
            'price_without_vat' => $priceVatApplicable,
            'vat' => $premium - $priceVatApplicable,
            'price_vat_applicable' => $priceVatApplicable,
            'policy_issuance_date' => $policyIssuanceDate,
            'policy_expiry_date' => $policyExpiryDate,
        ]);
        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy Issued Api called successfully and Quote is updated');

        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote ID : '.$quote->code.' ended');

        $response['status'] = true;
        $response['message'] = 'Policy Issued Api called successfully and Quote is updated.';

        return $response;
    }

    public function policyPurchase($quote, $travelType): array
    {
        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $response = ['status' => false, 'completed_step' => $this->PURCHASE_POLICY, 'error' => null, 'message' => null];
        $endpoint = $this->baseUrl.'/v1/quote/'.$travelType.'/purchase';

        $payLoad = [
            'agency_id' => $this->agencyId,
            'agency_code' => $this->agencyCode,
            'policy_id' => $quote->insurer_policy_id,
        ];
        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PayLoad : '.json_encode($payLoad));

        $policyPurchase = Http::post($endpoint, $payLoad);

        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Response : '.$policyPurchase);

        $policyPurchaseResponse = $policyPurchase->object();
        if ($policyPurchaseResponse?->status === 'failed') {
            $response['error'] = $policyPurchaseResponse?->errors;

            return $response;
        }
        $policyPurchaseResult = $policyPurchaseResponse?->result;

        $insurerPolicyNumber = $policyPurchaseResult->policy_number;

        $quote->update([
            'policy_number' => $insurerPolicyNumber,
            'quote_status_id' => QuoteStatusEnum::PolicyIssued,
            'quote_status_date' => now(),
        ]);
        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy Purchase Api called successfully and Quote is updated');

        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        $response['status'] = true;
        $response['message'] = 'Policy Purchase Api called successfully and Quote is updated.';

        return $response;
    }

    public function fetchAndUploadDocument($quote)
    {
        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $payLoad = [
            'agency_id' => $this->agencyId,
            'agency_code' => $this->agencyCode,
            'policy_id' => $quote->insurer_policy_id,
        ];
        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PayLoad : '.json_encode($payLoad));

        $endpoint = $this->baseUrl.'/v1/policy/inbound/documents';
        $policyDocuments = Http::post($endpoint, $payLoad);

        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Response : '.$policyDocuments);

        $policyDocumentsResponse = $policyDocuments->object();
        if ($policyDocumentsResponse?->status === 'failed') {
            $response['error'] = $policyDocumentsResponse?->errors;

            return $response;
        }

        $policyDocumentsResult = $policyDocumentsResponse?->result;

        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy documents are uploaded');

        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        $response['status'] = true;
        $response['message'] = 'Policy documents are uploaded';

        return $response;

    }


    public function fillBookingDetails($quote, $payment)
    {
        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $payLoad = [
            'agency_id' => $this->agencyId,
            'agency_code' => $this->agencyCode,
            'policy_id' => $quote->insurer_policy_id,
            'policy_number' => $quote->policy_number,
        ];
        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PayLoad : '.json_encode($payLoad));

        $endpoint = $this->baseUrl.'/v1/agency/buyer-tax-invoices';
        $buyerTaxInvoice = Http::post($endpoint, $payLoad);

        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Response : '.$buyerTaxInvoice);

        $buyerTaxInvoiceResponse = $buyerTaxInvoice->object();
        if ($buyerTaxInvoiceResponse?->status === 'failed') {
            $response['error'] = $buyerTaxInvoiceResponse?->errors;

            return $response;
        }

        $buyerTaxInvoiceResult = $buyerTaxInvoiceResponse?->result;
        $bookingDetails = $buyerTaxInvoiceResult?->buyer_tax_invoices[0];

        $insurerInvoiceDate = Carbon::createFromFormat('d-M-y', $bookingDetails->tax_invoice_date)->format('Y-m-d');

        $payment->update([
            'commission' => $bookingDetails->agency_commission_inc_tax,
            'commission_vat' => $bookingDetails->agency_commission_tax,
            'commission_vat_applicable' => $bookingDetails->agency_commission,
            'insurer_commmission_invoice_number' => $bookingDetails->tax_invoice_number,
            'insurer_invoice_date' => $insurerInvoiceDate,
        ]);

        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy Details filled and Buyer Tax Invoice is uploaded');

        (new SplitPaymentService)->updateCommissionSchedule($payment);
        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Commission Schedule updated');


        info('automation:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        $response['status'] = true;
        $response['message'] = 'Policy Details filled and Buyer Tax Invoice is uploaded';

        return $response;
    }

    public function triggerBookPolicyProcess($process)
    {
        $data = [
            'policy_number' => '123456',
            'policy_url' => 'https://www.allianceinsurance.com/policy/123456',
        ];

        return $data;
    }


    public function getNextStep($completedStep = null)
    {
        return match ($completedStep) {
            $this->ISSUE_POLICY => $this->PURCHASE_POLICY,
            $this->PURCHASE_POLICY => $this->UPLOAD_POLICY_DOCUMENTS,
            $this->UPLOAD_POLICY_DOCUMENTS => $this->FILL_POLICY_BOOKING_DETAILS,
            $this->FILL_POLICY_BOOKING_DETAILS => $this->BOOK_POLICY,
            default => $this->ISSUE_POLICY,
        };
    }

}
