<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;

class DeviceQuote extends Model
{
    use  Filterable, FilterCriteria, QuoteModelTrait;
    protected $table = 'smartphone_quote_request';
    protected $guarded = [];
   
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
