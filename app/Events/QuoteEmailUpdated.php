<?php

namespace App\Events;

use App\Models\CarQuote;
use Illuminate\Queue\SerializesModels;

class QuoteEmailUpdated
{
    use SerializesModels;

    public $quote;

    /**
     * Create a new event instance.
     */
    public function __construct($quote)
    {
        $this->quote = $quote;
    }
}
