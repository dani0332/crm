<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyLeadRequestLog extends Model
{
    protected $fillable = [
        'buy_lead_request_id',
        'quote_type_id',
        'quote_id',
        'uuid',
        'cost_per_lead',
        're_assigned_at',
        're_assigned_to',
        're_assignment_reason',
    ];

    public function request()
    {
        return $this->belongsTo(BuyLeadRequest::class);
    }

    public static function reAssign($quoteTypeId, $lead, $newAdvisorId)
    {
        $buyLeadRequestLog = BuyLeadRequestLog::whereNull('re_assigned_at')->where('quote_type_id', $quoteTypeId)->where('quote_id', $lead->id)->first();
        if ($buyLeadRequestLog) {
            $buyLeadRequestLog->update([
                're_assigned_at' => now(),
                're_assigned_to' => $newAdvisorId,
                're_assignment_reason' => 'ReAssigned to new Advisor due to unavailability',
            ]);
        }
    }

    public function quoteType()
    {
        return $this->belongsTo(QuoteType::class);
    }
}
