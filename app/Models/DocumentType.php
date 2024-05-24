<?php

namespace App\Models;

use App\Enums\DocumentTypeCode;
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

    public function scopeRequired($query)
    {
        $query->where('is_required', 1);
    }

    public function scopeIssuingDocument($query)
    {
        $query->where('category', DocumentTypeCode::ISSUING_DOCUMENTS);
    }

    public function scopeTaxDocument($query)
    {
        $query->whereIn('code', [DocumentTypeCode::TI, DocumentTypeCode::CTIRBB])->issuingDocument()->active();
    }

    public function scopeRequiredForSendPolicy($query)
    {
        return $query->where('is_required_for_send_policy', 1)->required()->active()->issuingDocument();
    }

    public function scopeSendToCustomer($query)
    {
        $query->whereNotIn('code', [DocumentTypeCode::CTIRBB])->where('send_to_customer', 1)->active();
    }
}
