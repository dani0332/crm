<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MyAlFredUser extends Model
{
    use HasFactory;

    protected $table = 'myalfred_users_migration';
    protected $fillable = ['signup_url', 'customer_id', 'code', 'source'];
    protected $timestamp = true;
}
