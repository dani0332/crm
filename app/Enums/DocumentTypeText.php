<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

class DocumentTypeText extends Enum
{
    const TAX_INVOICE = 'Tax Invoice';
    const TAX_INVOICE_RAISED_BY_BUYER = 'Tax Invoice Raised By Buyer';
}
