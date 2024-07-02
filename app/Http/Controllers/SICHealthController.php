<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\NationalityRepository;

class SICHealthController extends Controller
{
    //

    public function sicHealthConfig(){
        $nationalities = NationalityRepository::withActive()->get();
        return inertia('Admin/SICHealth/SicHealthConfigForm', [
            'nationalities' => $nationalities
        ]);
    }
}
