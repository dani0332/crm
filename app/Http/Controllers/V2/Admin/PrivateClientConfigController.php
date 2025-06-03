<?php

namespace App\Http\Controllers\V2\Admin;

use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Models\CarMake;
use App\Models\InsuranceProvider;
use App\Models\PrivateClientConfig;
use App\Models\SubArea;
use App\Traits\PrivateClient;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PrivateClientConfigController extends Controller
{
    use PrivateClient;

    public function __construct()
    {
        $this->middleware('role:'.Arr::join([RolesEnum::SeniorManagement, RolesEnum::Admin], '|'), ['only' => ['show']]);
    }

    public function show(Request $request)
    {
        $selectedVersion = $request->input('version', null);

        $allVersions = PrivateClientConfig::select('version')
            ->distinct()
            ->orderBy('version', 'desc')
            ->pluck('version')
            ->toArray();

        if ($selectedVersion === null && count($allVersions) > 0) {
            $selectedVersion = $allVersions[0];
        }

        $configurations = new PrivateClientConfig;

        if ($selectedVersion) {
            $configurations = $configurations->where('version', $selectedVersion);
        }
        $configurations = $configurations->get();

        $carMakes = CarMake::select('id as value', 'text as label')->where('is_active', true)->get()->toArray();

        $insurers = InsuranceProvider::select('id as value', 'text as label')->where('is_active', true)->get()->toArray();

        $locationAreas = SubArea::select('id as value', 'text as label')->get()->toArray();

        $isCurrentVersion = (int) $selectedVersion === (int) $allVersions[0];

        return Inertia::render('Admin/PrivateClientConfig/Show', [
            'configurations' => $configurations,
            'carMakes' => $carMakes,
            'insurers' => $insurers,
            'locationAreas' => $locationAreas,
            'allVersions' => $allVersions,
            'selectedVersion' => $selectedVersion,
            'isCurrentVersion' => $isCurrentVersion,
        ]);
    }

    public function upsert(Request $request)
    {
        try {
            // Validate incoming request
            $validated = $request->validate([
                'configurations' => 'required|array',
                'configurations.*.quote_type_id' => 'integer',
                'configurations.*.field_name' => 'nullable|string|max:255',
                'configurations.*.operator' => 'nullable|string',
                'configurations.*.value' => 'nullable|string',
                'configurations.*.currency_type_id' => 'nullable|integer',
                'configurations.*.status' => 'nullable|integer|in:0,1',
            ]);

            DB::beginTransaction();

            // Set active_version=false for all existing configurations
            PrivateClientConfig::where('active_version', true)->update(['active_version' => false]);

            $existingVersion = PrivateClientConfig::orderBy('version', 'desc')->first();
            foreach ($validated['configurations'] as $config) {

                $version = $existingVersion ? $existingVersion->version + 1 : 1;

                // Create new config with new version
                PrivateClientConfig::create([
                    'quote_type_id' => $config['quote_type_id'],
                    'field_name' => $config['field_name'],
                    'operator' => $config['operator'],
                    'value' => isset($config['value']) ? $config['value'] : null,
                    'currency_type_id' => $config['currency_type_id'] ?? null,
                    'status' => $config['status'],
                    'version' => $version,
                    'active_version' => true,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.private-client-config.show')
                ->with('message', 'Private Client Configuration updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'An error occurred while updating the configuration: '.$e->getMessage());
        }
    }
}
