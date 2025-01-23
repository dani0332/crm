<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentTooltip extends Enum
{
    // Managment section
    const PAYMENT_MANAGEMENT_PAYMENT_NO = 'This refers to the sequence or installment number of the payment. It helps in tracking multiple payments for a lead.';
    const PAYMENT_MANAGEMENT_PAYMENT_REF_ID = 'This is the unique identifier for the payment, directly tied to the lead in question. Use it for tracking or referencing a specific transaction.';
    const PAYMENT_MANAGEMENT_COLLECTION_DATE = 'The date when the payment is due or when it was collected. Ensure to update this date accurately to maintain proper payment records.';
    const PAYMENT_MANAGEMENT_DUE_DATE = 'The final date by which the payment should be received. It\'s essential for tracking overdue payments and managing collection efforts.';
    const PAYMENT_MANAGEMENT_PAYMENT_METHOD = 'Indicates how the payment was made. This could be via bank transfer, cheque, credit card, etc. Update this based on the client\'s chosen payment mode.';
    const PAYMENT_MANAGEMENT_TOTAL_PRICE = 'The entire amount due before any potential discounts. Remember, VAT is exempt for Life Insurance policies.';
    const PAYMENT_MANAGEMENT_DISCOUNT_VALUE = 'Any reduction applied to the total price. Always ensure there\'s a valid reason for any discount given. It\'s the difference between the total price and the total amount that the client has to pay for.';
    const PAYMENT_MANAGEMENT_TOTAL_AMOUNT = 'This is the final amount due from the client after subtracting discounts (if any) from the total price.';
    const PAYMENT_MANAGEMENT_COLLECTED_AMOUNT = 'The actual amount we\'ve successfully collected from the client. This should match with the Total amount once full payment is received.';
    const PAYMENT_MANAGEMENT_PAYMENT_STATUS = 'Tracks the progression of the payment. \'New\' indicates a fresh transaction, while \'Paid\' confirms the receipt of funds. Update this status as the payment process advances.';
    const PAYMENT_MANAGEMENT_PAYMENT_ALLOCATION_STATUS = 'Indicates whether this payment is allocated to a specific invoice. This ensures that there\'s a clear record of invoicing for this payment.';
    const PAYMENT_MANAGEMENT_ACTION = 'Here, you can manage the payment details.Use \'Edit\' to modify payment information, \'View\' to see the entire payment setup, and \'Copy Link\' to share a direct payment link for clients preferring card payments.';
    const PAYMENT_MANAGEMENT_ADD_PAYMENT = 'Click this if you need to input a payment that was received outside our automated system. Ensure you have all necessary details and proofs when recording manually to maintain accuracy.';
    const PAYMENT_MANAGEMENT_DOWNLOAD_PROFORMA_PAYMENT = 'Download the proforma payment request here. It\'s intended for initiating payment requisitions from customers and is an effective method for tracking the progress of those payments.';
    const PAYMENT_MANAGEMENT_ADD_PAYMENT_DIS = 'Payment already added; click \'Edit\' for changes.';
    const PAYMENT_MANAGEMENT_NO_ACTION_ALLOWED_TO_PAID_PAYMENTS = 'No further actions allowed to paid payments.';
    const PAYMENT_ADD_DUPLICATE_FILES = 'You are uploading a file with the same name as another one. To avoid confusion, please rename the file or ensure it\'s the correct one. This way, we\'ll maintain a tidy and efficient document management process.';
    const PAYMENT_ADD_DELETE_DOCUMENT = 'Any uploaded documents cannot be deleted once the payment status marks as \'Paid\'.';
    const PAYMENT_DISCOUNT_PROOF_TITLE = 'Use this field to attach the approved discount proof. Ensure the proof includes approval from the Chief Marketing Officer, Chief Finance Officer, or General Manager. This document is crucial for verifying the discount and for record accuracy.';
    const PAYMENT_DISCOUNT_PROOF_VIEW = 'Review the proof of discount here. Ensure all relevant documents are present for complete transparency.';

    // Main section
    const COLLECTION_DATE = 'The date when the payment is due or when it was collected. Ensure to update this date accurately to maintain proper payment records.';
    const TOTAL_PRICE = 'The entire amount due before any potential discounts. Remember, VAT is exempt for Life Insurance policies.';
    const COLLECTED_BY = 'Who\'s collecting the payment? Choose between the Broker or the Insurer. If unsure, consult your supervisor.
';
    const PROVIDER_NAME = 'Which insurance company is this policy from? Find and select the name from our list.';
    const PROVIDER_NAME_VIEW = 'Name of the insurance company this insurance policy will be issued from.';
    const FREQUENCY = 'How often is the payment made? Options might include once upfront, twice a year, and so on. This sets the payment schedule.';
    const PLAN_NAME = 'Every insurer has different plans. Input the specific one for this lead.';
    const PLAN_NAME_VIEW = 'The plan name of the insurance policy to be issued by the insurance company.';
    const PAYMENT_NO = 'This refers to the sequence or installment number of the payment. It helps in tracking multiple payments for a lead.';
    const PAYMENT_STATUS = 'Tracks the progression of the payment. \'New\' indicates a fresh transaction, while \'Paid\' confirms the receipt of funds. Update this status as the payment process advances.';
    const CREDIT_APPROVAL = 'This indicates a special payment arrangement where there isn\'t an immediate payment. Instead, the advisor seeks permission from higher-ups to issue the policy first, often due to specific circumstances or arrangements.
    ';
    const DISCOUNT_APPLICABLE = 'Is there a special discount? please specify its type here. Ensure that it has been approved before applying. If you\'re unclear about discounts, please contact your supervisor. ';
    const DISCOUNT_APPLICABLE_VIEW = 'Is there a special discount applied? Check the specified type. Remember, any discounts should have prior approval. For clarity or if there are discrepancies with the discounts, consult your supervisor before making any decisions.';
    const DISCOUNT_REASON = 'Please specify the primary reason for applying this discount. Be cautious: Any frequent or unwarranted discounts can impact our company\'s revenue and brand. Always ensure there\'s a valid justification before selecting a reason. Incorrect choices may come under managerial scrutiny and review.';
    const DISCOUNT_VALUE = 'Any reduction applied to the total price. Always ensure there\'s a valid reason for any discount given. It\'s the difference between the total price and the total amount that the client has to pay for.';
    const TOTAL_AMOUNT_VIEW = 'This is the final amount due from the client after subtracting discounts (if any) from the total price.';

    // Section 2
    const PAYMENT_NO_2 = 'This refers to the sequence or installment number of the payment. It helps in tracking multiple payments for a lead.';
    const PAYMENT_METHOD = 'Select how the payment is being made. This could be through credit card, debit card, bank transfer, cheque, etc.';
    const TOTAL_AMOUNT = 'Input the final amount due, inclusive of VAT. However, remember that Life insurance policies are exempt from VAT.
    ';
    const DUE_DATE = 'Please specify the date by which the payment should be received. This helps in keeping track of timely collections.';
    const DOCUMENTS = 'Upload all relevant documents here. This can be proof of payment, discount approvals, credit approvals or related documents.';
    const PAYMENT_METHOD_VIEW = 'Indicates how the payment was made. This could be via bank transfer, cheque, credit card, etc. Update this based on the client\'s chosen payment mode.';
    const TOTAL_AMOUNT_SPLIT_VIEW = 'This is the final amount due from the client after subtracting discounts (if any) from the total price.';
    const DUE_DATE_VIEW = 'The final date by which the payment should be received. It\'s essential for tracking overdue payments and managing collection efforts.';
    const DOCUMENTS_VIEW = 'Review the uploaded documents in this section. They may include proof of payment, discount approvals, credit approvals, or other pertinent paperwork. Ensure all relevant documents are present for complete transparency.';
    const CHECK_DETAILS = 'Enter the unique number found on the cheque. This is essential for tracking and verification purposes. Only fill this out if the payment method is \'cheque\'.';
    const DOCUMENTS_UPLOAD = 'Either click to browse your computer or simply drag and drop the necessary files here. It\'s a quick way to attach your documents.';
    const DOCUMENT_DELETE_ICON = 'Remove';
    const CAPTURE_AMOUNT = 'This is the specific amount you\'re confirming or \'capturing\' from the total amount that\'s authorized. Make sure it doesn\'t exceed the total amount due.';

    // Collector dropdown list
    const COLLECTOR_LIST_BROKER = 'Payment made directly to Insurancemarket.ae by the customer.';
    const COLLECTOR_LIST_INSURER = 'Payment made directly to the insurer by the customer.';

    // Frequency dropdown list
    const FREQUENCY_LIST_UPFRONT = 'This refers to a one-time payment that needs to be settled before any services are provided or goods delivered. It\'s an advance payment, often covering the total amount.';
    const FREQUENCY_LIST_MONTHLY = 'This is a recurring payment method where the customer pays a specified amount every month, typically at the beginning or end of the month. It spreads the total amount over 12 equal payments throughout the year.';
    const FREQUENCY_LIST_QUARTERLY = 'Under this payment term, the total amount is divided into four parts. Payments are expected every three months, which means four times in a year.';
    const FREQUENCY_LIST_SEMI_ANNUAL = 'This payment structure requires the customer to make payments twice a year. It breaks down the total amount into two equal parts, usually made every six months.';
    const FREQUENCY_LIST_SPLIT_PAYMENTS = 'This allows the customer flexibility in settling the total amount. They can pay in multiple, divided amounts or use different payment methods for each portion. It\'s especially useful when coordinating payments from multiple sources or for larger amounts.';
    const FREQUENCY_LIST_CUSTOM = 'Gain flexibility in settling the total amount. You can make payments in multiple, divided amounts using various payment terms and methods. Adjust the number of payments needed, ranging from 2 to 12, and customize due dates for each payment number to suit customers preferences';

    // Credit approval dropdown list
    const CREDIT_APPROVAL_LIST_AVAILABLE = 'This indicates that the customer already has a credit balance with us, perhaps from overpayments or prior arrangements. This credit balance can be used against the current total amount due, thereby not requiring immediate additional payment.';
    const CREDIT_APPROVAL_LIST_POSTDATED = 'The customer has provided a cheque with a future date on it, indicating their commitment to pay on that specified date. As a result, the policy issuance can proceed, but the payment won\'t be collected until the cheque\'s date.';
    const CREDIT_APPROVAL_LIST_CLEARANCE = 'The customer has opted to pay via cheque, which has been submitted, but it\'s still in the banking process for clearance. This means the funds are yet to be officially transferred and reflected in our account.';
    const CREDIT_APPROVAL_LIST_REASON = 'This option allows for unique or specific situations not covered by the predefined reasons. If selected, you must provide a detailed explanation in the provided text area to ensure clarity and proper documentation.';

    // Discount types dropdown list
    const DISCOUNT_TYPE_LIST_REFER = 'This discount is a thank-you gesture to customers who bring new clients to our business. By referring someone, they earn a reduction in their total amount.';
    const DISCOUNT_TYPE_LIST_INCENTIVE = 'This isn\'t a traditional discount. Instead, a portion or all of this amount can be adjusted against the advisor\'s incentives. It\'s a flexible way to balance between customer discounts and advisor incentives.';
    const DISCOUNT_TYPE_LIST_MANAGERIAL = 'This discount is not standardised and requires specific approval from the management. It\'s typically granted under unique circumstances or for special cases only.';
    const DISCOUNT_TYPE_LIST_EMPLOYEE = 'A special price reduction exclusive to InsuranceMarket.ae employees, recognizing their contributions and encouraging them to use our services.';
    const DISCOUNT_TYPE_LIST_FAMILY = 'This discount extends our appreciation to the families of our employees. Exclusive to family members of InsuranceMarket.ae staff, it provides a reduced rate on insurance premiums.';

    // Discount reason dropdown list
    const DISCOUNT_REASON_LIST_PROMOTIONAL = 'Reserved STRICTLY for well-defined marketing campaigns. Every unplanned discount can reduce our revenue and brand value. Ensure that the campaign is currently active and authorized. Misuse or increased use may result in a review of your decisions with your line manager.';
    const DISCOUNT_REASON_LIST_LOYALTY = 'ONLY for our most loyal customers who\'ve shown continued trust over the years. Be cautious: frequent, undeserved discounts can affect our profitability and undervalue our services. Ensure this is backed by a substantial purchase history to validate the use of such a discount. No proof will result in a review with your manager.';
    const DISCOUNT_REASON_LIST_COMPETITIVE = 'Use SPARINGLY. While we want to stay competitive, undercutting without a strategic basis can harm our market position. Confirm that the competitor\'s offer is genuine and that our discount won\'t compromise our revenue and brand value.';
    const DISCOUNT_REASON_LIST_CUSTOM_REASON = 'This should be your LAST RESORT. Every custom discount impacts our earnings and requires rigorous justification and approvals from your line manager. You\'ll be held accountable for providing a detailed and valid reason. Incorrect selections will be subject to managerial review.';

    // Payments dropdown list
    const PAYMENT_LIST_BT = 'This payment method involves the customer transferring funds directly from their bank account. It can be done electronically or through physical means such as cash or cheque deposits.';
    const PAYMENT_LIST_CSH = 'With this method, the customer provides physical currency as payment. Ensure proper documentation and receipts when dealing with cash transactions to maintain transparency.';
    const PAYMENT_LIST_CHQ = 'The customer pays using a cheque that has the current date on it. Ensure the cheque details are correctly filled out and verify its authenticity.';
    const PAYMENT_LIST_CC = 'The customer settles their payment using a credit card. This can be done in-person or electronically. Ensure to get authorization and proper documentation for such transactions.';
    const PAYMENT_LIST_PDC = 'This is a cheque given by the customer with a future date on it. It\'s a commitment to pay and cannot be cashed until the date mentioned.';
    const PAYMENT_LIST_IP = 'This indicates a direct payment to the insurance provider. It\'s not a payment to the broker or agency but directly to the company underwriting the insurance.';
    const PAYMENT_LIST_PP = 'This payment method is used when the total amount is divided into multiple payments over a set period. It\'s typically chosen for semi-annual, quarterly, or monthly payment frequencies.';
    const PAYMENT_LIST_MP = 'When the customer opts to use various methods or sources to pay the total amount, this option is chosen. It\'s often used in conjunction with the split payment method.';
    const PAYMENT_LIST_CA = 'This indicates a special payment arrangement where there isn\'t an immediate payment. Instead, the advisor seeks permission from higher-ups to issue the policy first, often due to specific circumstances or arrangements.';
    const PAYMENT_LIST_PPR = 'This is a preliminary payment request drafted and shared with the customer for their review or action. Such requests typically need approval from senior management before being finalized or shared.';
    const PAYMENT_LIST_IN_PL = 'This flexible payment option allows the customer to obtain their insurance policy first and then set up an instalment-based payment plan to settle the total amount due.';

    // Payment View
    const PAYMENT_VIEW_SAGE_RECIPT = 'A unique receipt number generated by the Sage platform. It confirms the transaction and can be used for reconciliation or referencing purposes.';
    const PAYMENT_VIEW_WALLET = 'The specific type of digital or e-wallet used for the transaction. Examples include Apple Pay, Google Pay, and other e-wallet platforms.';
    const PAYMENT_VIEW_CC_GATEWAY = 'The digital system responsible for securely processing electronic payments. It ensures safe transfer of funds during a transaction.';
    const PAYMENT_VIEW_CC_ID = 'A unique identifier for credit card payments. It\'s essential for tracking individual transactions and resolving any payment-related issues.';
    const PAYMENT_VIEW_STATUS = 'Tracks the progression of the payment. \'New\' indicates a fresh transaction, while \'Paid\' confirms the receipt of funds. Update this status as the payment process advances.';
    const PAYMENT_VIEW_ALLO_STATUS = 'Indicates whether this payment is allocated to a specific invoice. This ensures that there\'s a clear record of invoicing for this payment.';
    const PAYMENT_VIEW_COLLECTED_TEXT = 'The actual amount we\'ve successfully collected from the client. This should match with the Total amount once full payment is received.';
    const PAYMENT_VIEW_CC_PAYMENT_STATUS = 'Detailed status of the credit card payment, including reasons for any declines. This provides clarity on whether a transaction was successful or if there were issues.';
    const PAYMENT_VIEW_VERIFICATION_HEADER = 'This section focuses on confirming the receipt and details of payments. It ensures the legitimacy and completeness of each transaction.';
    const PAYMENT_VIEW_COLLECTED_AMOUNT = 'The complete amount that has been successfully captured or received from the customer during the transaction.';
    const PAYMENT_VIEW_BANK_REFERENCE = 'This unique code, assigned by the banking institution, serves as a specific reference for the transaction. It\'s crucial for tracking and auditing purposes.';
    const PAYMENT_VIEW_DOCUMENTS = 'Upload the insurer\'s official receipt here. This document provides evidence of the transaction and is vital for record-keeping and any future verifications.';

    // Reason dropdown list
    const DECLINED_REASON_1 = 'Incorrect payment information, proof of payment or receipt provided';
    const DECLINED_REASON_2 = 'Payment proof or receipt is not readable';
    const DECLINED_REASON_3 = 'Insufficient documentation';
    const DECLINED_REASON_4 = 'No proof of discount approval';
    const DECLINED_REASON_5 = 'No proof of Credit approval';
    const DECLINED_REASON_6 = 'Other reasons';
    const CONFIRM_APPROVE_UNSELECT = 'Select the checkbox to enable the \'Confirm\' button and proceed';
    const PAYMENT_LOCKED = 'This lead is now locked as the policy has been booked. If changes are needed, please make them through the \'Send Update\' section via \'Correction of Policy\'';
    const GOTO_AML_AND_KYC_PAGE = 'Click to initiate AML screening and complete the KYC process.';
    const CREDIT_APPROVAL_PAYMENT_METHOD_DISABLED_MESSAGE = 'Selection is disabled due to Credit Approval applied. Please remove Credit Approval to modify';
}
