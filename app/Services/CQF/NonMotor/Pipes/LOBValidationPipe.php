<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\Pipes;

use App\Services\CQF\NonMotor\CQFRenewalContext;
use Closure;

class LOBValidationPipe
{
    public function handle(CQFRenewalContext $context, Closure $next): CQFRenewalContext
    {
        if ($context->validator === null) {
            return $next($context);
        }

        $result = $context->validator->validateQuote($context->quote);

        if (! $result['success']) {
            return $context->fail($result['errors']);
        }

        return $next($context);
    }
}
