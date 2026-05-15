<?php

namespace App\Providers;

use App\Events\AmlAutomationScreeningSucceeded;
use App\Events\Axiom\FlushAxiomBatch;
use App\Events\BikeQuoteAdvisorUpdated;
use App\Events\CarQuoteAdvisorUpdated;
use App\Events\Device\DevicePaymentAuthorised;
use App\Events\Health\HealthTransactionApproved;
use App\Events\HealthQuoteAdvisorUpdated;
use App\Events\NationalityPoolCreated;
use App\Events\PrivateClientUpdatedEvent;
use App\Events\QuoteEmailUpdated;
use App\Events\QuotePolicyBooked;
use App\Events\TravelQuoteAdvisorUpdated;
use App\Listeners\ApplyPrivateClientTagListener;
use App\Listeners\Axiom\HandleAxiomBatchFlush;
use App\Listeners\Device\HandleDevicePaymentAuthorised;
use App\Listeners\HandleBikeAdvisorUpdated;
use App\Listeners\HandleBookPolicyJobFailed;
use App\Listeners\HandleCarAdvisorUpdated;
use App\Listeners\HandleHealthAdvisorUpdated;
use App\Listeners\HandleNationalityPoolCreated;
use App\Listeners\HandleTravelAdvisorUpdated;
use App\Listeners\Health\HandleHealthTransactionApproved;
use App\Listeners\Impersonation\HandleImpersonatedSession;
use App\Listeners\LoginListener;
use App\Listeners\LogoutListener;
use App\Listeners\SendAlfredCoinsInsurancePurchasedWebhook;
use App\Listeners\SendAmlAutomationOutcomeNotifications;
use App\Listeners\TriggerConversionApis;
use App\Listeners\UpdateCustomerEmail;
use App\Models\RenewalBatch;
use App\Observers\RenewalBatchObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobReleasedAfterException;
use Illuminate\Queue\Events\JobTimedOut;
use Lab404\Impersonate\Events\TakeImpersonation;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        CarQuoteAdvisorUpdated::class => [
            HandleCarAdvisorUpdated::class,
        ],
        TravelQuoteAdvisorUpdated::class => [
            HandleTravelAdvisorUpdated::class,
        ],
        HealthQuoteAdvisorUpdated::class => [
            HandleHealthAdvisorUpdated::class,
        ],
        Login::class => [
            LoginListener::class,
        ],
        Logout::class => [
            LogoutListener::class,
        ],
        QuoteEmailUpdated::class => [
            UpdateCustomerEmail::class,
        ],
        BikeQuoteAdvisorUpdated::class => [
            HandleBikeAdvisorUpdated::class,
        ],
        HealthTransactionApproved::class => [
            HandleHealthTransactionApproved::class,
        ],
        DevicePaymentAuthorised::class => [
            HandleDevicePaymentAuthorised::class,
        ],
        TakeImpersonation::class => [
            HandleImpersonatedSession::class,
        ],
        FlushAxiomBatch::class => [
            HandleAxiomBatchFlush::class,
        ],
        CommandFinished::class => [
            HandleAxiomBatchFlush::class,
        ],
        ScheduledTaskFailed::class => [
            HandleAxiomBatchFlush::class,
        ],
        ScheduledTaskFinished::class => [
            HandleAxiomBatchFlush::class,
        ],
        JobProcessed::class => [
            HandleAxiomBatchFlush::class,
        ],
        JobExceptionOccurred::class => [
            HandleAxiomBatchFlush::class,
        ],
        JobFailed::class => [
            HandleAxiomBatchFlush::class,
            HandleBookPolicyJobFailed::class,
        ],
        JobReleasedAfterException::class => [
            HandleAxiomBatchFlush::class,
        ],
        JobTimedOut::class => [
            HandleAxiomBatchFlush::class,
        ],
        PrivateClientUpdatedEvent::class => [
            ApplyPrivateClientTagListener::class,
        ],
        QuotePolicyBooked::class => [
            TriggerConversionApis::class,
            SendAlfredCoinsInsurancePurchasedWebhook::class,
        ],
        NationalityPoolCreated::class => [
            HandleNationalityPoolCreated::class,
        ],
        AmlAutomationScreeningSucceeded::class => [
            [SendAmlAutomationOutcomeNotifications::class, 'handleSucceeded'],
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        RenewalBatch::observe(RenewalBatchObserver::class);
    }
}
