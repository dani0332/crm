<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CycleQuote extends Model
{
    use HasFactory;

    protected $table = 'cycle_quote_request';
    protected $fillable = ['cycle_make', 'cycle_model', 'year_of_manufacture_id', 'accessories', 'has_accident', 'has_good_condition'];
    public $allowedColumns = ['cycle_make', 'cycle_model', 'year_of_manufacture_id', 'accessories', 'has_accident', 'has_good_condition'];

    /**
     * @return array
     */
    public function getAuditables()
    {
        return [
            'auditable_type' => PersonalQuote::class,
            'relations' => [
                ['auditable_type' => PersonalQuoteDetail::class, 'key' => 'personal_quote_id'],
                ['auditable_type' => CycleQuote::class, 'key' => 'personal_quote_id'],
            ],
        ];
    }

    public function yearOfManufacture()
    {
        return $this->belongsTo(YearOfManufacture::class);
    }

    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id', 'id');
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
