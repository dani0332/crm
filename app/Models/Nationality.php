<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;


class Nationality extends Model 
{
    use HasFactory ;
    protected $table = 'nationality';

    public function delete()
    {
        $this->setKeysForSaveQuery($this->newModelQuery())->update(['is_deleted' => true]);
        return true;
    }
}
