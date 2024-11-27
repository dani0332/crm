<?php

namespace App\Models;

use App\Enums\QuoteTypeId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use League\CommonMark\Extension\SmartPunct\Quote;

class QuoteRequestEntityMapping extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'quote_request_entity_mapping';

    public function entity()
    {
        return $this->belongsTo(Entity::class, 'entity_id');
    }

    public function quote(): ?\Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        // Get the quote_type_id from the current model
        $quoteTypeId = $this->quote_type_id;

        switch ($quoteTypeId) {
            case QuoteTypeId::Car:
                return $this->belongsTo(CarQuote::class, 'quote_request_id');

            case QuoteTypeId::Home:
                return $this->belongsTo(HomeQuote::class, 'quote_request_id');

            case QuoteTypeId::Health:
                return $this->belongsTo(HealthQuote::class, 'quote_request_id');

            case QuoteTypeId::Life:
                return $this->belongsTo(LifeQuote::class, 'quote_request_id');

            case QuoteTypeId::Business:
            case QuoteTypeId::GroupMedical:
            case QuoteTypeId::Corpline:
                return $this->belongsTo(BusinessQuote::class, 'quote_request_id');

            case QuoteTypeId::Bike:
            case QuoteTypeId::Yacht:
            case QuoteTypeId::Pet:
            case QuoteTypeId::Cycle:
            case QuoteTypeId::Jetski:
                return $this->belongsTo(PersonalQuote::class, 'quote_request_id');

            case QuoteTypeId::Travel:
                return $this->belongsTo(TravelQuote::class, 'quote_request_id');

            default:
                return null;
        }

    }
}
