<?php

declare(strict_types=1);

namespace App\Events\Device;

use App\Models\PersonalQuote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DevicePaymentAuthorised
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public PersonalQuote $quote;

    public function __construct(PersonalQuote $quote)
    {
        $this->quote = $quote;
    }
}
