<?php

namespace App\Http\Controllers\V2\Admin;

use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Models\CarMake;
use App\Models\CurrencyType;
use App\Models\InsuranceProvider;
use App\Models\Nationality;
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

    private const LABEL_TEXT = 'text as label';
    private const VALUE_TEXT = 'id as value';

    public function __construct()
    {
        $this->middleware('role:'.Arr::join([RolesEnum::SeniorManagement, RolesEnum::Admin], '|'), ['only' => ['show', 'advanced']]);
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

        $carMakes = CarMake::select(self::VALUE_TEXT, self::LABEL_TEXT)->where('is_active', true)->get()->toArray();

        $insurers = InsuranceProvider::select(self::VALUE_TEXT, self::LABEL_TEXT)->where('is_active', true)->get()->toArray();

        $locationAreas = SubArea::select(self::VALUE_TEXT, self::LABEL_TEXT)->get()->toArray();

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

    public function advanced(Request $request)
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

        $configurations = PrivateClientConfig::query()
                            ->when($selectedVersion, function ($query) use ($selectedVersion) {
                                return $query->where('version', $selectedVersion);
                            })
                            ->get();

        $carMakes = CarMake::select(self::VALUE_TEXT, self::LABEL_TEXT)->where('is_active', true)->get();

        $insurers = InsuranceProvider::select(self::VALUE_TEXT, self::LABEL_TEXT)->where('is_active', true)->get();

        $locationAreas = SubArea::select(self::VALUE_TEXT, self::LABEL_TEXT)->get();

        $nationalities = Nationality::select(self::VALUE_TEXT, self::LABEL_TEXT)->where('is_active', true)->get();

        $currencies = CurrencyType::select(self::VALUE_TEXT, self::LABEL_TEXT)->where('is_active', true)->get();

        $isCurrentVersion = (int) $selectedVersion === (int) $allVersions[0];

        return Inertia::render('Admin/PrivateClientConfig/Advanced', [
            'configurations' => $configurations,
            'carMakes' => $carMakes,
            'insurers' => $insurers,
            'locationAreas' => $locationAreas,
            'nationalities' => $nationalities,
            'currencies' => $currencies,
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
                'configurations.*.profiles' => 'required|array',
                'configurations.*.profiles.*.fieldName' => 'required|string',
                'configurations.*.profiles.*.operator' => 'required|string',
                'configurations.*.profiles.*.value' => 'nullable',
                'configurations.*.profiles.*.currencyTypeId' => 'nullable|integer',
                'configurations.*.profiles.*.nationalityIds' => 'nullable|array',
            ]);

            DB::beginTransaction();

            // Set active_version=false for all existing configurations
            PrivateClientConfig::where('active_version', true)->update(['active_version' => false]);

            $existingVersion = PrivateClientConfig::orderBy('version', 'desc')->first();
            $newVersion = $existingVersion ? $existingVersion->version + 1 : 1;

            foreach ($validated['configurations'] as $config) {
                // Create new config with new version
                PrivateClientConfig::create([
                    'quote_type_id' => $config['quote_type_id'],
                    'config' => json_encode(['profiles' => $config['profiles']]),
                    'version' => $newVersion,
                    'status' => 1,
                    'active_version' => true,
                ]);
            }

            DB::commit();

            // Check if request came from advanced page
            $redirectRoute = $request->header('referer') && str_contains($request->header('referer'), 'advanced')
                ? 'admin.private-client-config.advanced'
                : 'admin.private-client-config.show';

            return redirect()->route($redirectRoute)
                ->with('message', 'Private Client Configuration updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'An error occurred while updating the configuration: '.$e->getMessage());
        }
    }
}
