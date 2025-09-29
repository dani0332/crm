<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\QuoteTypes;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerVerificationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data;

    public function __construct(string $quoteUuid, bool $verificationSuccess, string $quoteType = null)
    {
        $this->data = [
            'quoteUuid' => $quoteUuid,
            'quoteType' => $quoteType ?? QuoteTypes::CAR->value,
            'verificationSuccess' => $verificationSuccess,
            'timestamp' => now()->toISOString(),
            'message' => 'Customer verification status updated',
        ];
    }

    public function broadcastOn()
    {
        return ['public.'.config('constants.APP_ENV').'.customer.verification'];
    }

    public function broadcastAs()
    {
        return 'customer.verification.updated';
    }

    public function broadcastWith()
    {
        return $this->data;
    }
}
