<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Illuminate\Database\Eloquent\Model;

class HomeAccomodationType extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $table = 'home_accommodation_type';
}
