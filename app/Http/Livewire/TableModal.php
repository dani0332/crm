<?php

namespace App\Http\Livewire;

use Livewire\Component;

class TableModal extends Component
{
    public $show = false;
    public $advisorId;
    public $leadType;
    public $startDate;
    public $endDate;
    public $createdAtFilter;
    public $ecommerceFilter;
    public $excludeCreatedLeadsFilter;
    public $batchNumberFilter;
    public $tiersFilter;
    public $leadSourceFilter;
    public $teamsFilter;
    public $advisorsFilter;
    protected $listeners = [
        'show' => 'show',
    ];

    public function show($data, $filters, $leadType)
    {
        if (array_key_exists('created_at', $filters)) {
            $this->createdAtFilter = str_replace('~', '|', $filters['created_at']);
        }
        if (array_key_exists('ecommerce', $filters)) {
            $this->ecommerceFilter = $filters['ecommerce'];
        }
        if (array_key_exists('exclude_created_leads', $filters)) {
            $this->excludeCreatedLeadsFilter = $filters['exclude_created_leads'];
        }
        if (array_key_exists('batch_number', $filters)) {
            $this->batchNumberFilter = $filters['batch_number'];
        }
        if (array_key_exists('tiers', $filters)) {
            $this->tiersFilter = $filters['tiers'];
        }
        if (array_key_exists('lead_source', $filters)) {
            $this->leadSourceFilter = $filters['lead_source'];
        }
        if (array_key_exists('teams', $filters)) {
            $this->teamsFilter = $filters['teams'];
        }
        if (array_key_exists('advisors', $filters)) {
            $this->advisorsFilter = $filters['advisors'];
        }
        info('lead type : '.$leadType);
        $this->advisorId = $data['advisorId'];
        $this->startDate = $data['batch.start_date'];
        $this->endDate = $data['batch.end_date'];
        $this->leadType = $leadType;
        $this->show = true;
    }

    public function render()
    {
        return view('livewire.table-modal');
    }
}
