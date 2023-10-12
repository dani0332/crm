<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function quoteRequestEntityMapping(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(QuoteRequestEntityMapping::class, 'entity_id', 'id');
    }

}
