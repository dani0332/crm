<?php

namespace App\Traits;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\DatabaseColumnsString;
use App\Enums\EmirateEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentGatewayIdEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\ProductionProcessTooltipEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Enums\TransactionPaymentStatusEnum;
use App\Models\ApplicationStorage;
use App\Models\Customer;
use App\Models\InsuranceProvider;
use App\Models\Nationality;
use App\Models\Payment;
use App\Models\PersonalQuoteDetail;
use App\Models\SendUpdateLog;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\PaymentRepository;
use App\Services\BrokerCommissionService;
use App\Services\CapiRequestService;
use App\Services\CentralService;
use App\Services\CustomerService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use App\Services\Reports\RenewalBatchReportService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait GenericQueriesAllLobs
{
    public function getQuoteCode($quoteType, $id)
    {
        $nameSpace = '\\App\\Models\\';
        $modelType = (checkPersonalQuotes(ucwords($quoteType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType).'Quote';

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
        $model = (checkPersonalQuotes(ucwords($quoteType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType).'Quote';
        if (! class_exists($model)) {
            if (in_array(ucwords($quoteType), [quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE])) {
                $model = $nameSpace.'BusinessQuote';
                if (class_exists($model)) {
                    return $model;
                }
            }

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

        $model = (checkPersonalQuotes(ucwords($quoteType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType).'Quote';

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

        $model = (checkPersonalQuotes(ucwords($quoteType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType).'Quote';

        if (! class_exists($model)) {
            return false;
        }

        $quote = $model::where($column, $id)->first();

        return (isset($quote->id)) ? $quote : false;
    }

    /**
     * @return false|mixed
     *                     TODO :
     */
    public function getSelectedQuoteObjectBy($quoteType, $id, $column = 'id')
    {
        $nameSpace = '\\App\\Models\\';

        $model = (checkPersonalQuotes(ucwords($quoteType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType).'Quote';

        if (! class_exists($model)) {
            return false;
        }

        $quote = $model::select(['id', 'uuid', 'code'])->where($column, $id)->first();

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
                $record->assignment_type = AssignmentTypeEnum::SELF_ASSIGNED;
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

        // create new customer if not exists
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
                'open cover - marine cargo insurance', 'professional indemnity insurance', 'property insurance',
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
            QuoteTypes::SAVINGS->value => ['Savings'],
        ];
    }

    /**
     * add comments & improvements needed
     * This method called when we visit all LOB's details page
     * Based on this method we decide to show buttons and  checking lacking payment and other stuff
     *
     * @return array
     */
    public function bookPolicyPayload($record, $quoteType, &$payments, $quoteDocuments)
    {
        info('Quote Code: '.$record->code.' fn: bookPolicyPayload called');
        $brokerInvoiceNo = $invoiceDescription = '';
        // Retrieve the first payment belongs to lead not to send update
        $payment = $payments->whereNull('send_update_log_id')->first();
        if ($payment) {
            $invoiceDescription = (new PaymentRepository)->generateInvoiceDescription($payment, $quoteType, $record);
            $brokerInvoiceNo = $payment->broker_invoice_number;
        }

        $isHealthAUHLead = $this->isHealthAUHLead($quoteType, $record);
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Quote code: '.$record->code.' - Is Health AUH Lead Check ', extra : [
            'isHealthAUHLead' => $isHealthAUHLead,
        ]);

        $bookPolicyDetails = [];
        $bookPolicyDetails['lineOfBusiness'] = ucfirst($quoteType);
        $bookPolicyDetails['brokerInvoiceNo'] = $brokerInvoiceNo;
        $bookPolicyDetails['invoiceDescription'] = $invoiceDescription;
        $bookPolicyDetails['bookButton'] = false;
        $bookPolicyDetails['sendButton'] = false;
        $bookPolicyDetails['editButton'] = false;
        $bookPolicyDetails['sendPolicyType'] = null;
        $bookPolicyDetails['text'] = 'Send and Book Policy';
        @[$transactionPaymentStatus, $paymentStatusTooltip] = $this->transactionPaymentStatus($payment, $record, $isHealthAUHLead);
        $bookPolicyDetails['transactionPaymentStatus'] = $transactionPaymentStatus;
        $bookPolicyDetails['paymentStatusTooltip'] = $paymentStatusTooltip;
        $bookPolicyDetails['isLackingOfPayment'] = $this->isLackingPayment($payment);
        @[$isInsufficientPayment, $paymentStatusHeading, $paymentStatusDescription] = $this->checkForInsufficientPayment($payment);
        $bookPolicyDetails['isInsufficientPayment'] = $isInsufficientPayment;
        $bookPolicyDetails['paymentStatusHeading'] = $paymentStatusHeading;
        $bookPolicyDetails['paymentStatusDescription'] = $paymentStatusDescription;
        $isFilledPolicyDetails = $this->isFilledPolicyDetails($quoteType, $record);
        $bookPolicyDetails['policyCancelled'] = false;
        $bookPolicyDetails['isPolicyCancelledOrPending'] = $this->isPolicyCancelledOrPending($record);
        $bookPolicyDetails['isPolicyCancelledOrPendingToolTtip'] = ProductionProcessTooltipEnum::POLICY_DETAILS_LOCKED_TOOL_TIP;
        $bookPolicyDetails['isEnableUploadDocument'] = app(QuoteDocumentService::class)->isEnableUploadDocument($record->quote_status_id);
        $bookPolicyDetails['isPaidEditable'] = $this->isSplitPaymentFullyPaid($payment);
        if ($bookPolicyDetails['lineOfBusiness'] == quoteTypeCode::Travel) {
            $payments = $payments->map(function ($payment) use ($quoteType, $record) {
                $tapPaymentConfiguration = app(CentralService::class)->getTapConfiguration($quoteType, $record, $payment, true);
                $payment->isCreditCardEnabled = $tapPaymentConfiguration['isCreditCardEnabled'];
                $payment->isGIGProvider = $tapPaymentConfiguration['isGIGProvider'];
                $payment->isMultiplePaymentsEnabled = $tapPaymentConfiguration['isMultiplePaymentsEnabled'];
                $payment->isCaptureButtonEnabled = $tapPaymentConfiguration['isCaptureButtonEnabled'];

                return $payment;
            });
        }
        $tapPaymentConfiguration = app(CentralService::class)->getTapConfiguration($quoteType, $record, $payment, true);
        $bookPolicyDetails = array_merge($bookPolicyDetails, $tapPaymentConfiguration);
        // check if policy details are filled & all required documents are uploaded then show send policy button to customer & show edit button &  send policy to sage
        if ($isFilledPolicyDetails) {
            $areSendPolicyDocsUploaded = app(DocumentTypeRepository::class)->fetchAreSendPolicyDocsUploaded($quoteDocuments, $quoteType, $record);
            $bookPolicyDetails['disabled'] = $areSendPolicyDocsUploaded['disabled'];
            $bookPolicyDetails['sendButton'] = true;
            $bookPolicyDetails['requiredDocuments'] = $areSendPolicyDocsUploaded['requiredDocuments'];
            $bookPolicyDetails['text'] = SendPolicyTypeEnum::CUSTOMER_BUTTON_TEXT;
            $bookPolicyDetails['sendPolicyType'] = SendPolicyTypeEnum::CUSTOMER;
            if ($bookPolicyDetails['sendButton']) {
                $taxDocuments = DocumentTypeRepository::taxDocumentsCode($quoteType, $record);
                $taxDocumentsCount = collect($quoteDocuments)->whereIn('document_type_code', $taxDocuments)->groupBy('document_type_code')->count();
                if ($taxDocumentsCount == count($taxDocuments)) {
                    $bookPolicyDetails['editButton'] = true;
                    $areBookingDetailsFilled = $this->areBookingDetailsFilled($payment);

                    if ($areBookingDetailsFilled) {
                        $isMainLead = $this->checkMainLead($record, $quoteType);
                        if (! $isMainLead || $record->quote_status_id === QuoteStatusEnum::PolicyCancelledReissued) {
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

        // If quote status id is policy sent to customer then we set text book policy
        if ($record->quote_status_id == QuoteStatusEnum::PolicySentToCustomer) {
            $bookPolicyDetails['text'] = 'Book Policy';
        }
        // Check if this is an Abu Dhabi health quote lead
        $bookPolicyDetails['isHealthAUHLead'] = $isHealthAUHLead;

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

    public function getQuoteDetailObject($quoteType, $id, $idType = 'quote')
    {
        $nameSpace = '\\App\\Models\\';

        $model = $nameSpace.ucwords($quoteType).'QuoteRequestDetail';
        if (! class_exists($model)) {
            if (! (in_array(ucwords($quoteType), [quoteTypeCode::Cycle, quoteTypeCode::Jetski]))) {
                return false;
            }
        }
        if ($idType == 'quote') {
            if (checkPersonalQuotes(ucwords($quoteType))) {
                $quote = PersonalQuoteDetail::where('personal_quote_id', $id)->first();
            } else {
                $quote = $model::where($quoteType.'_quote_request_id', $id)->first();
            }
        } else {
            $quote = $model::find($id);
        }

        return (isset($quote->id)) ? $quote : false;
    }

    /**
     * Retrieves the transaction payment status and associated tooltip information from payment table
     * Invoking from bookPolicyPayload function.
     *
     * @return array
     */
    private function transactionPaymentStatus($payment, $quote, $isHealthAUHLead = false)
    {
        // If no payment has been created for the lead, return an unpaid payment status along with the relevant tooltip
        if (! $payment) {
            return $this->getUnpaidStatus();
        }

        // List of statuses where the payment allocation status needs updating if payment status is set to null
        $statusesTriggeringUpdate = [
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::CancellationPending,
            QuoteStatusEnum::PolicyCancelled,
            QuoteStatusEnum::PolicyCancelledReissued,
        ];
        $updateRequired = in_array($quote->quote_status_id, $statusesTriggeringUpdate) && is_null($payment->transaction_payment_status);
        if ($updateRequired && ! $isHealthAUHLead) {
            $this->updatePaymentAllocationStatus($quote);
        }

        return $this->getPaymentStatus($payment);
    }

    /**
     * This will return unpaid payment status and tool tip.
     *
     * @return array
     */
    private function getUnpaidStatus()
    {
        return [
            'status' => TransactionPaymentStatusEnum::UNPAID_TEXT,
            'tooltip' => ProductionProcessTooltipEnum::TRANSACTION_PAYMENT_STATUS_NOT_PAID,
        ];
    }

    /**
     * Retrieves the payment status and its corresponding tooltip based on the transaction payment status of a payment.
     *
     * @return array
     */
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

    /**
     * Evaluates if all necessary policy details are filled for a given quote.
     * such as policy number, policy issuance date, policy start date, policy expiry date, and price with VAT are present.
     * Triggering from bookPolicyPayload
     *
     * @return bool
     */
    private function isFilledPolicyDetails($type, $quote)
    {
        $hasBasicPolicyDetails = ! empty($quote->policy_number) &&
                                ! empty($quote->policy_issuance_date) &&
                                ! empty($quote->policy_start_date) &&
                                ! empty($quote->policy_expiry_date) &&
                                $quote->price_with_vat >= 0;

        if (! $hasBasicPolicyDetails) {
            return false;
        }

        $isCarOrBike = in_array(ucfirst($type), [QuoteTypes::CAR->value, QuoteTypes::BIKE->value]);
        $hasInsurerQuoteNumber = ! empty($quote->insurer_quote_number);

        // For CAR or BIKE types, ensure insurer_quote_number is also filled
        if ($isCarOrBike && ! $hasInsurerQuoteNumber) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the given payment is lacking based on its total price and the sum of its split payments.
     * It calculates the total price and the sum of split payments including payment discount value
     * Triggering from updatePriceAndDiscount & bookPolicyPayload
     *
     * @return bool
     */
    private function isLackingPayment($payment)
    {
        LoggerService::info('fn:isLackingPayment - GenericQueriesAllLobs');
        if ($this->isSplitPaymentFullyPaid($payment)) {
            return true;
        }

        if ($payment) {
            $paymentTotalPrice = round($payment->total_price, 2);
            $discountValue = round($payment->discount_value, 2);
            $paymentTotalAmount = round($payment->total_amount, 2);
            $tolerance = 0.01;

            if (($paymentTotalAmount + $discountValue) >= ($paymentTotalPrice - $tolerance) &&
                ($paymentTotalAmount + $discountValue) <= ($paymentTotalPrice + $tolerance)) {
                return false;
            }

            $sumOfSplitPayment = round(($payment->paymentSplits()->sum('payment_amount') + $discountValue), 2);
            info('Quote Code: '.$payment->code.' Checking Lacking Payment paymentTotalPrice '.$paymentTotalPrice.' sum of Split payment '.$sumOfSplitPayment);

            // Check if the sum of split payments is approximately equal to the total price
            return ! (($sumOfSplitPayment >= ($paymentTotalPrice - $tolerance)) &&
                    ($sumOfSplitPayment <= ($paymentTotalPrice + $tolerance)));
        }

        return true;
    }

    /**
     * Checks if the payment is insufficient based on its payment status.
     * This method sets appropriate headings and descriptions based on the specific payment status
     * Triggering from bookPolicyPayload and used in book policy section before sending policy
     *
     * @return array
     */
    public function checkForInsufficientPayment($payment)
    {
        $paymentStatusHeading = '';
        $paymentStatusDescription = '';
        $isInsufficientPayment = false;

        if ($payment) {
            $paymentStatusId = $payment->payment_status_id;

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

            if (in_array($payment->payment_status_id, $insufficientPaymentStatuses)) {
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

    /**
     * Determines if all below mentiooned fields are filled or not
     * Based on this we will show book policy button inj booking details section
     * Triggering from bookPolicyPayload
     *
     * @return bool
     */
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

    /**
     * Updates the transaction payment status of a payment associated with a given quote.
     * This method is triggered during the booking policy process
     *
     * @return null
     */
    private function updatePaymentAllocationStatus($quote)
    {

        $payment = Payment::where('code', '=', $quote->code)->mainLeadPayment()->with('paymentSplits')->first();

        if ($payment) {
            $capturedAmount = $payment->captured_amount;
            $totalAmount = $payment->captured_amount + $payment->discount_value;
            $priceWithVat = $quote->price_with_vat;

            $totalAmount = round($totalAmount, 2);
            $priceWithVat = round($priceWithVat, 2);

            $paymentSplits = $payment->paymentSplits->first();

            if ($paymentSplits && $paymentSplits->sage_reciept_id == null) {
                $paymentStatus = TransactionPaymentStatusEnum::UNPAID_TEXT;
            } else {
                if ($capturedAmount == 0) {
                    $paymentStatus = TransactionPaymentStatusEnum::UNPAID_TEXT;
                } elseif ($totalAmount >= $priceWithVat) {
                    $paymentStatus = TransactionPaymentStatusEnum::FULLY_PAID_TEXT;
                } else {
                    $paymentStatus = TransactionPaymentStatusEnum::PARTIALLY_PAID_TEXT;
                }
            }

            $payment->transaction_payment_status = $paymentStatus;
            $payment->save();
        }
    }

    /**
     * Determines if the provided quote is the main lead or child lead based on parent_duplicate_quote_id column.
     * If child lead and parent lead status is not cancellation pending we weill show only send policy to customer button
     * Triggering from bookPolicyPayload
     *
     * @return bool
     */
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

    /**
     * Determines if a quote's status indicates that the policy is either cancelled, pending cancellation, or cancelled and reissued.
     * Based on this we show tooltip and disbaled button related to send & book policy
     * Triggering from bookPolicyPayload and used in book policy & policy details section
     *
     * @return bool
     */
    private function isPolicyCancelledOrPending($quote)
    {
        $quote_status_id = $quote->quote_status_id;

        return in_array($quote_status_id, [QuoteStatusEnum::PolicyCancelled, QuoteStatusEnum::CancellationPending, QuoteStatusEnum::PolicyCancelledReissued]);
    }

    public function adjustQueryByDateFilters($query, $tablePrefix, $requestParams = [], $useJoin = true)
    {
        $request = $requestParams ? collect($requestParams) : request();
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $defaultDate = now()->endOfDay();
        if (! empty($request->get('payment_due_date')) && ! $useJoin) {
            $startDate = isset($request['payment_due_date']) ? Carbon::parse($request['payment_due_date'][0])->startOfDay() : $defaultDate;
            $endDate = isset($request['payment_due_date']) ? Carbon::parse($request['payment_due_date'][1])->endOfDay() : $defaultDate;

            $query->whereHas('paymentSplits', function ($q) use ($startDate, $endDate, $dateFormat) {
                $q->whereBetween('due_date', [
                    $startDate->format($dateFormat),
                    $endDate->format($dateFormat),
                ]);
            });

            return;
        }
        if (! empty($request->get('payment_due_date'))) {
            $query->join('payment_splits as pays', 'pays.code', '=', $tablePrefix.'.code');
            $columnName = 'pays.due_date';
        } elseif (! empty($request->get('booking_date'))) {
            $columnName = $tablePrefix.'.policy_booking_date';
        } else {
            return;
        }
        $dateType = $request->get('payment_due_date') ? 'payment_due_date' : 'booking_date';
        $startDate = isset($request[$dateType]) ? Carbon::parse($request[$dateType][0])->startOfDay() : $defaultDate;
        $endDate = isset($request[$dateType]) ? Carbon::parse($request[$dateType][1])->endOfDay() : $defaultDate;
        $query->whereBetween($columnName, [$startDate->format($dateFormat), $endDate->format($dateFormat)]);
    }

    public function adjustQueryByInsurerInvoiceFilters($query, $requestParams = [])
    {
        if (is_array($requestParams)) {
            $requestParams = empty($requestParams) ? collect(request()->all()) : collect($requestParams);
        } elseif ($requestParams instanceof Collection) {
            $requestParams = $requestParams->isEmpty() ? collect(request()->all()) : collect($requestParams);
        }

        if (auth()->check()) {
            $user = auth()->user();
        } elseif (! empty($requestParams)) {
            $user = $requestParams['user'] ?? null;
        }

        if ($user->can(PermissionsEnum::SEARCH_INSURER_TAX_INVOICE_NUMBER) && $requestParams->has('insurer_tax_number')) {
            $value = $requestParams->get('insurer_tax_number');
            $query->whereHas('payments', function ($query) use ($value) {
                $query->where(DatabaseColumnsString::INSURER_TAX_INVOICE_NUMBER, $value);
            });
        }

        if ($user->can(PermissionsEnum::SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER) && $requestParams->has('insurer_commmission_invoice_number')) {
            $value = $requestParams->get('insurer_commmission_invoice_number');
            $query->whereHas('payments', function ($query) use ($value) {
                $query->where(DatabaseColumnsString::INSURER_COMMISSION_TAX_INVOICE_NUMBER, $value);
            });
        }
    }

    public function getSendUpdatePaymentCode($sendUpdateLogId): string
    {
        $code = Payment::where('send_update_log_id', $sendUpdateLogId)->pluck('code')->first();
        if ($code) {
            return $code;
        }

        return '';
    }

    public function getSendUpdateDocumentIds($sendUpdateLogId)
    {
        $sendUpdateLog = SendUpdateLog::with(['documents' => function ($query) {
            $query->withTrashed();
        }])->find($sendUpdateLogId);

        $documentIds = $sendUpdateLog->documents->pluck('id')->toArray();

        if (! empty($documentIds)) {
            return $documentIds;
        }

        return null;
    }

    /**
     * Check if the payment is split and all payment splits are paid or not fully paid
     * This method checks if the given payment has a frequency of split payments
     * and verifies if all associated payment splits have a payment status of 'paid'.
     *
     * @return bool
     */
    private function isSplitPaymentFullyPaid($payment)
    {
        LoggerService::info('fn:isSplitPaymentFullyPaid - Start - GenericQueriesAllLobs');

        // Check if the payment exists and has a frequency of split payments
        if ($payment && $payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
            // Get the payment splits associated with the payment
            $paymentSplits = $payment->paymentSplits;

            // Check if the payment splits are not empty and all have a payment status of 'paid'
            if (! $paymentSplits->isEmpty() && $paymentSplits->every(function ($split) {
                return $split->payment_status_id == PaymentStatusEnum::PAID;
            })) {

                $totalPrice = round($payment->total_price, 2);
                $totalAmount = round($payment->total_amount, 2);
                $discountValue = round($payment->discount_value, 2);
                $sumValue = round(($totalAmount + $discountValue), 2);

                return $totalPrice < $sumValue;
            }
        }

        return false;
    }

    public function removeStaleFromLead($lead_status_id)
    {
        $skipStatus = [
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyDocumentsPending,
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::PolicySentToCustomer,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::CancellationPending,
            QuoteStatusEnum::PolicyCancelled,
            QuoteStatusEnum::PolicyCancelledReissued,
        ];

        return in_array($lead_status_id, $skipStatus);
    }

    public function getPaymentAuthorisedDays()
    {
        $paymentAuthorisedDays = intval(ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->value('value'));

        return intval($paymentAuthorisedDays ?? 0);
    }

    public function getRenewalBaches()
    {
        return app(RenewalBatchReportService::class)->getAllNonMotorBatches();
    }

    /**
     * Check if the insurance provider is supported for this payment gateway
     *
     * @param  int  $insuranceProviderId
     * @param  object  $quoteModel
     * @param  string  $type
     * @return bool
     */
    public function checkInsuranceProviderPaymentGateway($insuranceProviderId, $quoteModel, $type)
    {
        $insurerProvider = InsuranceProvider::where('id', $insuranceProviderId)->first();
        $quoteTypeId = QuoteTypes::getIdFromValue($type);
        $businessTypeId = $quoteModel->business_type_of_insurance_id ?? null;
        $planId = $quoteModel->plan_id ?? null;
        [,,, $isPaymentLinkEnabled] = app(BrokerCommissionService::class)->fetchBrokerCommission($quoteTypeId, $insuranceProviderId, $businessTypeId, $planId);
        if ($isPaymentLinkEnabled) {
            return true;
        }

        return $insurerProvider->payment_gateway_id == PaymentGatewayIdEnum::PAYMENT_GATEWAY_PL;
    }

    public function isHealthAUHLead($quoteType, $record)
    {
        return strtolower($quoteType) === strtolower(QuoteTypes::HEALTH->value) &&
                                            isset($record->emirate_of_your_visa_id) &&
                                            $record->emirate_of_your_visa_id === EmirateEnum::ABU_DHABI;
    }

    /**
     * Format dates from various input types to display format (d/m/Y) with comprehensive error handling
     *
     * @param  mixed  $date  Date input (string, DateTime, Carbon, or null)
     * @return string Formatted date in d/m/Y format or empty string on error
     */
    protected function formatDateToDisplay($date): string
    {
        if (! $date) {
            return '';
        }

        if (is_string($date)) {
            try {
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
                    $dateObj = Carbon::createFromFormat('d-m-Y', $date);

                    return $dateObj->format('d/m/Y');
                }

                $dateObj = Carbon::parse($date);

                return $dateObj->format('d/m/Y');
            } catch (\Exception $e) {
                LoggerService::warning('Failed to format date', extra: [
                    'date_input' => $date,
                    'error' => $e->getMessage(),
                ]);

                return '';
            }
        }

        if ($date instanceof \DateTime || $date instanceof Carbon) {
            try {
                return $date->format('d/m/Y');
            } catch (\Exception $e) {
                LoggerService::warning('Failed to format date object', extra: [
                    'date_class' => get_class($date),
                    'error' => $e->getMessage(),
                ]);

                return '';
            }
        }

        LoggerService::warning('Unexpected date type for formatting', extra: [
            'date_type' => gettype($date),
            'date_value' => $date,
        ]);

        return '';
    }

    public function getNationalityId(?string $nationality): ?int
    {
        if (empty($nationality)) {
            return null;
        }

        $nationalityRecord = Nationality::where('text', 'LIKE', '%'.$nationality.'%')
            ->orWhere('code', $nationality)
            ->orWhere('country_name', 'LIKE', '%'.$nationality.'%')
            ->first();

        return $nationalityRecord?->id;
    }

    public function getNationalityById($nationalityId): ?string
    {
        $nationalityRecord = Nationality::find($nationalityId);

        return $nationalityRecord?->text;
    }
}
