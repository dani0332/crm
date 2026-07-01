<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMedicalQuoteCategory extends Model
{
    protected $table = 'group_medical_quote_category';
    protected $fillable = [
        'business_quote_request_id',
        'group_medical_category_id',
        'number_of_people',
        'insurance_provider_id',
        'health_third_party_administrator_id',
        'health_network_id',
        'renewal_date',
        'sort_order',
        'group_medical_network_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(GroupMedicalCategory::class, 'group_medical_category_id', 'id');
    }
}
