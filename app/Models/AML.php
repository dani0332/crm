<?php

namespace App\Models;

use App\Enums\AMLDecisionStatusEnum;
use App\Enums\AMLScreeningTypeEnum;
use App\Traits\SpatieActivityLog;
use Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AML extends Model implements AuditableContract
{
    use Auditable, HasFactory, SpatieActivityLog;

    protected $table = 'kyc_logs';

    /**
     * Scope to exclude RYU decision records (includes NULL records).
     * Use this when you want to filter out RYU but keep records with no decision.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeExcludeRyuDecision($query)
    {
        return $query->where(function ($ryuFilter) {
            $ryuFilter->whereNotIn('decision', [AMLDecisionStatusEnum::RYU]);
            $ryuFilter->orWhereNull('decision');
        });
    }

    /**
     * Scope to exclude RYU decision records (also excludes NULL records).
     * Use this when you want to filter out RYU AND records with no decision.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeExcludeRyuDecisionStrict($query)
    {
        return $query->where('decision', '!=', AMLDecisionStatusEnum::RYU);
    }

    /**
     * Scope to exclude insurer AML screening types (AXA, RSA).
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeExcludeInsurerScreening($query)
    {
        return $query->where(function ($aml) {
            $aml->whereNotIn('screening_type', [AMLScreeningTypeEnum::INSURER_AXA, AMLScreeningTypeEnum::INSURER_RSA]);
            $aml->orWhereNull('screening_type');
        });
    }

    /**
     * Scope to exclude records with screenshot data.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeExcludeScreenshot($query)
    {
        return $query->where(function ($screenshotFilter) {
            $screenshotFilter->whereNull('screenshot');
            $screenshotFilter->orWhere('screenshot', '');
        });
    }

    /**
     * Scope to apply all standard AML screening filters.
     * Combines exclusions for RYU decisions, insurer screening, and screenshots.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeStandardAmlFilters($query)
    {
        return $query->excludeRyuDecision()
            ->excludeInsurerScreening()
            ->excludeScreenshot();
    }

    public function quotetype()
    {
        return $this->belongsTo(QuoteType::class, 'quote_type_id', 'id');
    }

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
}
