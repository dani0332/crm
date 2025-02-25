<?php

namespace App\Jobs;

use App\Models\CustomerAddress;
use App\Services\HomeQuoteService;
use App\Services\Logger\LoggerService;
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
        LoggerService::startQuoteLogging($this->quoteUID);
        info('SaveCustomerAddressJob started');

        try {
            // Validate address data
            if (empty($this->address)) {
                throw new InvalidArgumentException('Address data is empty.');
            }

            info('Attempting to save the customer address');

            $quoteData = app(HomeQuoteService::class)->getQuoteData($this->quoteUID);

            if (! $quoteData) {
                info('Quote data not found');

                return;
            }

            $customerId = $quoteData->customer_id;
            $subArea = $quoteData->homeQuote->subArea ?? null;

            if (! $subArea) {
                throw new ModelNotFoundException('SubArea not found');
            }

            if (! $subArea->emirate) {
                throw new ModelNotFoundException("Emirate not found for subArea: {$subArea->id}");
            }

            // Format the address data
            $customerAddress = app(HomeQuoteService::class)->formatAddress($customerId, $subArea, $this->quoteUID, $this->address);

            if (empty($customerAddress)) {
                throw new InvalidArgumentException('Formatted address data is empty.');
            }

            // Check if a CustomerAddress record already exists
            $customerAddressRecord = CustomerAddress::where('customer_id', $customerId)
                ->where('quote_uuid', $this->quoteUID)
                ->first();

            // Create or update the customer address
            if ($customerAddressRecord) {
                $customerAddressRecord->update($customerAddress);
                info('Customer address updated successfully');
            } else {
                CustomerAddress::create($customerAddress);
                info('Customer address created successfully');
            }
        } catch (InvalidArgumentException $e) {
            info('Invalid address data', [
                'error' => $e->getMessage(),
            ]);
        } catch (ModelNotFoundException $e) {
            info('Quote data not found', [
                'error' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            info('Unexpected error saving customer address', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        LoggerService::endLogging();
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
