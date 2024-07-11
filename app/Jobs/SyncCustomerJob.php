<?php

namespace App\Jobs;

use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\TravelQuote;
use App\Models\YachtQuote;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use OwenIt\Auditing\Models\Audit;

class SyncCustomerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;
    private $newCustomerId;
    private $email;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($newCustomerId, $email)
    {
        $this->newCustomerId = $newCustomerId;
        $this->email = $email;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (empty($this->newCustomerId) || empty($this->email)) {
            info('SyncCustomerJob - missing data '.$this->newCustomerId.' - '.$this->email);

            return;

        }

        info('----------- SyncCustomerJob Started ----------- '.$this->newCustomerId.' - '.$this->email);
        $modelClasses = $this->getQuoteModels();
        foreach ($modelClasses as $modelClass) {
            try {

                $modelClass::where('email', $this->email)
                    ->chunk(1000, function ($entries) use ($modelClass) {
                        $auditsToCreate = [];
                        $entriesToSkip = [];

                        foreach ($entries as $entry) {
                            if ($entry->customer_id == $this->newCustomerId) {
                                $entriesToSkip[] = $entry->id;

                                continue;
                            }

                            $auditsToCreate[] = [
                                'event' => 'updated',
                                'auditable_type' => $modelClass,
                                'auditable_id' => $entry->id,
                                'old_values' => json_encode(['customer_id' => $entry->customer_id]),
                                'new_values' => json_encode(['customer_id' => $this->newCustomerId]),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }

                        // update quote classes
                        $entryIds = $entries->pluck('id')->diff($entriesToSkip)->toArray();
                        $modelClass::whereIn('id', $entryIds)->update(['customer_id' => $this->newCustomerId]);

                        // insert audits
                        Audit::insert($auditsToCreate);

                        info('SyncCustomerJob - Updated '.count($entryIds).' entries in '.$modelClass.' for '.$this->email.' - new customer id - '.$this->newCustomerId);
                    });

            } catch (Exception $e) {
                $error = 'SyncCustomerJob Error syncing entry: '.$this->newCustomerId.' - '.$this->email.' - '.$modelClass.' - '.$e->getMessage();
                info($error.' --- '.$e->getTraceAsString());
            }
        }

        info('----------- SyncCustomerJob Completed ----------- '.$this->newCustomerId.' - '.$this->email);
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
