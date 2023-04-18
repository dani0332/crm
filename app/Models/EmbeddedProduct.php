<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmbeddedProduct extends Model
{
    protected $fillable = ['insurance_provider_id', 'product_name', 'short_code', 'display_name', 'product_type', 'logic', 'description', 'description2', 'commission_type', 'commission_value', 'email_template_id', 'company_documents', 'pricing_type','removal_confirmation'];

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
        return $this->hasMany(EmbeddedProductPricing::class);
    }
}
