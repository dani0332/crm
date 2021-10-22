<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalsDump extends Model
{
    use HasFactory;
    protected $table = 'renewals_dump';
    protected $guarded = [];
}
