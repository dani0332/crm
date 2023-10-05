<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentTooltip extends Enum
{
    //Main section
    const COLLECTION_DATE = 'The date when the payment is due or when it was collected. Ensure to update this date accurately to maintain proper payment records.';
    const TOTAL_PRICE = 'The entire amount due before any potential discounts. Remember, VAT is exempt for Life Insurance policies.';
    const COLLECTED_BY = 'Who\'s collecting the payment? Choose between the Broker or the Insurer. If unsure, consult your supervisor.
';
    const PROVIDER_NAME = 'Which insurance company is this policy from? Find and select the name from our list.';
    const FREQUENCY = 'How often is the payment made? Options might include once upfront, twice a year, and so on. This sets the payment schedule.';
    const PLAN_NAME = 'Every insurer has different plans. Input the specific one for this lead.';
    const PAYMENT_NO = 'This refers to the sequence or installment number of the payment. It helps in tracking multiple payments for a lead.';
    const PAYMENT_STATUS = 'Tracks the progression of the payment. \'New\' indicates a fresh transaction, while \'Paid\' confirms the receipt of funds. Update this status as the payment process advances.';
    const CREDIT_APPROVAL = 'This indicates a special payment arrangement where there isn\'t an immediate payment. Instead, the advisor seeks permission from higher-ups to issue the policy first, often due to specific circumstances or arrangements.
    ';
    const DISCOUNT_APPLICABLE = 'Is there a special discount? please specify its type here. Ensure that it has been approved before applying. If you\'re unclear about discounts, please contact your supervisor. ';
    //Section 2    
    const PAYMENT_NO_2 = 'This refers to the sequence or installment number of the payment. It helps in tracking multiple payments for a lead.';
    const PAYMENT_METHOD = 'Select how the payment is being made. This could be through credit card, debit card, bank transfer, cheque, etc.';
    const TOTAL_AMOUNT = 'Input the final amount due, inclusive of VAT. However, remember that Life insurance policies are exempt from VAT.
    ';
    const DUE_DATE = 'Please specify the date by which the payment should be received. This helps in keeping track of timely collections.';
    const DOCUMENTS = 'Either click to browse your computer or simply drag and drop the necessary files here. It\'s a quick way to attach your documents.
    ';
    // Collector dropdown list
    const COLLECTOR_LIST_BROKER    = 'Payment made directly to Insurancemarket.ae by the customer.';
    const COLLECTOR_LIST_INSURER   = 'Payment made directly to the insurer by the customer.';
}
