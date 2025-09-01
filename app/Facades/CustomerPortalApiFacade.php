<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Customer Portal API Facade
 *
 * Provides static access to CustomerPortalApiService methods
 *
 * @method static object request(string $path, string $method = 'post', array $data = [], bool $isCustomerOperation = false)
 * @method static object createCustomerAccount(array $customerData)
 * @method static object updateCustomerAccount(string $customerId, array $customerData)
 * @method static object getCustomerInfo(string $customerId)
 * @method static object syncCustomerPolicies(string $customerId, array $policies)
 * @method static object sendCustomerNotification(string $customerId, array $notificationData)
 * @method static object updateCustomerPreferences(string $customerId, array $preferences)
 * @method static bool ping()
 * @method static array getHealthStatus()
 */
class CustomerPortalApiFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'CustomerPortalApiService';
    }
}
