<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmbeddedTransaction extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function quoteType()
    {
        return $this->belongsTo(QuoteType::class, 'quote_type_id', 'id');
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(EmbeddedProductOption::class, 'product_id', 'id');
    }
    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function travelAnnualPayments()
    {
        return $this->hasOne(Payment::class, 'code', 'code');
    }

    public function quoteRequest()
    {
        return $this->morphTo();
    }

    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    public function travelQuote()
    {
        return $this->belongsTo(TravelQuote::class, 'code', 'code');
    }
}
