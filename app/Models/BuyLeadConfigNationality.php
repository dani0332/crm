<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyLeadConfigNationality extends Model
{
    protected $table = 'buy_lead_config_nationalities';

    protected $fillable = [
        'nationality_id',
        'buy_lead_configuration_id',
    ];

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function buyLeadConfiguration()
    {
        return $this->belongsTo(BuyLeadConfiguration::class);
    }
}   
