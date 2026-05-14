<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\Pipes;

use App\Services\CQF\NonMotor\CQFRenewalContext;
use Closure;

class StoragePipe
{
    public function handle(CQFRenewalContext $context, Closure $next): CQFRenewalContext
    {
        if ($context->storage === null || $context->hasErrors()) {
            return $next($context);
        }

        $context->newQuote = $context->storage->storeRenewalQuote(
            $context->quote,
            $context->renewalsUploadLeads,
            $context->epCodes
        );

        return $next($context);
    }
}
