<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PaymentStatusEnum;
use App\Jobs\AdvisorPaymentNotificationJob;
use App\Models\Payment;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendPaymentEmailToAdvisor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SendPaymentEmailToAdvisor:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Authorised Payments Notifications To Advisor';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::AUTHORISED_PAYMENT_NOTIFICATION_TO_ADVISOR);

        $startTime = microtime(true);

        // Check if the advisor email notification is enabled for authorised payments.
        if (! getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_PAYMENT_NOTIFICATION_EMAIL_TO_ADVISOR)) {
            LoggerService::info('ENABLE_PAYMENT_NOTIFICATION_EMAIL_TO_ADVISOR is Disable');

            return false;
        }

        $authorizedDays = getAppStorageValueByKey(ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS_TO_ADVISOR) ?? 1;

        $advisorWisePayments = $this->getAdvisorWisePayments($authorizedDays);

        if ($advisorWisePayments->isEmpty()) {
            LoggerService::info('No advisor found to send payment email.');

            return false;
        }

        LoggerService::info(count($advisorWisePayments).' advisor wise payments found.');

        foreach ($advisorWisePayments as $index => $payment) {
            $iterationStartTime = microtime(true);

            if ($payment['advisorId']) {
                LoggerService::info("Dispatching AdvisorPaymentNotification Job For Advisor {$payment['advisorName']} (ID: {$payment['advisorId']})");
                AdvisorPaymentNotificationJob::dispatch($payment)->onQueue('advisor-payment-notification');
            }

            $iterationEndTime = microtime(true);
            $iterationExecutionTime = $iterationEndTime - $iterationStartTime;
            LoggerService::info("SendPaymentEmailToAdvisor - Time: {$index} ({$payment['advisorEmail']}): ".number_format($iterationExecutionTime, 5).' seconds');
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        LoggerService::info('SendPaymentEmailToAdvisor - '.number_format($executionTime, 5).' seconds');
    }

    public function getAdvisorWisePayments($authorizedDays): \Illuminate\Support\Collection
    {
        $authorizedDate = $lastDay = Carbon::today()->subDays($authorizedDays);

        return Payment::with([
            'personalQuote:id,code,advisor_id,premium',
            'personalQuote.advisor:id,name,email',
        ])
            ->where('payments.payment_status_id', PaymentStatusEnum::AUTHORISED)
            ->where('payments.authorized_at', '>=', $authorizedDate)
            ->get()
            ->filter(fn ($payment) => $payment->personalQuote !== null)
            ->groupBy(fn ($payment) => $payment->personalQuote->advisor_id)
            ->map(function ($advisorPayments, $advisorId) use ($lastDay) {
                $advisor = $advisorPayments?->first()?->personalQuote?->advisor;

                return [
                    'advisorId' => $advisorId,
                    'advisorName' => $advisor?->name,
                    'advisorEmail' => $advisor?->email,
                    'totalLeads' => (string) $advisorPayments->count(),
                    'totalExpiringLeads' => (string) $advisorPayments->filter(
                        fn ($p) => Carbon::parse($p->getRawOriginal('authorized_at'))->toDateString() === $lastDay->toDateString()
                    )->count(),
                    'totalPremium' => (string) $advisorPayments->sum(fn ($p) => (float) $p->personalQuote->premium),
                ];
            })
            ->values();
    }
}
