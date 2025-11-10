<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CyberQuote extends Model
{
    //
    protected $table = 'cyber_quote_request';
    protected $fillable = [];
    protected $guarded = [];

    public function personalQuote()
    {
        return $this->belongsTo(PersonalQuote::class, 'personal_quote_id', 'id');
    }

    /**
     * Check if booking has failed
     *
     * @return bool
     */
    public function isBookingFailed()
    {
        return $this->insurer_api_status_id === \App\Enums\PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID;
    }

    /**
     * Check if policy issuance has failed
     *
     * @return bool
     */
    public function isPolicyIssuanceFailed()
    {
        return in_array($this->insurer_api_status_id, app(\App\Services\PolicyIssuanceAutomation\PolicyIssuanceService::class)->getInsurerAPIStatuses(null, true));
    }
}
