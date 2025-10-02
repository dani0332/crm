<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Trait\HasDateTrait;

class CustomerAcceptanceLog extends Model
{
    use HasDateTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [];

    protected $guarded = [];
}
