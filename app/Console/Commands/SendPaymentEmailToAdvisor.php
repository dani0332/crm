<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\PaymentStatusEnum;
use App\Jobs\AdvisorPaymentNotificationJob;
use App\Models\Payment;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SendPaymentEmailToAdvisor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-payment-email-to-advisor:cron';

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
        $startTime = microtime(true);

        // Check if the advisor email notification is enabled for authorised payments.
        if (! getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_PAYMENT_NOTIFICATION_EMAIL_TO_ADVISOR, false, true)) {
            LoggerService::info('ENABLE_PAYMENT_NOTIFICATION_EMAIL_TO_ADVISOR is Disable');

            return Command::SUCCESS;
        }

        $authorizedDays = getAppStorageValueByKey(ApplicationStorageEnums::ADVISOR_AUTHORISED_PAYMENT_NOTIFICATION_DAYS, 1, true);

        $advisorWisePayments = $this->getAdvisorWisePayments($authorizedDays);

        if ($advisorWisePayments->isEmpty()) {
            LoggerService::info('No advisor found to send payment email.');

            return Command::SUCCESS;
        }

        LoggerService::info($advisorWisePayments->count().' advisor wise payments found.');

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

        return Command::SUCCESS;
    }

    private function getAdvisorWisePayments($authorizedDays): Collection
    {
        $authorizedDate = Carbon::today()->subDays($authorizedDays);

        return Payment::with([
            'personalQuote:id,code,advisor_id,premium',
            'personalQuote.advisor:id,name,email',
        ])
            ->where('payments.payment_status_id', PaymentStatusEnum::AUTHORISED)
            ->whereDate('payments.authorized_at', '>=', $authorizedDate)
            ->get()
            ->filter(fn ($payment) => $payment->personalQuote !== null)
            ->groupBy(fn ($payment) => $payment->personalQuote->advisor_id)
            ->map(function ($advisorPayments, $advisorId) use ($authorizedDate) {
                $advisor = $advisorPayments?->first()?->personalQuote?->advisor;

                return [
                    'advisorId' => $advisorId,
                    'advisorName' => $advisor?->name,
                    'advisorEmail' => $advisor?->email,
                    'totalLeads' => (string) $advisorPayments->count(),
                    'totalExpiringLeads' => (string) $advisorPayments->filter(
                        fn ($p) => Carbon::parse($p->getRawOriginal('authorized_at'))->toDateString() === $authorizedDate->toDateString()
                    )->count(),
                    'totalPremium' => (string) $advisorPayments->sum(fn ($p) => (float) $p->personalQuote->premium),
                ];
            })
            ->values();
    }
}
