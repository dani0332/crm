<?php

namespace App\Events\Axiom;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FlushAxiomBatch
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
}
