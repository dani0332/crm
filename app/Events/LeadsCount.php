<?php

namespace App\Events;

use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Models\BusinessQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\PersonalQuote;
use Faker\Provider\ar_EG\Person;
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
        $getRoleByQuoteTypeId = [
            QuoteTypeId::Pet => RolesEnum::PetAdvisor,
            QuoteTypeId::Cycle => RolesEnum::CycleAdvisor,
            QuoteTypeId::Yacht => RolesEnum::YachtAdvisor,
            QuoteTypeId::Home => RolesEnum::HomeAdvisor,
            QuoteTypeId::Health => RolesEnum::HealthAdvisor,
            QuoteTypeId::Business => RolesEnum::BusinessAdvisor,
        ];

        $checkCorplineAdvisor = [
            auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::CORPLINE),
            auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Business),
            auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::Amt),
            auth()->user()->isSpecificTeamAdvisor(quoteTypeCode::GM),
        ];

        // Todo:: Need to update query as per list view
        switch ($modelObject) {
            case PersonalQuote::class:
                $totalLeadsCount = $modelObject::where('quote_type_id', $quoteTypeId)
                ->withFakeLeadCriteria()
                ->when(\auth()->user()->hasRole($getRoleByQuoteTypeId[$quoteTypeId]), function ($query) {
                    $query->where('advisor_id', \auth()->user()->id);
                })
                ->count();
                break;

            case HomeQuote::class:
            case HealthQuote::class:
                $totalLeadsCount = $modelObject::withFakeLeadCriteria()
                ->when(\auth()->user()->hasRole($getRoleByQuoteTypeId[$quoteTypeId]), function ($query) {
                    $query->where('advisor_id', \auth()->user()->id);
                })
                ->count();
                break;

            case BusinessQuote::class:
                $totalLeadsCount = $modelObject::withFakeLeadCriteria()
                ->whereNot('business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical))
                ->when(in_array(true, $checkCorplineAdvisor), function ($query) {
                    $query->where('advisor_id', auth()->user()->id);
                })
                ->count();
                break;
            
            default:
                $totalLeadsCount = $totalLeadsCount;
                break;
        }

        return $totalLeadsCount;
    }
}
