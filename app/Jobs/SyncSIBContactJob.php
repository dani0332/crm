<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\quoteTypeCode;
use App\Services\ApplicationStorageService;
use App\Services\SIBService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncSIBContactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 300;
    protected $entity = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ApplicationStorageService $appStorageService)
    {
        if (! $this->entity) {
            return false;
        }
        $isCarQuote = str_contains($this->entity->code, strtoupper(quoteTypeCode::Car));
        $data = [
            'customerName' => isset($this->entity->full_name) ? $this->entity->full_name : null,
            'advisorName' => isset($this->entity->advisor) ? $this->entity->advisor->name : null,
            'advisorEmail' => isset($this->entity->advisor) ? $this->entity->advisor->email : null,
            'advisorMobile' => isset($this->entity->advisor) ? $this->entity->advisor->mobile_no : null,
            'customerLastName' => isset($this->entity->last_name) ? $this->entity->last_name : null,
            'customerFirstName' => isset($this->entity->first_name) ? $this->entity->first_name : null,
            'leadStatus' => isset($this->entity->quoteStatus) ? $this->entity->quoteStatus->text : null,
            'cdbid' => isset($this->entity->code) ? $this->entity->code : null,
            'link' => config('constants.ECOM_'.($isCarQuote ? 'CAR' : 'HEALTH').'_INSURANCE_QUOTE_URL').$this->entity->uuid,
            'advisorLandline' => isset($this->entity->advisor) ? $this->entity->advisor->landline_no : null,
        ];
        $listId = $isCarQuote ? $appStorageService->getValueByKey(ApplicationStorageEnums::SIB_CAR_DRIP_LIST_ID) : $appStorageService->getValueByKey(ApplicationStorageEnums::SIB_HEALTH_EBP_LIST_ID);
        info('going to create or update contact on sib for list id : '.$listId);

        return SIBService::contactCreateUpdate($listId, $this->entity->first_name, $this->entity->last_name, $this->entity->email, null, $data);
    }
}
