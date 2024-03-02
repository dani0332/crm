<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SendUpdateLog extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $guarded = [];

    public function quoteType()
    {
        return $this->belongsTo(QuoteType::class, 'quote_type_id');
    }

    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    public function category()
    {
        return $this->belongsTo(Lookup::class, 'category_id');
    }

    public function option()
    {
        return $this->belongsTo(Lookup::class, 'option_id');
    }
}
