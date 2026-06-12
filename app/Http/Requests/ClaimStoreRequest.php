<?php

declare(strict_types=1);

namespace App\Http\Requests;

class ClaimStoreRequest extends ClaimBaseRequest
{
    protected function validationFailedLogMessage(): string
    {
        return 'Claim store validation failed';
    }
}
