<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\SpatieActivityLog;

class PaymentStatusHistory extends Model
{
    use SpatieActivityLog;

    protected $table = 'payment_status_history';
}
