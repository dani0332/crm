<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GenericDocument extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function genericDocumentType()
    {
        return $this->belongsTo(GenericDocumentType::class, 'generic_document_type_id');
    }

    /**
     * @return BelongsTo
     */
    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id');
    }

    /**
     * @return BelongsTo
     */
    public function businessTypeOfInsurance()
    {
        return $this->belongsTo(BusinessTypeOfInsurance::class, 'business_type_of_insurance_id');
    }

    /**
     * @return MorphTo
     */
    public function documentable()
    {
        return $this->morphTo();
    }
}
