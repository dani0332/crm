<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CyberQuote extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'cyber_quote_request';
    protected $guarded = [];

    public function personalQuote(): BelongsTo
    {
        return $this->belongsTo(PersonalQuote::class, 'personal_quote_id');
    }

    public function emirateOfRegistration()
    {
        return $this->belongsTo(Emirate::class, 'emirate_of_registration_id');
    }

    public function coverage(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'coverage_id');
    }

    public function getAuditables()
    {
        return [
            'auditable_type' => PersonalQuote::class,
            'relations' => [
                ['auditable_type' => PersonalQuoteDetail::class, 'key' => 'personal_quote_id'],
                ['auditable_type' => self::class, 'key' => 'personal_quote_id'],
            ],
        ];
    }
}
