<?php

namespace App\Models;

use Carbon\Carbon;
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

        $startMonth = $includePreviousMonth ? $baseDate->copy()->subMonth(1)->startOfMonth() : $baseDate->copy()->startOfMonth();
        $endMonth = $baseDate->copy()->addMonth(1)->endOfMonth();

        $startMonthFormatted = $startMonth->format('n');
        $endMonthFormatted = $endMonth->format('n');
        $startYearFormatted = $startMonth->format('Y');
        $endYearFormatted = $endMonth->format('Y');

        $q->whereBetween('month', [$startMonthFormatted, $endMonthFormatted])
            ->whereBetween('year', [$startYearFormatted, $endYearFormatted]);
    }
}
