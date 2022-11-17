<?php

namespace App\Http\Livewire;

use Livewire\Component;

class TableModal extends Component
{
    public bool $show = false;
    protected $listeners = [
        'show' => 'show',
    ];

    public function show($totalLeadCount)
    {
        $this->show = true;
    }

    public function loadTable($id)
    {
        $this->emit('loadTable', $id);
    }

    public function render()
    {
        return view('livewire.table-modal');
    }
}
