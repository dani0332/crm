<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class DocumentType extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'document_types';
    public $timestamps = false;

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    /**
     * @return mixed
     */
    public function scopeByQuoteTypeId($query, $quoteTypeId)
    {
        return $query->where('quote_type_id', $quoteTypeId);
    }

    public static function findOrCreate($code)
    {
        $obj = static::where('code', $code)->first();

        return $obj ?: new static;
    }

    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }

    public function scopeBySortDocumentType($query)
    {
        $query->orderBy('is_required', 'asc')->orderBy('text', 'asc');
    }
}
