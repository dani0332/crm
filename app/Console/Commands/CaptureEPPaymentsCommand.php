<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\PaymentGatewayIdEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use App\Repositories\EmbeddedProductRepository;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CaptureEPPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ep:capture-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'For booked leads, capture the authorised EP payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        LoggerService::info('CaptureEPPaymentsCommand Started');
        $isAutoCaptureEPPaymentsEnabled = ApplicationStorage::where('key_name', ApplicationStorageEnums::ENABLE_AUTO_CAPTURE_EP_PAYMENTS)->first();

        if (! $isAutoCaptureEPPaymentsEnabled || $isAutoCaptureEPPaymentsEnabled->value == 0) {
            LoggerService::info('CaptureEPPaymentsCommand is disabled');

            return;
        }

        // Process each quote type
        $quoteTypes = [QuoteTypes::CAR->value, QuoteTypes::TRAVEL->value, QuoteTypes::PERSONAL->value];
        foreach ($quoteTypes as $quoteType) {
            try {
                $this->processEPPayments($quoteType);
            } catch (Exception $e) {
                LoggerService::error('CaptureEPPaymentsCommand - Failed to process quotes', extra: [
                    'quoteType' => $quoteType,
                ], exception: $e);
            }
        }
    }

    private function processEPPayments($quoteType)
    {
        $modelMap = [
            QuoteTypes::CAR->value => [
                'model' => CarQuote::class,
                'quoteTypeCode' => QuoteTypes::CAR->value,
                'additionalConditions' => null,
            ],
            QuoteTypes::TRAVEL->value => [
                'model' => TravelQuote::class,
                'quoteTypeCode' => QuoteTypes::TRAVEL->value,
                'additionalConditions' => null,
            ],
            QuoteTypes::PERSONAL->value => [
                'model' => PersonalQuote::class,
                'quoteTypeCode' => null, // Will be determined by quote_type_id
                'additionalConditions' => function ($query) {
                    $query->whereIn('quote_type_id', [QuoteTypeId::Home, QuoteTypeId::Bike]);
                },
            ],
        ];

        // Retrieve the configuration for the current quote type from the model map
        $config = $modelMap[$quoteType];

        // Get the Eloquent model class associated with the quote type
        $model = $config['model'];

        $bookingDays = ApplicationStorage::where('key_name', ApplicationStorageEnums::AUTO_CAPTURE_EP_PAYMENTS_BOOKING_DAYS)->first();
        $bookingDays = $bookingDays->value ?? 7;
        $bookingDate = Carbon::now()->subDays((int) $bookingDays)->format('Y-m-d 00:00:00');

        $query = $model::select('id', 'code', 'quote_status_id', 'policy_booking_date')
            ->with([
                'embeddedTransactions' => function ($query) {
                    $query->select('id', 'code', 'quote_request_id', 'quote_request_type', 'payment_status_id')
                        ->where('payment_status_id', PaymentStatusEnum::AUTHORISED);
                },
                'embeddedTransactions.payments' => function ($query) {
                    $query->select('id', 'paymentable_id', 'paymentable_type', 'payment_gateway_id')
                        ->where('payment_gateway_id', PaymentGatewayIdEnum::PAYMENT_GATEWAY_TAP);
                },
            ])
            ->where('policy_booking_date', '>=', $bookingDate)
            ->where('quote_status_id', QuoteStatusEnum::PolicyBooked)
            ->whereHas('embeddedTransactions', function ($query) {
                $query->where('payment_status_id', PaymentStatusEnum::AUTHORISED);
            })
            ->whereHas('embeddedTransactions.payments', function ($query) {
                $query->where('payment_gateway_id', PaymentGatewayIdEnum::PAYMENT_GATEWAY_TAP);
            });

        // Add quote_type_id for personal quotes
        if ($quoteType === QuoteTypes::PERSONAL->value) {
            $query->addSelect('quote_type_id');
        }

        // Apply additional conditions if any
        $additionalConditions = $config['additionalConditions'] ?? null;
        if ($additionalConditions instanceof \Closure) {
            $additionalConditions($query);
        }

        $leads = $query->get();

        if ($leads->count()) {
            foreach ($leads as $lead) {
                $epsCodes = $lead->embeddedTransactions->pluck('code')->toArray();
                LoggerService::info('CaptureEPPaymentsCommand - capturing embedded product', extra: [
                    'leadId' => $lead->id,
                    'leadCode' => $lead->code,
                    'epsCodes' => $epsCodes,
                ]);
                try {
                    $quoteTypeCode = $quoteType === QuoteTypes::PERSONAL->value
                        ? QuoteTypes::getName($lead->quote_type_id)->value
                        : $config['quoteTypeCode'];

                    EmbeddedProductRepository::capturePayment($lead->id, $quoteTypeCode);
                } catch (Exception $e) {
                    LoggerService::error('CaptureEPPaymentsCommand - capture embedded products failed', extra: [
                        'code' => $lead->code,
                    ], exception: $e);
                }
            }
        }
    }
}
