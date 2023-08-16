<?php

namespace App\Events;

use App\Models\CarQuote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CarAdvisorUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $model;

    public function __construct(CarQuote $model)
    {
        $this->model = $model;
    }
}
