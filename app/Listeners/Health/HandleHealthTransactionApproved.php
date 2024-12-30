<?php

namespace App\Listeners\Health;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Events\Health\HealthTransactionApproved;
use App\Models\ApplicationStorage;
use App\Models\HealthQuote;
use App\Models\RenewalBatch;

class HandleHealthTransactionApproved
{
    /**
     * Handles the HealthTransactionApproved event.
     *
     * @param  HealthTransactionApproved  $event  The HealthTransactionApproved event.
     */
    public function handle(HealthTransactionApproved $event): void
    {
        $healthQuote = $event->healthQuote;

        // Log the transaction approval process start
        info(self::class." - Transaction approval process started for quote uuid: {$healthQuote->uuid}");

        // Update transaction approval timestamp without triggering events
        $this->updateTransactionApprovedAt($healthQuote);

        // Check if source is ecommerce or IMCRM and assign renewal batch if applicable
        if ($this->isEcommerceOrIMCRM($healthQuote)) {
            $this->assignRenewalBatch($healthQuote);
        } else {
            info(self::class." - Non-Ecommerce/IMCRM source, no renewal batch assigned for quote uuid: {$healthQuote->uuid}");
        }
    }

    /**
     * Updates the transaction approval timestamp for the given health quote.
     *
     * @param  HealthQuote  $healthQuote  The health quote to update.
     */
    protected function updateTransactionApprovedAt(HealthQuote $healthQuote): void
    {
        $healthQuote->withoutEvents(fn () => $healthQuote->update(['transaction_approved_at' => now()]));
    }

    /**
     * Check if the health quote is from the ecommerce or IMCRM source.
     *
     * @param  HealthQuote  $healthQuote  The health quote object to check.
     * @return bool Returns true if the health quote is from the ecommerce or IMCRM source, false otherwise.
     */
    protected function isEcommerceOrIMCRM(HealthQuote $healthQuote): bool
    {
        // Get the ecommerce source from the application_storage database table
        $ecommerceSource = ApplicationStorage::where('key_name', ApplicationStorageEnums::LEAD_SOURCE_ECOMMERCE)->value('value');

        return $healthQuote->source === LeadSourceEnum::IMCRM || strpos($healthQuote->source, $ecommerceSource) !== false;
    }

    /**
     * Assigns a renewal batch to the given health quote.
     *
     * @param  HealthQuote  $healthQuote  The health quote to assign the renewal batch to.
     */
    protected function assignRenewalBatch(HealthQuote $healthQuote): void
    {
        $date = today()->toDateString();

        // Find the motor renewal batch for the current date
        $renewalBatch = RenewalBatch::whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where('quote_type_id', 1)
            ->first();

        if ($renewalBatch) {
            // Assign the renewal batch to the health quote
            $healthQuote->update(['renewal_batch' => $renewalBatch->name]);
            info(self::class." - Renewal batch assigned for quote uuid: {$healthQuote->uuid}, renewal_batch: {$renewalBatch->name}");
        } else {
            info(self::class." - No renewal batch found for the current date for quote uuid: {$healthQuote->uuid}");
        }
    }
}
