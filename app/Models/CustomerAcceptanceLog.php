<?php

namespace App\Models;

use App\Models\Trait\HasDateTrait;
use Illuminate\Database\Eloquent\Model;

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
