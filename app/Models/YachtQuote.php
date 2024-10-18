<?php

namespace App\Models;

use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class YachtQuote extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'yacht_quote_request';
    protected $guarded = [];
    public $allowedColumns = ['boat_details', 'engine_details', 'claim_experience', 'use', 'operator_experience'];

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    /**
     * @return array
     */
    public function getAuditables()
    {
        return [
            'auditable_type' => PersonalQuote::class,
            'relations' => [
                ['auditable_type' => PersonalQuoteDetail::class, 'key' => 'personal_quote_id'],
                ['auditable_type' => YachtQuote::class, 'key' => 'personal_quote_id'],
            ],
        ];
    }

    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id', 'id');
    }

    public function yachtQuoteRequestDetail()
    {
        return $this->hasOne(YachtQuoteRequestDetail::class, 'yacht_quote_request_id', 'id');
    }

    public function allowedColumns()
    {
        return $this->allowedColumns;
    }

    public function sageApiLogs()
    {
        return $this->morphMany(SageApiLog::class, 'section');
    }

    public function advisor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'advisor_id')->select(['id', 'email', 'name', 'mobile_no', 'landline_no', 'profile_photo_path', 'calendar_link']);
    }
}
