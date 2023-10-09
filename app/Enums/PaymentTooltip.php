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

    // Frequency dropdown list
    const FREQUENCY_LIST_UPFRONT   = 'This refers to a one-time payment that needs to be settled before any services are provided or goods delivered. It\'s an advance payment, often covering the total amount.';
    const FREQUENCY_LIST_MONTHLY   = 'This is a recurring payment method where the customer pays a specified amount every month, typically at the beginning or end of the month. It spreads the total amount over 12 equal payments throughout the year.';
    const FREQUENCY_LIST_QUARTERLY = 'Under this payment term, the total amount is divided into four parts. Payments are expected every three months, which means four times in a year.';
    const FREQUENCY_LIST_SEMI_ANNUAL = 'This payment structure requires the customer to make payments twice a year. It breaks down the total amount into two equal parts, usually made every six months.';
    const FREQUENCY_LIST_SPLIT_PAYMENTS    = 'This allows the customer flexibility in settling the total amount. They can pay in multiple, divided amounts or use different payment methods for each portion. It\'s especially useful when coordinating payments from multiple sources or for larger amounts.';
    const FREQUENCY_LIST_CUSTOM   = 'Gain flexibility in settling the total amount. You can make payments in multiple, divided amounts using various payment terms and methods. Adjust the number of payments needed, ranging from 1 to 12, and customize due dates for each payment number to suit customers preferences';

    // Payments dropdown list
    const PAYMENT_LIST_BT   = 'This payment method involves the customer transferring funds directly from their bank account. It can be done electronically or through physical means such as cash or cheque deposits.';
    const PAYMENT_LIST_CSH   = 'With this method, the customer provides physical currency as payment. Ensure proper documentation and receipts when dealing with cash transactions to maintain transparency.';
    const PAYMENT_LIST_CHQ   = 'The customer pays using a cheque that has the current date on it. Ensure the cheque details are correctly filled out and verify its authenticity.';
    const PAYMENT_LIST_CC   = 'The customer settles their payment using a credit card. This can be done in-person or electronically. Ensure to get authorization and proper documentation for such transactions.';
    const PAYMENT_LIST_PDC   = 'This is a cheque given by the customer with a future date on it. It\'s a commitment to pay and cannot be cashed until the date mentioned.';
    const PAYMENT_LIST_IP   = 'This indicates a direct payment to the insurance provider. It\'s not a payment to the broker or agency but directly to the company underwriting the insurance.';
    const PAYMENT_LIST_PP   = 'This payment method is used when the total amount is divided into multiple payments over a set period. It\'s typically chosen for semi-annual, quarterly, or monthly payment frequencies.';
    const PAYMENT_LIST_MP   = 'When the customer opts to use various methods or sources to pay the total amount, this option is chosen. It\'s often used in conjunction with the split payment method.';
    const PAYMENT_LIST_CA   = 'This indicates a special payment arrangement where there isn\'t an immediate payment. Instead, the advisor seeks permission from higher-ups to issue the policy first, often due to specific circumstances or arrangements.';
    const PAYMENT_LIST_PPR   = 'This is a preliminary payment request drafted and shared with the customer for their review or action. Such requests typically need approval from senior management before being finalized or shared.';
    const PAYMENT_LIST_IN_PL   = 'This flexible payment option allows the customer to obtain their insurance policy first and then set up an instalment-based payment plan to settle the total amount due.';
    



}
