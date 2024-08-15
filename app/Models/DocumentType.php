<?php

namespace App\Models;

use App\Enums\CustomerTypeEnum;
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

    public function scopeSortDocumentType($query)
    {
        $query->orderBy('is_required', 'desc')->orderBy('text', 'asc');
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

    public function scopeByBusinessTypeOfInsurance($query, $businessTypeOfInsurance)
    {
        return $query->where('business_type_of_insurance_id', $businessTypeOfInsurance);
    }

    public function scopeByBusinessTypeOfCustomer($query, $businessTypeOfCustomer, $businessInsurerName = false)
    {
        if (! $businessInsurerName) {
            if (in_array($businessTypeOfCustomer, [CustomerTypeEnum::Business, CustomerTypeEnum::Entity])) {
                $businessTypeOfCustomer = DocumentTypeCode::COMPANY_BUSINESS_TYPE_OF_CUSTOMER;
            } elseif ($businessTypeOfCustomer == CustomerTypeEnum::Individual) {
                $businessTypeOfCustomer = DocumentTypeCode::INDIVIDUAL_BUSINESS_TYPE_OF_CUSTOMER;
            }
        } else {
            $businessTypeOfCustomer = $businessInsurerName;
        }

        return $query->where('business_type_of_customer', $businessTypeOfCustomer);
    }

    public function scopeGetBusinessDocument($query, $businessTypeOfInsurance, $businessTypeOfCustomer, $businessInsurerName = false)
    {
        return $query->when($businessTypeOfInsurance, function ($query) use ($businessTypeOfInsurance) {
            return $query->byBusinessTypeOfInsurance($businessTypeOfInsurance);
        })->when($businessTypeOfCustomer, function ($query) use ($businessTypeOfCustomer, $businessInsurerName) {
            return $query->byBusinessTypeOfCustomer($businessTypeOfCustomer, $businessInsurerName);
        });
    }

    public function scopeByCompanyBusinessDocument($query)
    {
        return $query->where('business_type_of_customer', DocumentTypeCode::COMPANY_BUSINESS_TYPE_OF_CUSTOMER);
    }
}
