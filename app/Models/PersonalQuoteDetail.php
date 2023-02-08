<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalQuoteDetail extends Model
{
    use HasFactory;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function personalQuote()
    {
        return $this->belongsTo(PersonalQuote::class);
    }

    public function lostReason()
    {
        return $this->belongsTo(LostReasons::class);
    }

}
