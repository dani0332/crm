<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * EP Log Model
 *
 * Tracks embedded product events and their associated data.
 */
class EpLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ep_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'embedded_transaction_id',
        'event',
        'values',
        'loggable_id',
        'loggable_type',
    ];

    /**
     * Get the parent loggable model (SendUpdateLog, etc.).
     */
    public function loggable()
    {
        return $this->morphTo();
    }

    public function embeddedTransaction()
    {
        return $this->belongsTo(EmbeddedTransaction::class, 'embedded_transaction_id', 'id');
    }
}
