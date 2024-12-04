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
        're_assigned_at',
        're_assigned_to',
        're_assignment_reason',
    ];

    public function request()
    {
        return $this->belongsTo(BuyLeadRequest::class);
    }

    public static function reAssign($quoteTypeId, $lead, $newAdvisorId, $previousAdvisorId)
    {
        $buyLeadRequestLog = BuyLeadRequestLog::select('buy_lead_request_logs.id as logId')
            ->join('buy_lead_requests', 'buy_lead_requests.id', 'buy_lead_request_logs.buy_lead_request_id')
            ->where('buy_lead_requests.user_id', $previousAdvisorId)
            ->whereNull('re_assigned_at')
            ->where('buy_lead_request_logs.quote_type_id', $quoteTypeId)
            ->where('quote_id', $lead->id)
            ->first();

        if ($buyLeadRequestLog) {
            info(self::class.'::reAssign - going to update the re-assignment details in the buy_lead_request_logs table for the lead uuid: '.$lead->uuid);
            self::where([
                'id' => $buyLeadRequestLog->logId,
            ])->update([
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

    public function scopeReAssigned($query)
    {
        return $query->whereNotNull('re_assigned_at');
    }
}
