<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CarLostQuoteLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @return MorphMany
     */
    public function documents()
    {
        return $this->morphMany(GenericDocument::class, 'documentable');
    }

    /**
     * @return BelongsTo
     */
    public function advisor()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function quoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class);
    }

    /**
     * @return BelongsTo
     */
    public function reason()
    {
        return $this->belongsTo(Lookup::class, 'reason_id');
    }

    /**
     * @return BelongsTo
     */
    public function actionBy()
    {
        return $this->belongsTo(User::class, 'action_by_id');
    }
}
