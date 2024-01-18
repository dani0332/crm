<?php

namespace App\Events;

use App\Enums\quoteBusinessTypeCode;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadsCount implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $modelObject;
    private $quoteType;

    /**
     * Create a new event instance.
     */
    public function __construct($modelObject, $quoteType)
    {
        $this->modelObject = $modelObject;
        $this->quoteType = $quoteType;
    }
    
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return ['public.'.config('constants.APP_ENV').'.total-leads-count'];
    }
    
    public function broadcastAs(): string
    {
        return 'leads.count';
    }

    public function broadcastWith(): array
    {
        return [
            'totalLeadsCount' => $this->getTotalLeadsCount($this->modelObject, $this->quoteType),
        ];
    }

    private function getTotalLeadsCount($modelObject, $quoteTypeId)
    {
        $totalLeadsCount = 0;

        // Todo:: Need to update query as per list view
        switch ($modelObject) {
            case 'App\Models\PersonalQuote':
                $totalLeadsCount = $modelObject::where('quote_type_id', $quoteTypeId)->count();
                break;

            case 'App\Models\HomeQuote':
            case 'App\Models\HealthQuote':
                $totalLeadsCount = $modelObject::count();
                break;

            case 'App\Models\BusinessQuote':
                $totalLeadsCount = $modelObject::whereNot('business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical))->count();
                break;
            
            default:
                $totalLeadsCount = $totalLeadsCount;
                break;
        }

        return $totalLeadsCount;
    }
}
