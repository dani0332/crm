<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PaymentStatusLog extends Model
{
    protected $table = 'payment_status_log';
    protected $fillable = ['current_payment_status_id', 'payment_code', 'created_at', 'updated_at', 'previous_payment_status_id'];

    public function payment()
    {
        return $this->belongsTo('App\Models\Payment', 'code', 'payment_code');
    }

    public function getCreatedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::parse($date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
