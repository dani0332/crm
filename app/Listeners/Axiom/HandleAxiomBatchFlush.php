<?php

namespace App\Listeners\Axiom;

use App\Logging\AxiomBatchHandler;
use Illuminate\Support\Facades\Log;

class HandleAxiomBatchFlush
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(): void
    {
        $logger = Log::getLogger();

        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof AxiomBatchHandler) {
                $handler->sendBatch();
            }
        }
    }
}
