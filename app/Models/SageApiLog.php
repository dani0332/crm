<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SageApiLog extends Model
{
    use HasFactory;

    /**
     * attributes those are mass assignable
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'section_id',
        'section_type',
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

    public function section()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->select(['id', 'email', 'name', 'mobile_no']);
    }
}
