<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class Nationality extends BaseModel
{
    use HasFactory;
    protected $table = 'nationality';

    public function delete()
    {
        $this->setKeysForSaveQuery($this->newModelQuery())->update(['is_deleted' => true]);
        return true;
    }

    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, 'nationality', ['code', 'id', 'text']);
    }
}
