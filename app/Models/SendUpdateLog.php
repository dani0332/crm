<?php

namespace App\Models;

use App\Enums\SendUpdateLogStatusEnum;
use App\Traits\SpatieActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SendUpdateLog extends Model implements AuditableContract
{
    use Auditable, HasFactory, SpatieActivityLog;

    protected $guarded = [];
    protected $casts = [
        'car_addons' => 'json',
    ];
    protected $appends = ['display_status'];
    /*
     * it will convert BOOK-UPDATE to Book Update.
     */
    public function getDisplayStatusAttribute(): string
    {
        return ucwords(str_replace('_', ' ', strtolower($this->status)));
    }

    public function quoteType(): BelongsTo
    {
        return $this->belongsTo(QuoteType::class, 'quote_type_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'category_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'option_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function sageApiLogs()
    {
        return $this->morphMany(SageApiLog::class, 'section');
    }

    public function emirates(): BelongsTo
    {
        return $this->belongsTo(Emirate::class, 'emirates_id');
    }

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id', 'id')->select(['id', 'text', 'code']);
    }

    public function travelPlan(): BelongsTo
    {
        return $this->belongsTo(TravelPlan::class, 'plan_id', 'id')->select(['id', 'text', 'code']);
    }

    public function getAuditables()
    {
        return [
            'auditable_type' => self::class,
        ];
    }
    public function isUpdateBooked(): bool
    {
        return $this->status == SendUpdateLogStatusEnum::UPDATE_BOOKED;
    }

    public function isEndorsementFinancial()
    {
        return $this->category->code == SendUpdateLogStatusEnum::EF;
    }

    public function isPolicyPeriodExtension()
    {
        return $this->option->code == SendUpdateLogStatusEnum::PPE;
    }
    public function personalQuote()
    {
        return $this->belongsTo(PersonalQuote::class, 'personal_quote_id', 'id');
    }

    public function epLogs(): MorphMany
    {
        return $this->morphMany(EpLog::class, 'loggable');
    }

    public function sendUpdateStatusLogs(): HasMany
    {
        return $this->hasMany(SendUpdateStatusLog::class, 'send_update_log_id');
    }
}
