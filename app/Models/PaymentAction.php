<?php

namespace App\Models;

use App\Traits\SpatieActivityLog;
use Illuminate\Database\Eloquent\Model;

class PaymentAction extends Model
{
    use SpatieActivityLog;

    protected $guarded = [];

    /**
     * @return bool
     */
}
