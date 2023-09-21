<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PetQuote extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $table = 'pet_quote_request';
    protected $guarded = [];

    public function quoteStatus()
    {
        return $this->hasOne(QuoteStatus::class, 'id', 'quote_status_id');
    }

    public function petQuoteRequestDetail()
    {
        return $this->hasOne(PetQuoteRequestDetail::class, 'pet_quote_request_id', 'id');
    }

    public function accomodationType()
    {
        return $this->belongsTo(HomeAccomodationType::class, 'ilivein_accommodation_type_id', 'id');
    }

    public function possessionType()
    {
        return $this->belongsTo(HomePossessionType::class, 'iam_possesion_type_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function petType()
    {
        return $this->belongsTo(Lookup::class, 'pet_type_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function petAge()
    {
        return $this->belongsTo(Lookup::class, 'pet_age_id', 'id');
    }

    /**
     * @return array
     */
    public function getAuditables()
    {
        return [
            'auditable_type' => PersonalQuote::class,
            'relations' => [
                ['auditable_type' => PersonalQuoteDetail::class, 'key' => 'personal_quote_id'],
                ['auditable_type' => PetQuote::class, 'key' => 'personal_quote_id'],
            ],
        ];
    }
}
