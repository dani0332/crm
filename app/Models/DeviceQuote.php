<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class DeviceQuote extends Model implements AuditableContract
{
    use Auditable, HasFactory, QuoteModelTrait,Filterable, FilterCriteria;
    protected $table = 'device_quote_request';
    protected $guarded = [];

    public function personalQuote(): BelongsTo
    {
        return $this->belongsTo(PersonalQuote::class, 'personal_quote_id');
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
