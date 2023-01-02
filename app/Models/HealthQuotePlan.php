<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthQuotePlan extends Model
{
    use HasFactory;

    protected $table = 'health_quote_plans';
    protected $guarded = ['id'];
    protected $casts = [
        'plan_payload' => 'json',
    ];

    public function healthQuote()
    {
        return $this->belongsTo(HealthQuote::class, 'health_quote_id', 'id');
    }

    public function planPayload()
    {
        return $this->plan_payload['plans'];
    }

    public function getPlanNameAttribute()
    {
        return $this->plan_payload['plans'][0]['providerName'];
    }
}
