<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use App\Models\BaseModel;

class CarMake extends BaseModel
{
    use HasFactory;
    protected $table = 'car_make';
    public function processGetDSL($filters = []) {
        return self::processGetBaseDSL($filters, 'car_make', ['code', 'id', 'text', 'is_active']);
    }
}
