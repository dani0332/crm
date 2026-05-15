<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\MyAlfredWelcomeEmailProcessResult;
use App\Exceptions\CustomerNotFoundForWelcomeEmailException;
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

            $customer = Customer::query()->where('email', $email)->first();

            if (! $customer) {
                LoggerService::warning('MyAlfred Welcome Email - Customer not found', [
                    'customer_email' => $email,
                ]);

                throw new CustomerNotFoundForWelcomeEmailException($email);
            }

            LoggerService::info('MyAlfred Welcome Email - Dispatching job', [
                'customer_email' => $customer->email,
            ]);

            MAWelcomeJob::dispatch($customer, $source, $tag);

            return MyAlfredWelcomeEmailProcessResult::Dispatched;
        } catch (Throwable $e) {
            if (! $e instanceof CustomerNotFoundForWelcomeEmailException) {
                LoggerService::error('MyAlfred Welcome Email - Processing failed', [], $e, [
                    'customer_email' => $email,
                ]);
            }

            throw $e;
        } finally {
            LoggerService::endLogging();
        }
    }
}
