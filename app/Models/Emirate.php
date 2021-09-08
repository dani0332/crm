<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use App\Models\BaseModel;

class Emirate extends BaseModel
{
    use HasFactory;
    protected $table = 'emirates';
    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, 'emirates', ['code', 'id', 'text', 'text_ar']);
    }
}
