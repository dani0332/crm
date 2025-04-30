<?php

namespace App\Http\Controllers\V2\Admin;

use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Models\CarMake;
use App\Models\InsuranceProvider;
use App\Models\PrivateClientConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;

class PrivateClientConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:'.Arr::join([RolesEnum::SeniorManagement, RolesEnum::Engineering], '|'), ['only' => ['show']]);
    }

    public function show()
    {
        // Get existing configurations
        $configurations = PrivateClientConfig::all();

        // Car makes for luxury vehicles
        $carMakes = CarMake::select('code as value', 'text as label')->where('is_active', true)->get()->toArray();

        // Insurance companies
        $insurers = InsuranceProvider::select('code as value', 'text as label')->where('is_active', true)->get()->toArray();

        // Location areas
        $locationAreas = [
            ['label' => 'Dubai Marina', 'value' => 'Dubai Marina'],
            ['label' => 'Downtown Dubai', 'value' => 'Downtown Dubai'],
            ['label' => 'Palm Jumeirah', 'value' => 'Palm Jumeirah'],
            ['label' => 'Emirates Hills', 'value' => 'Emirates Hills'],
            ['label' => 'Jumeirah Beach Residence', 'value' => 'Jumeirah Beach Residence'],
            ['label' => 'Arabian Ranches', 'value' => 'Arabian Ranches'],
            ['label' => 'Dubai Hills Estate', 'value' => 'Dubai Hills Estate'],
            ['label' => 'The Springs', 'value' => 'The Springs'],
            ['label' => 'Jumeirah Lakes Towers', 'value' => 'Jumeirah Lakes Towers'],
            ['label' => 'Bluewaters Island', 'value' => 'Bluewaters Island'],
        ];

        return Inertia::render('Admin/PrivateClientConfig/Config/Show', [
            'configurations' => $configurations,
            'carMakes' => $carMakes,
            'insurers' => $insurers,
            'locationAreas' => $locationAreas,
        ]);
    }

    public function upsert(Request $request)
    {
        // Validate and process the request
        $validated = $request->validate([
            'configurations' => 'array',
        ]);

        // Process and save configs
        foreach ($validated['configurations'] as $config) {
            PrivateClientConfig::updateOrCreate(
                [
                    'quote_type_id' => $config['quote_type_id'],
                    'field_name' => $config['field_name'],
                ],
                [
                    'name' => $config['name'],
                    'operator' => $config['operator'],
                    'value' => $config['value'],
                    'currency_type_id' => $config['currency_type_id'] ?? null,
                    'status' => $config['status'] ?? 1,
                    'is_active' => true,
                ]
            );
        }

        return back()->with('success', 'Configuration updated successfully');
    }
}
