<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class CarModel extends BaseModel
{
    use HasFactory;
    protected $table = 'car_model';

    public function processGetDSL($filters = []) {
        return self::processGetBaseDSL($filters, 'car_model', ['car_make_code', 'id', 'text']);
    }
}
