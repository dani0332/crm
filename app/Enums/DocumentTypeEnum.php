<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

class DocumentTypeEnum extends Enum
{
    const ProformaPaymentRequest = 'Proforma Payment Request';
    const RECEIPT = 'Receipt';
    const AUDIT_RECORD = 'Audit Record';
    const ISSUING_DOCUMENTS = 'ISSUING_DOCUMENTS';
}
