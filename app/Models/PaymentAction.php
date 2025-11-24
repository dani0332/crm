<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\SpatieActivityLog;
class PaymentAction extends Model
{
    use SpatieActivityLog;
    
    protected $guarded = [];

    /**
     * @return bool
     */
}
