<?php

namespace App\Jobs;

use App\Models\CustomerAddress;
use App\Services\HomeQuoteService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;

class SaveCustomerAddressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;
    public $quoteUID;
    public $address;

    /**
     * Create a new job instance.
     */
    public function __construct(string $quoteUID, array $address)
    {
        $this->quoteUID = $quoteUID;
        $this->address = $address;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        info('SaveCustomerAddressJob started', ['quoteUID' => $this->quoteUID]);

        try {
            info('Attempting to save the customer address', ['quoteUID' => $this->quoteUID]);

            $quoteData = app(HomeQuoteService::class)->getQuoteData($this->quoteUID);

            if ($quoteData) {
                $customerId = $quoteData->customer_id;
                $subArea = $quoteData->homeQuote->subArea ?? null;

                if (! $subArea) {
                    throw new ModelNotFoundException("SubArea not found for quote: {$this->quoteUID}");
                }

                if (! $subArea->emirate) {
                    throw new ModelNotFoundException("Emirate not found for subArea: {$subArea->id}");
                }

                // Format the address data
                $custmerAddress = app(HomeQuoteService::class)->formatAddress($customerId, $subArea, $this->quoteUID, $this->address);

                if (empty($custmerAddress)) {
                    throw new InvalidArgumentException('Formatted address data is empty.');
                }

                // Save the customer address
                CustomerAddress::create($custmerAddress);

                info('Customer address saved successfully', ['quoteUID' => $this->quoteUID]);
            } else {
                info('Quote data not found', ['quoteUID' => $this->quoteUID]);
            }
        } catch (InvalidArgumentException $e) {
            info('Invalid address data', [
                'quoteUID' => $this->quoteUID,
                'error' => $e->getMessage(),
            ]);
        } catch (ModelNotFoundException $e) {
            info('Quote data not found', [
                'quoteUID' => $this->quoteUID,
                'error' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            info('Unexpected error saving customer address', [
                'quoteUID' => $this->quoteUID,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(Exception $exception): void
    {
        // Log the failure
        info('SaveCustomerAddressJob failed', [
            'quoteUID' => $this->quoteUID,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
