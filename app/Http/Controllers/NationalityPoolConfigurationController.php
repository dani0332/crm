<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Models\CanonicalNationality;
use Illuminate\Http\Request;
use Inertia\Response;

class NationalityPoolConfigurationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:'.PermissionsEnum::NATIONALITY_POOL_CONFIG);
    }

    public function index(): Response
    {
        $nationalities = CanonicalNationality::where('nationality_synonym', 0)
            ->select('canonical_nationality_code as value', 'canonical_nationality_name as label')
            ->orderBy('canonical_nationality_name')->get();

        return inertia('Admin/AllocationConfig/NationalityPool/Index', [
            'gbpNationalities' => $nationalities,
        ]);
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
