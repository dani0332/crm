<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\Pipes;

use App\Services\CQF\NonMotor\CQFRenewalContext;
use App\Services\Logger\LoggerService;
use Closure;

class InslyCheckPipe
{
    public function handle(CQFRenewalContext $context, Closure $next): CQFRenewalContext
    {
        if ($context->validator === null) {
            return $next($context);
        }

        if (! $context->validator->checkInslyRenewal($context->quote)) {
            LoggerService::info(self::class.' - Insly renewal criteria not met for policy number. Skipping processing', [
                'policy_number' => $context->quote->policy_number,
            ]);

            return $context->fail([
                'policy_number' => 'Insly renewal criteria not met for policy number: '.$context->quote->policy_number,
            ]);
        }

        return $next($context);
    }
}
