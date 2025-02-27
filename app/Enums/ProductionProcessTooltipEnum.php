<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ProductionProcessTooltipEnum extends Enum
{
    // Managment section
    const POLICY_NUMBER = 'The unique Insurance policy number for the chosen insurance plan offered by the provider.';
    const PRICE_VAT_NOT_APPLICABLE = 'Price that VAT is not applicable. Remember, VAT is exempt for Life Insurance policies.';
    const PRICE_VAT_APPLICABLE = 'Price as per the insurers tax invoice that VAT is applicable. Please enter the price without including Value Added Tax (VAT). VAT will be calculated separately.';
    const TOTAL_VAT_AMOUNT = 'Display the total Value Added Tax (VAT) amount for this transaction. Verify this amount before submission.';
    const INSURER_QUOTE_NUMBER = 'Refers to the unique identifier associated with the initial quote provided by the insurer.';
    const ISSUANCE_DATE = 'Signifies the date when the insurance policy was officially issued.';
    const START_DATE = 'Indicates the commencement date of the insurance coverage, marking when the policy becomes effective.';
    const EXPIRY_DATE = 'This field records the date when the insurance coverage is set to expire, marking the end of the policy validity.';
    const TOTAL_PRICE = 'Display the total price including all charges and VAT as per tax invoice. Make sure it aligns with the final transaction amount.';
    const ISSURANEC_STATUS = 'Indicates the current state or progress of policy issuance, tracking whether its pending, approved, or completed.';

    // Bookind policy section
    const BOOKING_DATE = 'The exact date when the booking details was successfully recorded in the system.';
    const LINE_OF_BUSINESS = 'Signifies the specific category or type of insurance coverage associated with this booking. It helps categorize the booking by its primary insurance focus, allowing for better organization and classification of insurance transactions.';
    const SUB_TYPE = 'Secondary category or classification of insurance coverage associated with this booking. It provides further specification and detail regarding the nature of the insurance coverage within the selected primary line of business.';
    const INVOICE_DESCRIPTION = 'Description per invoice entry in Sage.';
    const TRANSACTION_PAYMENT_STATUS = 'This status provides a real-time snapshot of the payment progress for each insurer tax invoice. Make sure to update these statuses regularly to maintain financial accuracy.';
    const BROKER_INVOICE_NUMBER = 'Invoice number provided by the broker.';
    const INSURER_INVOICE_DATE = 'Signifies the date when the insurer invoice within the booking was issued.';
    const INSURER_TAX_INVOICE_NUMBER = 'Enter the unique tax invoice number provided by the insurer. It helps in proper identification and tracking of transactions.';
    const INSURER_COMMISSION_TAX_INVOICE_NUMBER = 'Input the invoice number issued by the insurer for commission purposes. Double-check for accuracy.';
    const COMMISSION_VAT_NOT_APPLICABLE = 'Commission amount as per the tax invoice raised by buyer that VAT is not applicable.';
    const COMMISSION_VAT_APPLICABLE = 'Commission amount as per the tax invoice raised by buyer that VAT is applicable. Enter commission amount without including Value Added Tax (VAT). VAT will be calculated separately.';
    const COMMISSION_PERCENTAGE = 'Commission percentage for this transaction.';
    const VAT_ON_COMMISSION = 'Value Added Tax (VAT) amount applicable to the commission.';
    const TOTAL_COMMISSION = 'Display the total commission amount including VAT for this transaction. Ensure it matches the calculations.';
    const DISCOUNT_VALUE = 'If applicable, this field indicates the exact amount or percentage reduced from the original price.';
    const TRANSACTION_PAYMENT_STATUS_NOT_PAID = 'This status indicates that no payments have been applied to the associated insurer tax invoice. Regular follow-ups are essential to ensure timely collections.';
    const TRANSACTION_PAYMENT_STATUS_PARTIALLY_PAID = 'The invoice has received a portion of its total amount due. Please ensure that the remaining balance is collected promptly to prevent potential financial discrepancies.';
    const TRANSACTION_PAYMENT_STATUS_PAID = 'This insurer tax invoice has been settled in its entirety, with no outstanding amounts. Always review payments to guarantee the accuracy of this status.';
    const POLICY_DETAILS_LOCKED_TOOL_TIP = "This lead is now locked as the policy has been booked. If changes are needed, go to 'Send Update', select 'Add Update', and choose 'Correction of Policy'";
    const NON_SELF_BILLING_INSURER_COM_TAX_INVOICE_NUMBER_TOOLTIP = 'For non-self billing insurers, broker invoice number will automatically be used as insurer commission tax invoice number';

    // Manage payment section
    const PAYMENT_ALLOCATION_STATUS_NOT_ALLOCATED = 'This payment is currently standalone and hasn\'t been associated with any insurer tax invoices. It\'s essential to review and link it to its relevant invoice(s) for accurate accounting.';
    const PAYMENT_ALLOCATION_STATUS_PARTIALLY_ALLOCATED = 'This payment is connected to one or more insurer tax invoices, but there\'s a balance remaining. The unallocated portion should be connected to relevant invoices or accounted for.';
    const PAYMENT_ALLOCATION_STATUS_FULLY_ALLOCATED = 'This payment is thoroughly associated with insurer tax invoices, ensuring that there are no outstanding amounts or pending links.';
    const COMMISSION_VAT_APPLICABLE_FILLED = 'This option is disabled because Commission (VAT applicable) has already been entered';
    const COMMISSION_VAT_NOT_APPLICABLE_FILLED = 'This option is disabled because Commission (VAT not applicable) has already been entered.';
}
