<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\QuoteFlowType;

class QuoteFlowDetails extends Model
{
    use HasFactory;
    protected $table = 'quotes_flow_details';

    protected $guarded = [];
    protected $casts = [
        'flow_type' => QuoteFlowType::class, // Cast the flow_type to the FlowType enum
    ];

    // Accessor to get the flow type label
    public function getFlowTypeLabelAttribute(): string
    {
        return $this->flow_type->label();
    }
}
