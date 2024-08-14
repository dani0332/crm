<?php

namespace App\Models;

use App\Enums\CustomerTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Entity extends Model
{
    protected $guarded = [];

    public function quoteRequestEntityMapping(): HasMany
    {
        return $this->hasMany(QuoteRequestEntityMapping::class, 'entity_id', 'id');
    }

    public function quoteMember(): HasOne
    {
        return $this->hasOne(QuoteMemberDetail::class, 'customer_entity_id')->ofMany([
            'id' => 'max',
        ], function (Builder $query) {
            $query->where('customer_type', CustomerTypeEnum::Entity);
        });
    }
    public function corporationCountry()
    {
        return $this->belongsTo(Nationality::class, 'country_of_corporation', 'id');
    }
}
