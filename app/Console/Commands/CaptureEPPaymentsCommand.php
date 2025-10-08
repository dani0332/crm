<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\PaymentGatewayIdEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
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
        $quoteTypes = ['car', 'travel', 'personal'];
        foreach ($quoteTypes as $quoteType) {
            try {
                $this->processEPPayments($quoteType);
            } catch (Exception $e) {
                LoggerService::error('CaptureEPPaymentsCommand - Failed to process quotes', extra: [
                    'quoteType' => $quoteType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function processEPPayments(string $quoteType)
    {
        $modelMap = [
            'car' => [
                'model' => CarQuote::class,
                'quoteTypeCode' => quoteTypeCode::Car,
                'additionalConditions' => null,
            ],
            'travel' => [
                'model' => TravelQuote::class,
                'quoteTypeCode' => quoteTypeCode::Travel,
                'additionalConditions' => null,
            ],
            'personal' => [
                'model' => PersonalQuote::class,
                'quoteTypeCode' => null, // Will be determined by quote_type_id
                'additionalConditions' => function ($query) {
                    $query->whereIn('quote_type_id', [QuoteTypeId::Home, QuoteTypeId::Bike]);
                },
            ],
        ];

        $config = $modelMap[$quoteType];
        $model = $config['model'];

        $query = $model::select('id', 'code', 'quote_status_id')
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
            ->where('quote_status_id', QuoteStatusEnum::PolicyBooked)
            ->whereHas('embeddedTransactions', function ($query) {
                $query->where('payment_status_id', PaymentStatusEnum::AUTHORISED);
            })
            ->whereHas('embeddedTransactions.payments', function ($query) {
                $query->where('payment_gateway_id', PaymentGatewayIdEnum::PAYMENT_GATEWAY_TAP);
            });

        // Add quote_type_id for personal quotes
        if ($quoteType === 'personal') {
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
                    $quoteTypeCode = $quoteType === 'personal'
                        ? QuoteTypes::getName($lead->quote_type_id)->value
                        : $config['quoteTypeCode'];

                    EmbeddedProductRepository::capturePayment($lead->id, $quoteTypeCode);
                } catch (Exception $e) {
                    LoggerService::error('CaptureEPPaymentsCommand - capture embedded products failed', [
                        'error' => $e->getMessage(),
                        'uuid' => $lead->code,
                    ]);
                }
            }
        }
    }
}
