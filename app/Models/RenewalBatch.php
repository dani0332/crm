<?php

namespace App\Models;

use App\Enums\QuoteTypes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class RenewalBatch extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    /**
     * @var mixed
     */
    const RENEWALS_VOLUME = 'Renewals Volume';

    const RENEWALS_VALUE = 'Renewals Value';
    const BDM = 'BDM';
    const SBDM = 'SBDM';

    /**
     * @var mixed
     */
    const SEGMENT_TYPE_VOLUME = 'volume';

    const SEGMENT_TYPE_VALUE = 'value';

    /**
     * Const teams list
     *
     * @var array
     */
    const RENEWAL_BATCH_TEAMS_LIST =
        [
            self::RENEWALS_VALUE,
            self::RENEWALS_VOLUME,
            self::BDM,
            self::SBDM,
        ];

    /**
     * Const segments type list
     *
     * @var array
     */
    const SGEMENT_TYPES_LIST =
        [
            self::SEGMENT_TYPE_VOLUME,
            self::SEGMENT_TYPE_VALUE,
        ];

    /**
     * Fillables array
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'month',
        'year',
        'quote_type_id',
    ];

    /**
     * RELATIONS
     */

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function deadline()
    {
        return $this->hasOne(RenewalBatchDeadline::class);
    }

    /**
     * get renewal batch team wise slabs function
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(
            Team::class,
            'renewal_batch_slabs',
            'renewal_batch_id',
            'team_id',
            'id',
            'id',
            'slabs'
        )->withTimestamps()->withPivot('slab_id', 'max', 'min');
    }

    /**
     * get renewal batch slab wise teams function
     */
    public function slabs(): BelongsToMany
    {
        return $this->belongsToMany(
            Slab::class,
            'renewal_batch_slabs',
            'renewal_batch_id',
            'slab_id',
            'id',
            'id',
            'slabs'
        )->using(RenewalBatchSlab::class)
            ->withTimestamps()->withPivot('id', 'team_id', 'max', 'min');
    }

    /**
     * get renewal batch segment wise advisors function
     */
    public function segmentAdvisors(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'renewal_batch_segment_user',
            'renewal_batch_id',
            'advisor_id',
            'id',
            'id',
            'segmentAdvisors'
        )->withTimestamps()->withPivot('segment_type');
    }

    public function deadlines()
    {
        return $this->belongsToMany(
            QuoteStatus::class,
            'renewal_batch_deadlines',
            'renewal_batch_id',
            'quote_status_id',
            'id',
            'id',
            'deadlines'
        )->withTimestamps()->withPivot('deadline_date');
    }

    public function scopeDateFilter($q, $reportDateEnd = null, $includePreviousMonth = true)
    {
        $baseDate = $reportDateEnd ? Carbon::parse($reportDateEnd) : now();

        $monthsYears = [
            ['month' => $baseDate->month, 'year' => $baseDate->year],
            ['month' => $baseDate->copy()->addMonthNoOverflow()->month, 'year' => $baseDate->copy()->addMonthNoOverflow()->year],
        ];

        if ($includePreviousMonth) {
            array_unshift($monthsYears, ['month' => $baseDate->copy()->subMonthNoOverflow()->month, 'year' => $baseDate->copy()->subMonthNoOverflow()->year]);
        }

        $q->where(function ($query) use ($monthsYears) {
            foreach ($monthsYears as $monthYear) {
                $query->orWhere(function ($query) use ($monthYear) {
                    $query->where('month', $monthYear['month'])->where('year', $monthYear['year']);
                });
            }
        });
    }

    public function scopeNonMotor($q)
    {
        $q->whereNull('quote_type_id');
    }

    public function scopeMotor($q)
    {
        $q->where('quote_type_id', QuoteTypes::CAR->id());
    }

    public function monthName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $year = $this->year ?? now()->year;
                $month = $this->month ?? now()->month;

                return Carbon::parse("{$year}-{$month}")->format('M');
            }
        );
    }

    public static function getAllBatches($nonMotor = true)
    {
        return self::select('id', 'name', 'start_date', 'end_date', 'month', 'year')
            ->when($nonMotor, fn ($q) => $q->nonMotor(), fn ($q) => $q->motor())
            ->orderBy('id')
            ->get()
            ->transform(function ($batch) {
                // Get the date display format from the configuration
                $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
                // Format the start and end dates of the batch
                $start_date = Carbon::parse($batch->start_date)->format($dateFormat);
                $end_date = Carbon::parse($batch->end_date)->format($dateFormat);

                // Return an associative array with the batch 'name' and 'id'
                return [
                    'id' => $batch->id,
                    'name' => "{$batch->month_name}-{$batch->name}-({$start_date} to {$end_date})",
                ];
            })
            ->toArray();
    }

    public function personalQuotes()
    {
        return $this->hasMany(PersonalQuote::class, 'renewal_batch_id');
    }
}
