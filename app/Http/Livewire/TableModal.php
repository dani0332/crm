<?php

namespace App\Http\Livewire;

use Livewire\Component;

class TableModal extends Component
{
    public bool $show = false;
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

    public function show($data, $filters = [])
    {
        info($data);
        info($filters);
        $this->advisorId = $data[0]['advisorId'];
        $this->startDate = $data[0]['batch.start_date'];
        $this->endDate = $data[0]['batch.end_date'];
        $this->leadType = $data[1];
        $this->createdAtFilter = $data[2];
        $this->excludeCreatedLeadsFilter = $data[3];
        $this->ecommerceFilter = $data[4];
        $this->batchNumberFilter = $data[5];
        $this->tiersFilter = $data[6];
        $this->leadSourceFilter = $data[7];
        $this->teamsFilter = $data[8];
        $this->advisorsFilter = $data[9];
        $this->show = true;
    }

    public function render()
    {
        return view('livewire.table-modal');
    }
}
