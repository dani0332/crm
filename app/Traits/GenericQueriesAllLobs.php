<?php

namespace App\Traits;

use App\Enums\DiscountTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentStatusEnum;
use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\ProductionProcessTooltipEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Enums\TransactionPaymentStatusEnum;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Services\CapiRequestService;
use App\Services\CustomerService;
use App\Services\QuoteDocumentService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

trait GenericQueriesAllLobs
{
    public function getQuoteCode($quoteType, $id)
    {
        $nameSpace = '\\App\\Models\\';
        $modelType = (in_array(ucwords($quoteType), newUi()) && checkPersonalQuotes(ucwords($quoteType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType).'Quote';

        if (! class_exists($modelType)) {
            return false;
        }

        $result = $modelType::whereId($id)->value('code');
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function getModelObject($quoteType)
    {
        $nameSpace = '\\App\\Models\\';
        $model = (in_array(ucwords($quoteType), newUi()) && checkPersonalQuotes(ucwords($quoteType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType).'Quote';

        if (! class_exists($model)) {
            return false;
        }

        return $model;
    }

    /**
     * get quote object by quote type.
     *
     * @param  $quoteType  e.g car, health etc
     * @param  $id  can be id or uuid
     * @return false|mixed
     */
    public function getQuoteObject($quoteType, $id)
    {
        $nameSpace = '\\App\\Models\\';

        $model = (in_array(ucwords($quoteType), newUi()) && checkPersonalQuotes(ucwords($quoteType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType).'Quote';

        if (! class_exists($model)) {
            return false;
        }

        $quote = (is_numeric($id)) ? $model::find($id) : $model::where('uuid', $id)->first();

        return (isset($quote->id)) ? $quote : false;
    }

    /**
     * @return false|mixed
     */
    public function getQuoteObjectBy($quoteType, $id, $column = 'id')
    {
        $nameSpace = '\\App\\Models\\';

        $model = (in_array(ucwords($quoteType), newUi()) && checkPersonalQuotes(ucwords($quoteType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType).'Quote';

        if (! class_exists($model)) {
            return false;
        }

        $quote = $model::where($column, $id)->first();

        return (isset($quote->id)) ? $quote : false;
    }

    /**
     * get Quote Request Member Detail by Quote Type e.g health, travel etc
     *
     * @return false|mixed
     */
    public function getMemberDetailObject($quoteType, $id)
    {
        $nameSpace = '\\App\\Models\\';
        $model = $nameSpace.ucwords($quoteType).'QuoteMemberDetail';

        if (! class_exists($model)) {
            return false;
        }

        return $model::find($id);
    }

    public function getRepositoryObject($quoteType)
    {
        $repository = '\\App\\Repositories\\'.ucwords($quoteType).'QuoteRepository';

        if (! class_exists($repository)) {
            return false;
        }

        return $repository;
    }

    public function createDuplicateRecord($lob, $parentRecord)
    {
        if (! ($lob) || ! isset($parentRecord->enquiryType) || ! isset($parentRecord->id)) {
            return false;
        }
        $nameSpace = '\\App\\Models\\';
        $model = $nameSpace.ucwords($lob).'Quote';
        if (! class_exists($model)) {
            return false;
        }
        $dataArr = [
            'firstName' => $parentRecord->first_name,
            'lastName' => $parentRecord->last_name,
            'email' => $parentRecord->email,
            'mobileNo' => $parentRecord->mobile_no,
            'referenceUrl' => config('constants.APP_URL'),
            'source' => config('constants.SOURCE_NAME'),
        ];
        if (strtolower($lob) == strtolower(quoteTypeCode::GroupMedical)) {
            $dataArr['business_type_of_insurance_id'] = 5;
        }
        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-'.strtolower($lob).'-quote', $dataArr);
        if (isset($response->message) && str_contains($response->message, 'Error')) {
            return false;
        } elseif (isset($parentRecord->enquiryType) && $parentRecord->enquiryType == GenericRequestEnum::RECORD_PURPOSE) {
            $record = $model::where('uuid', $response->quoteUID)->first();
            if ($record) {
                $record->parent_duplicate_quote_id = $parentRecord->code;
                $record->advisor_id = auth()->user()->id;
                if (strtolower($lob) == strtolower(quoteTypeCode::Health)) {
                    $subTeam = null;
                    if (auth()->user()->subTeam) {
                        $subTeam = auth()->user()->subTeam->name;
                    }
                    $record->health_team_type = $subTeam;
                }
                $record->save();
            }
        }
    }

    public function getCustomer($customerData)
    {
        $customer = CustomerService::getCustomerByEmail($customerData['email']);

        //create new customer if not exists
        if (! isset($customer->id)) {
            $customer = Customer::create(Arr::only($customerData, ['first_name', 'last_name', 'email', 'mobile_no']));

            // create additional emails
            if (isset($customerData['additional_emails']) && count($customerData['additional_emails'])) {
                foreach ($customerData['additional_emails'] as $additionalEmail) {
                    $customer->additionalContactInfo()->create(['key' => 'email', 'value' => $additionalEmail]);
                }
            }

            // create additional mobile nos
            if (isset($customerData['additional_mobiles']) && count($customerData['additional_mobiles'])) {
                foreach ($customerData['additional_mobiles'] as $additionalMobile) {
                    $customer->additionalContactInfo()->create(['key' => 'mobile_no', 'value' => $additionalMobile]);
                }
            }
        }

        return $customer;
    }

    public function inslyInsurances()
    {
        return [
            QuoteTypes::BIKE->value => ['Bike insurance'],
            QuoteTypes::BUSINESS->value => [
                'business interruption insurance', 'contractors all risks', 'Cyber liability', 'directors and officers liability insurance',
                'Engineering and plant insurance', 'fidelity guarantee', 'group life', 'group medical insurance', 'holiday homes',
                'livestock insurance', 'machinery breakdown insurance', 'marine cargo (individual shipment) insurance',
                'marine hull insurance', 'medical malpractice insurance', 'money insurance', 'motor fleet',
                'open cover - marine cargo insurance', 'professional indemnity insurance,property insurance',
                'public liability insurance', 'road transit (international)', 'road transit (UAE only)',
                'sme packaged insurance', 'trade credit insurance', 'workmens compensation insurance',
            ],
            QuoteTypes::CAR->value => ['casco', 'motor insurance - Comprehensive', 'motor insurance - TPL'],
            QuoteTypes::LIFE->value => ['Critical illness', 'Individual life insurance'],
            QuoteTypes::HOME->value => ['Home insurance', 'personal accident', 'home insurance'],
            QuoteTypes::TRAVEL->value => ['Inbound travel insurance', 'Outbound travel insurance'],
            QuoteTypes::HEALTH->value => ['Individual or family medical'],
            QuoteTypes::CYCLE->value => ['Pedal cycle insurance'],
            QuoteTypes::PET->value => ['Pet insurance'],
            QuoteTypes::YACHT->value => ['Yacht insurance'],
        ];
    }

    /**
     * add comments & improvements needed
     *
     * @return array
     */
    public function bookPolicyPayload($record, $quoteType, $payments, $quoteDocuments)
    {
        $infoMessage = 'QC '.$record->code.' ';
        $insuranceProviderLeadCount = $insuranceProviderCode = $sendUpdateInvoiceDescription = $sendUpdateBrokerInvoice = '';
        $payment = $payments->whereNull('send_update_log_id')->first();
        if ($payment) {
            $insurance_provider_id = $payment->insurance_provider_id;
            $insuranceProviderCode = InsuranceProviderRepository::where('id', $insurance_provider_id)->value('code');
            $insuranceProviderLeadCount = Payment::where('insurance_provider_id', '=', $insurance_provider_id)->count();
        }
        if ($payments->first()?->send_update_log_id) {
            $sendUpdateInvoiceDescription = $payments->first()->invoice_description;
            $sendUpdateBrokerInvoice = $payments->first()->broker_invoice_number;
        }

        $invoiceDescription = empty($sendUpdateInvoiceDescription) ? $insuranceProviderCode.'-'.ucfirst($quoteType).'-'.$record->policy_number : $sendUpdateInvoiceDescription;
        $bookPolicyDetails = [];
        $bookPolicyDetails['lineOfBusiness'] = ucfirst($quoteType);
        $bookPolicyDetails['brokerInvoiceNo'] = empty($sendUpdateBrokerInvoice) ? $insuranceProviderCode.$insuranceProviderLeadCount : $sendUpdateBrokerInvoice;
        $bookPolicyDetails['invoiceDescription'] = substr($invoiceDescription, 0, 60);
        $bookPolicyDetails['bookButton'] = false;
        $bookPolicyDetails['sendButton'] = false;
        $bookPolicyDetails['editButton'] = false;
        $bookPolicyDetails['sendPolicyType'] = null;
        $bookPolicyDetails['text'] = 'Send and Book Policy';
        @[$transactionPaymentStatus, $paymentStatusTooltip] = $this->transactionPaymentStatus($payment, $record);
        $bookPolicyDetails['transactionPaymentStatus'] = $transactionPaymentStatus;
        $bookPolicyDetails['paymentStatusTooltip'] = $paymentStatusTooltip;
        $bookPolicyDetails['isLackingOfPayment'] = $this->isLackingPayment($payment);
        @[$isInsufficientPayment, $paymentStatusHeading, $paymentStatusDescription] = $this->checkForInsufficientPayment($payment);
        $bookPolicyDetails['isInsufficientPayment'] = $isInsufficientPayment;
        $bookPolicyDetails['paymentStatusHeading'] = $paymentStatusHeading;
        $bookPolicyDetails['paymentStatusDescription'] = $paymentStatusDescription;
        $isFilledPolicyDetails = $this->isFilledPolicyDetails($quoteType, $record);
        $infoMessage .= 'QSI: '.$record->quote_status_id.' IPDF: '.$isFilledPolicyDetails;
        $bookPolicyDetails['policyCancelled'] = false;
        $bookPolicyDetails['isPolicyCancelledOrPending'] = $this->isPolicyCancelledOrPending($record);
        $bookPolicyDetails['isPolicyCancelledOrPendingToolTtip'] = ProductionProcessTooltipEnum::POLICY_DETAILS_LOCKED_TOOL_TIP;

        // check if policy details are filled & all required documents are uploaded then show send policy button to customer & show edit button &  send policy to sage
        if ($isFilledPolicyDetails) {
            if (! empty($quoteDocuments)) {
                $isAllRequiredDocumentUploaded = $this->isAllRequiredDocumentAreUploaded($quoteDocuments, $quoteType, $record);
                $infoMessage .= ' ARDF: '.$isAllRequiredDocumentUploaded;
                if ($isAllRequiredDocumentUploaded) {
                    $bookPolicyDetails['sendButton'] = true;
                    $bookPolicyDetails['text'] = SendPolicyTypeEnum::CUSTOMER_BUTTON_TEXT;
                    $bookPolicyDetails['sendPolicyType'] = SendPolicyTypeEnum::CUSTOMER;
                }
                if ($bookPolicyDetails['sendButton']) {
                    $taxDocuments = DocumentTypeRepository::taxDocumentsCode($quoteType, $record);
                    $taxDocumentsCount = collect($quoteDocuments)->whereIn('document_type_code', $taxDocuments)->groupBy('document_type_code')->count();
                    $infoMessage .= ' TDC: '.count($taxDocuments).' UDC: '.$taxDocumentsCount;
                    if ($taxDocumentsCount == count($taxDocuments)) {
                        $bookPolicyDetails['editButton'] = true;
                        $areBookingDetailsFilled = $this->areBookingDetailsFilled($payment);
                        $infoMessage .= ' BDS '.$areBookingDetailsFilled;

                        if ($areBookingDetailsFilled) {
                            if (! $this->checkMainLead($record, $quoteType) || $record->quote_status_id === QuoteStatusEnum::PolicyCancelledReissued) {
                                $bookPolicyDetails['bookButton'] = true;
                                $bookPolicyDetails['text'] = SendPolicyTypeEnum::SAGE_BUTTON_TEXT;
                                $bookPolicyDetails['sendPolicyType'] = SendPolicyTypeEnum::SAGE;
                            } else {
                                $bookPolicyDetails['policyCancelled'] = true;
                            }
                        }
                    }
                }
            }
        }

        if ($record->quote_status_id == QuoteStatusEnum::PolicySentToCustomer) {
            $bookPolicyDetails['text'] = 'Book Policy';
        }
        Log::info($infoMessage);
        Log::info('Book Policy Details: ', $bookPolicyDetails);

        return $bookPolicyDetails;
    }

    public function getQuoteCodeType($lead)
    {
        $leadCodeArray = explode('-', $lead->code);
        if (count($leadCodeArray) == 0) {
            return false;
        }

        return $leadCodeArray[0];
    }

    public function updateQuoteStatus($type, $id)
    {

        if ($type == 'send-update') {
            return true;
        }
        if (request()->has('quote_type')) {
            $type = request()->quote_type;
        }
        $quote = $this->getQuoteObject($type, $id);
        Log::info('Updating quote_status_id && policy_issuance_status_id for  : '.$quote->uuid);
        if ($quote->quote_status_id != QuoteStatusEnum::PolicySentToCustomer || $quote->policy_issuance_status_id != PolicyIssuanceStatusEnum::PolicyIssued) {
            $isPolicyDetailsFilled = $this->isFilledPolicyDetails($type, $quote);
            Log::info('Is policy details filled for  : '.$quote->uuid.' '.$isPolicyDetailsFilled);
            if ($isPolicyDetailsFilled) {
                $quoteDocuments = (new QuoteDocumentService())->getQuoteDocuments($type, $id);
                $isAllRequiredDocumentAreUploaded = $this->isAllRequiredDocumentAreUploaded($quoteDocuments, $type, $quote);
                Log::info('Is all required documens filled for  : '.$quote->uuid.' '.$isAllRequiredDocumentAreUploaded);
                if ($isAllRequiredDocumentAreUploaded) {
                    $quote->update([
                        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                        'policy_issuance_status_id' => PolicyIssuanceStatusEnum::PolicyIssued,
                        'policy_issuance_status_other' => '',
                    ]);
                }
                Log::info('Update done for quote_status_id && policy_issuance_status_id for  : '.$quote->uuid);
            }
        }
    }

    private function transactionPaymentStatus($payment, $quote)
    {
        if (! $payment) {
            return $this->getUnpaidStatus();
        }

        if ($quote->quote_status_id == QuoteStatusEnum::PolicyBooked && $payment->transaction_payment_status == null) {
            $this->updatePaymentAllocationStatus($quote);
        }

        return $this->getPaymentStatus($payment);
    }

    private function getUnpaidStatus()
    {
        return [
            'status' => TransactionPaymentStatusEnum::UNPAID_TEXT,
            'tooltip' => ProductionProcessTooltipEnum::TRANSACTION_PAYMENT_STATUS_NOT_PAID,
        ];
    }

    private function getPaymentStatus($payment)
    {
        if ($payment->transaction_payment_status == TransactionPaymentStatusEnum::UNPAID_TEXT) {
            $paymentStatus = TransactionPaymentStatusEnum::UNPAID_TEXT;
            $paymentStatusTooltip = ProductionProcessTooltipEnum::TRANSACTION_PAYMENT_STATUS_NOT_PAID;
        } elseif ($payment->transaction_payment_status == TransactionPaymentStatusEnum::FULLY_PAID_TEXT) {
            $paymentStatus = TransactionPaymentStatusEnum::FULLY_PAID_TEXT;
            $paymentStatusTooltip = ProductionProcessTooltipEnum::TRANSACTION_PAYMENT_STATUS_PAID;
        } elseif ($payment->transaction_payment_status == TransactionPaymentStatusEnum::PARTIALLY_PAID_TEXT) {
            $paymentStatus = TransactionPaymentStatusEnum::PARTIALLY_PAID_TEXT;
            $paymentStatusTooltip = ProductionProcessTooltipEnum::TRANSACTION_PAYMENT_STATUS_PARTIALLY_PAID;
        } else {
            return $this->getUnpaidStatus();
        }

        return [$paymentStatus, $paymentStatusTooltip];
    }

    public function updatePriceAndDiscount($quoteModel): bool
    {
        Log::info('Updating price & discount for: '.$quoteModel->uuid);

        $payment = $quoteModel->payments()->mainLeadPayment()->first();
        $priceWithVat = $quoteModel->price_with_vat;

        if ($payment) {

            $difference = $this->handleSmallAmountDifference($payment, $priceWithVat);

            $this->setPaymentStatusAsPerPrice($quoteModel, $payment, $difference);

            // total price is actual price without discount
            $payment->total_price = $quoteModel->price_with_vat;
            $payment->save();
            $this->updateTotalAmount($payment);
            $this->updateChildPaymentStatus($payment);
        }

        return $this->isLackingPayment($payment);
    }

    private function updateTotalAmount($payment)
    {
        if ($payment && $payment->frequency == PaymentFrequency::UPFRONT && $payment->payment_status_id == PaymentStatusEnum::PAID) {
            Log::info('Updating TA for PC: '.$payment->code);
            $captureAmount = $payment->captured_amount;
            $totalPrice = $payment->total_price;
            $discountValue = $payment->discount_value;
            if ($captureAmount < ($totalPrice - $discountValue)) {
                $totalAmount = $captureAmount;
            } else {
                $totalAmount = $totalPrice - $discountValue;
            }
            Log::info('updateTotalAmount totalAmount: '.$totalAmount);
            $payment->total_amount = $totalAmount;
            $payment->save();
        }
    }

    private function isFilledPolicyDetails($type, $quote)
    {
        if (! empty($quote->policy_number) && ! empty($quote->policy_issuance_date) && ! empty($quote->policy_start_date) && ! empty($quote->renewal_expiry_date) && $quote->price_with_vat >= 0) {
            if (in_array(ucfirst($type), [QuoteTypes::CAR->value, QuoteTypes::BIKE->value])) {
                if (! empty($quote->insurer_quote_number)) {
                    return true;
                }
            } else {
                return true;
            }
        }

        return false;
    }

    private function isAllRequiredDocumentAreUploaded($quoteDocuments, $quoteType, $record)
    {
        $documentTypeCodes = DocumentTypeRepository::sendPolicyDocumentCodes($quoteType, $record);
        $quoteDocumentsCount = collect($quoteDocuments)->whereIn('document_type_code', $documentTypeCodes)->groupBy('document_type_code')->count();

        info('isAllRequiredDocumentAreUploaded: '.$record->code.' Total number of document required: '.count($documentTypeCodes).' Upload nber of document: '.$quoteDocumentsCount);
        info('documentTypeCodes: ', $documentTypeCodes);

        return $quoteDocumentsCount == count($documentTypeCodes);
    }

    private function isLackingPayment($payment)
    {
        if ($payment) {
            $paymentTotalPrice = round($payment->total_price, 2);
            $sumOfSplitPayment = round(($payment->paymentSplits()->sum('payment_amount') + $payment->discount_value), 2);
            Log::info('isLackingPayment for payment : '.$payment->code.' paymentTotalPrice '.$paymentTotalPrice.' Split payment count '.$sumOfSplitPayment);

            return ! ($sumOfSplitPayment >= $paymentTotalPrice);
        }

        return true;
    }

    private function checkForInsufficientPayment($paymnet)
    {
        $paymentStatusHeading = '';
        $paymentStatusDescription = '';
        $isInsufficientPayment = false;

        if ($paymnet) {
            $paymentStatusId = $paymnet->payment_status_id;

            $insufficientPaymentStatuses = [
                PaymentStatusEnum::PARTIALLY_PAID,
                PaymentStatusEnum::PENDING,
                PaymentStatusEnum::NEW,
                PaymentStatusEnum::OVERDUE,
                PaymentStatusEnum::CREDIT_APPROVED, // TODO: Check with Faisal and Ahsan about this to be included or not for booking of policy with zero price.
            ];

            $insufficientPaymentStatusesHeading = [
                PaymentStatusEnum::PENDING,
                PaymentStatusEnum::NEW,
                PaymentStatusEnum::OVERDUE,
            ];

            if (in_array($paymnet->payment_status_id, $insufficientPaymentStatuses)) {
                switch ($paymentStatusId) {
                    case PaymentStatusEnum::PARTIALLY_PAID:
                        $paymentStatusHeading = 'Insufficient payment received';
                        break;
                    case PaymentStatusEnum::CREDIT_APPROVED:
                        $paymentStatusHeading = "Pending payment under 'Credit approval'";
                        break;
                    default:
                        if (in_array($paymentStatusId, $insufficientPaymentStatusesHeading)) {
                            $paymentStatusHeading = 'Payment not yet completed';
                        }
                        break;
                }
                $paymentStatusDescription = 'Unpaid policies breach our Code of Conduct and will be escalated to management. Do you still want to continue?';
                $isInsufficientPayment = true;
            }
        }

        return [$isInsufficientPayment, $paymentStatusHeading, $paymentStatusDescription];
    }

    public function setPaymentStatusAsPerPrice($quoteModel, mixed $payment, mixed $difference): void
    {
        $priceWithVat = round($quoteModel->price_with_vat, 2);
        $captureAndDiscount = round(($payment->captured_amount + $payment->discount_value), 2);
        // If status is partially paid & total price is less than price with vat then set status to partially paid
        if ($payment->payment_status_id === PaymentStatusEnum::PAID && $payment->total_price < $quoteModel->price_with_vat && ($difference > 0.99)) {
            $payment->payment_status_id = PaymentStatusEnum::PARTIALLY_PAID;
        } elseif ($priceWithVat <= $captureAndDiscount) {
            $payment->payment_status_id = PaymentStatusEnum::PAID;
        }
    }

    /**
     * @return float|mixed
     */
    public function handleSmallAmountDifference(mixed $payment, mixed $priceWithVat): mixed
    {
        $infoMessage = '';
        $capturedAmount = $payment->captured_amount;
        $discountValue = $payment->discount_value;

        $totalPaymentAmount = $capturedAmount + $discountValue;
        $initialDifference = $priceWithVat - $totalPaymentAmount;

        $difference = (float) number_format($initialDifference, 2);

        $infoMessage = 'CA: '.$capturedAmount.' DV: '.$discountValue.' TA: '.$totalPaymentAmount.' ';
        $infoMessage .= 'ID: '.$difference.' ';
        if ($payment->system_adjusted_discount != null) {
            $difference += $payment->system_adjusted_discount;
            $infoMessage .= 'SAD: '.$payment->system_adjusted_discount.' DASA '.$difference;
        }
        // Case 1 if difference is less than 1 and greater than 0 else set total price to price with vat
        if ($difference <= 0.99 && $difference > 0) {
            $payment->system_adjusted_discount = $difference;
            // If condition to check if discount value is not null & add difference to it else set difference as discount value
            if ($payment->discount_value != null) {
                $payment->discount_value += $initialDifference;
            } else {
                $payment->discount_value = $difference;
                $payment->discount_type = DiscountTypeEnum::SYSTEM_ADJUSTED_DISCOUNT;
            }
        } // Case 2 if difference is greater than 0.99 and system adjusted discount is greater than 0 then subtract system adjusted discount from discount value
        elseif (($difference > 0.99 || $difference == 0) && $payment->system_adjusted_discount > 0) {
            $payment->discount_value -= $payment->system_adjusted_discount;
            $payment->system_adjusted_discount = 0;
            if ($payment->discount_type == DiscountTypeEnum::SYSTEM_ADJUSTED_DISCOUNT) {
                $payment->discount_type = null;
            }
        }

        Log::info($infoMessage);

        return $difference;
    }

    private function areBookingDetailsFilled($payment)
    {

        if (! $payment) {
            return false;
        }

        return ! empty($payment->insurer_invoice_date)
            && ! empty($payment->insurer_tax_number)
            && ! empty($payment->insurer_commmission_invoice_number)
            && (! empty($payment->commission_vat_not_applicable) || ! empty($payment->commission_vat_applicable));
    }

    private function updatePaymentAllocationStatus($quote)
    {

        $payment = Payment::where('code', '=', $quote->code)->mainLeadPayment()->first();

        if ($payment) {
            $capturedAmount = $payment->captured_amount;
            $totalAmount = $payment->captured_amount + $payment->discount_value;
            $priceWithVat = $quote->price_with_vat;

            $totalAmount = round($totalAmount, 2);
            $priceWithVat = round($priceWithVat, 2);

            if ($capturedAmount == 0) {
                $paymentStatus = TransactionPaymentStatusEnum::UNPAID_TEXT;
            } elseif ($totalAmount >= $priceWithVat) {
                $paymentStatus = TransactionPaymentStatusEnum::FULLY_PAID_TEXT;
            } else {
                $paymentStatus = TransactionPaymentStatusEnum::PARTIALLY_PAID_TEXT;
            }

            $payment->transaction_payment_status = $paymentStatus;
            $payment->save();
        }
    }

    private function checkMainLead($quote, $quoteType)
    {
        if ($quote->parent_duplicate_quote_id == null) {
            return false;
        }

        $parentQuoteCode = count(explode('-', $quote->code)) > 2 ? $quote->parent_duplicate_quote_id : false;
        if ($parentQuoteCode) {
            $parentQuote = $this->getQuoteObjectBy($quoteType, $parentQuoteCode, 'code');

            return $parentQuote && $parentQuote->quote_status_id === QuoteStatusEnum::CancellationPending;
        }

        return false;
    }

    private function updateChildPaymentStatus($payment)
    {
        Log::info('Updating child payment status for: '.$payment->code);
        $paymentSplits = PaymentSplits::where('code', $payment->code)->get();
        if (! $paymentSplits->isEmpty()) {
            foreach ($paymentSplits as $paymentSplit) {
                if ($payment->frequency == PaymentFrequency::UPFRONT && $payment->payment_status_id == PaymentStatusEnum::PAID) {
                    Log::info('Updating PA for PC: '.$payment->code.' BTA: '.$paymentSplit->payment_amount.' WTA: '.$payment->total_amount);
                    $paymentSplit->payment_amount = $payment->total_amount;
                }
                if (! ($paymentSplit->collection_amount == null || $paymentSplit->collection_amount == 0)) {
                    if ($paymentSplit->collection_amount >= $paymentSplit->payment_amount) {
                        $paymentSplit->payment_status_id = PaymentStatusEnum::PAID;
                    } else {
                        $paymentSplit->payment_status_id = PaymentStatusEnum::PARTIALLY_PAID;
                    }
                    $paymentSplit->save();
                }
            }
        }
    }

    private function isPolicyCancelledOrPending($quote)
    {
        $quote_status_id = $quote->quote_status_id;

        return in_array($quote_status_id, [QuoteStatusEnum::PolicyCancelled, QuoteStatusEnum::CancellationPending, QuoteStatusEnum::PolicyCancelledReissued]);
    }
}
