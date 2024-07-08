<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Models\CarQuote;
use App\Models\HomeQuote;
use App\Models\HealthQuote;
use App\Models\LifeQuote;
use App\Models\BusinessQuote;
use App\Models\BikeQuote;
use App\Models\YachtQuote;
use App\Models\TravelQuote;
use App\Models\PetQuote;
use App\Models\PersonalQuote;
use Exception;

class SyncCustomerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;
    public $timeout = 30;
    public $backoff = 300;
    private $newCustomerId;
    private $previousEmail;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($newCustomerId, $previousEmail)
    {
        $this->newCustomerId = $newCustomerId;
        $this->previousEmail = $previousEmail;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(empty($this->newCustomerId) || empty($this->previousEmail)) {
            info('SyncCustomerJob - missing data ' . $this->newCustomerId . ' - ' . $this->previousEmail);
            return;

        }

        info('----------- SyncCustomerJob Started ----------- ' . $this->newCustomerId . ' - ' . $this->previousEmail);
        $modelClasses = $this->getQuoteModels();
        foreach ($modelClasses as $modelClass) {
            try {
                $updateCount = $modelClass::where('email', $this->previousEmail)->update(['customer_id' => $this->newCustomerId]);
                info('SyncCustomerJob - Updated ' . $updateCount . ' entries in ' . $modelClass . ' for ' . $this->previousEmail . ' - new customer id - ' . $this->newCustomerId);
            } catch (Exception $e) {
                $error = 'SyncCustomerJob Error syncing entry: ' . $this->newCustomerId . ' - ' . $this->previousEmail . ' - ' . $modelClass . ' - ' . $e->getMessage();
                info($error . ' --- ' . $e->getTraceAsString());
            }
        }

        info('----------- SyncCustomerJob Completed ----------- ' . $this->newCustomerId . ' - ' . $this->previousEmail);
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->newCustomerId))->dontRelease()];
    }

    private function getQuoteModels()
    {
        return [
            CarQuote::class,
            HomeQuote::class,
            HealthQuote::class,
            LifeQuote::class,
            BusinessQuote::class,
            BikeQuote::class,
            YachtQuote::class,
            TravelQuote::class,
            PetQuote::class,
            PersonalQuote::class,
        ];
    }
}
