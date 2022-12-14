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
    protected $listeners = [
        'show' => 'show',
    ];

    public function show($data)
    {
        $this->advisorId = $data[0]['advisorId'];
        $this->startDate = $data[0]['batch.start_date'];
        $this->endDate = $data[0]['batch.end_date'];
        $this->leadType = $data[1];
        $this->show = true;
    }

    public function render()
    {
        return view('livewire.table-modal');
    }
}
