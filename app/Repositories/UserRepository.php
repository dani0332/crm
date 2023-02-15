<?php

namespace App\Repositories;

use App\Models\Activities;
use App\Models\User;

class UserRepository extends BaseRepository
{
    public function model() {
        return User::class;
    }

    public function fetchGetPersonalQuoteAdvisors()
    {
        return $this->whereHas('roles', function($q){
            //todo: add required roles here
        })->get();
    }

}
