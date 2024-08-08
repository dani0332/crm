<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmbeddedProduct extends Model
{
    protected $fillable = [
        'insurance_provider_id',
        'product_name',
        'product_type',
        'product_category',
        'product_validity',
        'short_code',
        'display_name',
        'description',
        'pricing_type',
        'commission_type',
        'commission_value',
        'email_template_ids',
        'uncheck_message',
        'logic_description',
        'company_documents',
        'is_active',
        'min_value',
        'max_value',
        'min_age',
        'max_age',
    ];
    protected $appends = ['canGenerateCerticate'];

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id', 'id');
    }

    public function placements()
    {
        return $this->hasMany(EmbeddedProductPlacement::class);
    }

    public function prices()
    {
        return $this->hasMany(EmbeddedProductOption::class);
    }

    /**
     * Scope the query to only include active reward details.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function ScopeActive($query)
    {
        $query->where('is_active', 1);
    }

    /**
     * Determine if the embedded product can generate a certificate.
     */
    protected function getCanGenerateCerticateAttribute(): bool
    {
        $certificatesConfig = config('embedded-products.certificates');
        $shortCode = strtoupper($this->short_code);
        if (isset($certificatesConfig[$shortCode])) {
            return true;
        }

        return false;
    }

    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }
}
