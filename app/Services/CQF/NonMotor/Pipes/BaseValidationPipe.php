<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\Pipes;

use App\Services\CQF\BaseCQFValidationService;
use App\Services\CQF\NonMotor\CQFRenewalContext;
use Closure;

class BaseValidationPipe
{
    public function __construct(
        protected BaseCQFValidationService $baseValidator
    ) {}

    public function handle(CQFRenewalContext $context, Closure $next): CQFRenewalContext
    {
        $result = $this->baseValidator->validateQuote($context->quote);

        if (! $result['success']) {
            return $context->fail($result['errors']);
        }

        return $next($context);
    }
}
