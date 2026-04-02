<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\MyAlfredWelcomeEmailProcessResult;
use App\Jobs\MAWelcomeJob;
use App\Models\Customer;
use App\Services\Logger\LoggerService;
use Throwable;

class MyAlfredWelcomeEmailInboundService
{
    public function process(
        string $email,
        string $code,
        ?string $source = null,
        ?string $tag = null,
    ): MyAlfredWelcomeEmailProcessResult {
        LoggerService::startFeatureLogging(feature: LoggerFeatureEnum::SEND_MA_WELCOME_EMAIL);

        try {
            LoggerService::info('MyAlfred Welcome Email - Request received', [
                'customer_email' => $email,
                'code' => $code,
                'source' => $source,
                'tag' => $tag,
            ]);
        } catch (Throwable $e) {
            // Ensure logging failures don't break the business flow.
            LoggerService::error('MyAlfred Welcome Email - Logging failed', [], $e);
        }

        $customer = Customer::query()->where('email', $email)->first();

        if (! $customer) {
            LoggerService::warning('MyAlfred Welcome Email - Customer not found', [
                'customer_email' => $email,
            ]);

            return MyAlfredWelcomeEmailProcessResult::CustomerNotFound;
        }

        LoggerService::info('MyAlfred Welcome Email - Dispatching job', [
            'customer_email' => $customer->email,
        ]);

        MAWelcomeJob::dispatch($customer, $source, $tag);

        return MyAlfredWelcomeEmailProcessResult::Dispatched;
    }
}
