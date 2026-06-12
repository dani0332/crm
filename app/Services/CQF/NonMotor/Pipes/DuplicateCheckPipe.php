<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\Pipes;

use App\Services\CQF\NonMotor\CQFRenewalContext;
use Closure;

class DuplicateCheckPipe
{
    public function handle(CQFRenewalContext $context, Closure $next): CQFRenewalContext
    {
        if ($context->validator === null) {
            return $next($context);
        }

        if ($context->validator->isDuplicateQuote($context->quote)) {
            return $context->fail([
                'policy_number' => 'Duplicate quote detected for policy number: '.$context->quote->policy_number,
            ]);
        }

        return $next($context);
    }
}
