<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use Illuminate\Http\Request;

class NationalityPoolConfigurationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:'.PermissionsEnum::NATIONALITY_POOL_CONFIG);
    }

    public function index()
    {
        //
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }
}
