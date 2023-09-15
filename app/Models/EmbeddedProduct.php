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
    ];

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
}
