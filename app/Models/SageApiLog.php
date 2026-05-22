<?php

namespace App\Models;

use App\Traits\SpatieActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SageApiLog extends Model
{
    use HasFactory, SpatieActivityLog;

    /**
     * attributes those are mass assignable
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'section_id',
        'section_type',
        'model_id',
        'model_type',
        'step',
        'total_steps',
        'sage_request_type',
        'sage_end_point',
        'sage_payload',
        'response',
        'status',
        'entry_type',
        'created_at',
        'updated_at',
    ];

    public function section(): MorphTo
    {
        return $this->morphTo();
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->select(['id', 'email', 'name', 'mobile_no']);
    }

    // Reminder:: This relationship is used through Sage Processes
    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }
}
