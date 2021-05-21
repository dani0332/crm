<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardCategoryMapping extends Model
{
    use HasFactory;
    protected $table = 'reward_category_mapping';
    public $timestamps = false;
}
