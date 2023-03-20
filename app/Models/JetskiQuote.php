<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JetskiQuote extends Model
{
    use HasFactory;

    protected $table = 'jetski_quote_request';
    protected $guarded = [];

    /**
     * @return array
     */
    public function getAuditables()
    {
        return [
            'auditable_type' => PersonalQuote::class,
            'relations' => [
                ['auditable_type' => PersonalQuoteDetail::class, 'key' => 'personal_quote_id'],
                ['auditable_type' => JetskiQuote::class, 'key' => 'personal_quote_id'],
            ],
        ];
    }
}
