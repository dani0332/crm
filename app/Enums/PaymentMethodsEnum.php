<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class PaymentMethodsEnum extends Enum
{
    const CreditCard = 'CC';
    const BankTransfer = 'BT';
    const Cash = 'CSH';
    const Cheque = 'CHQ';
    const Credit = 'CR';
    const GMApproval = 'CR_FAYAZ';
    const CMOApproval = 'CR_HITESH';
    const COOApproval = 'CR_MAHESH';
    const InsureNowPayLater = 'IN_PL';
    const PostDatedCheque = 'PDC';
    const InsurerPayment = 'IP';
    const PartialPayment = 'PP';
    const MultiplePayment = 'MP';
    const CreditApproval = 'CA';
    const ProformaPaymentRequest = 'PPR';
}
