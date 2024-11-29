<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteRequestEntityMapping extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'quote_request_entity_mapping';

    public function entity()
    {
        return $this->belongsTo(Entity::class, 'entity_id');
    }
}
