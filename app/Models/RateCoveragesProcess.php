<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateCoveragesProcess extends Model
{
    use HasFactory;

    protected $fillable = ['rate_coverage_id', 'type', 'data', 'validation_errors'];
    protected $table = 'rate_coverage_processes';
    protected $casts = [
        'data' => 'array',
        'validation_errors' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    //    public function renewalUploadLead()
    //    {
    //        return $this->belongsTo(RenewalsUploadLeads::class, 'renewals_upload_lead_id');
    //    }

    /**
     * json encode data.
     * todo: fix later as its not preserving order
     *
     * @return void
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode(json_encode($value));
    }

    /**
     * @return mixed
     */
    public function getDataAttribute($data)
    {
        return json_decode(json_decode($data, true), true);
    }

    /**
     * json encode validation_errors.
     *
     * @return void
     */
    public function setValidationErrorsAttribute($value)
    {
        $this->attributes['validation_errors'] = json_encode($value);
    }
}
