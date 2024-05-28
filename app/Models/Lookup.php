<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lookup extends Model
{
    use HasFactory;

    public function scopeWithChildTree($query, $quoteTypeId, $removeOptions = [])
    {
        return $query->with('childs', function ($query) use ($quoteTypeId, $removeOptions) {
            $query->select('id', 'key as title', 'text as description', 'code as slug', 'parent_id', 'quote_type_id')
                ->with('childs', function ($query) use ($quoteTypeId, $removeOptions) {
                    $query->where('quote_type_id', $quoteTypeId)->select('id', 'key as title', 'text as description', 'code as slug', 'parent_id', 'quote_type_id');
                    $query->when(count($removeOptions), function ($query) use ($removeOptions) {
                        $query->whereNotIn('code', $removeOptions);
                    });
                });
        })->select('id', 'key as title', 'text as description', 'code as slug', 'parent_id', 'quote_type_id');

        return $query->with('childs');
    }

    public function childs()
    {
        return $this->hasMany('App\Models\Lookup', 'parent_id', 'id');
    }
}
