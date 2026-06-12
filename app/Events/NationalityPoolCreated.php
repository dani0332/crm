<?php

namespace App\Events;

use App\Models\NationalityPool;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NationalityPoolCreated
{
    use Dispatchable,SerializesModels;

    public $pool;

    /**
     * Create a new event instance.
     */
    public function __construct(NationalityPool $pool)
    {
        $this->pool = $pool;
    }
}
