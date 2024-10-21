<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrokerInvoiceNumber extends Model
{
    use HasFactory;

    protected $fillable = ['insurance_provider_id', 'date', 'sequence_number'];
}
