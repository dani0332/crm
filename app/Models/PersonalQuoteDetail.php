<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PersonalQuoteDetail extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $guarded = [];

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
    public function previousAdvisor()
    {
        return $this->belongsTo(User::class, 'previous_advisor_id', 'id');
    }
}
