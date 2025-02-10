<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsQuote extends Model
{
    protected $table = 'savings_quote_request';
    protected $fillable = [
        'personal_quote_id',
        'marital_status_id',
        'tenure_of_savings',
        'has_nicotine',
        'purpose_of_savings',
        'currency_id',
        'amount',
        'investment_frequency',
        'additional_notes',
    ];
}
