<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalBatch extends Model
{
    use HasFactory, FilterCriteria;

    protected $fillable = ['name', 'quote_status_id', 'deadline_date'];

    public $filterables = [
        'name' => FilterTypes::EXACT,
        'quote_status_id' => FilterTypes::EXACT
    ];

    public function quoteStatus()
    {
        return $this->hasOne(QuoteStatus::class, 'id', 'quote_status_id');
    }
}
