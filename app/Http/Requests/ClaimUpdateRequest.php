<?php

declare(strict_types=1);

namespace App\Http\Requests;

class ClaimUpdateRequest extends ClaimBaseRequest
{
    protected function validationFailedLogMessage(): string
    {
        return 'Claim update validation failed';
    }

    /**
     * @return array<string, mixed>
     */
    protected function validationFailedExtraContext(): array
    {
        return [
            'claim_uuid' => $this->route('uuid'),
        ];
    }
}
