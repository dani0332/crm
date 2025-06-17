<?php

namespace App\Http\Controllers\V2\Admin;

use Inertia\Inertia;
use App\Models\CarMake;
use App\Models\SubArea;
use App\Enums\RolesEnum;
use App\Models\QuoteType;
use App\Models\Nationality;
use Illuminate\Support\Arr;
use App\Models\CurrencyType;
use Illuminate\Http\Request;
use App\Traits\PrivateClient;
use App\Models\InsuranceProvider;
use Illuminate\Support\Facades\DB;
use App\Models\PrivateClientConfig;
use App\Http\Controllers\Controller;
use App\Services\PrivateClientConfigService;
use App\Enums\quoteTypeCode;

class PrivateClientConfigController extends Controller
{
    use PrivateClient;

    private const LABEL_TEXT = 'text as label';
    private const VALUE_TEXT = 'id as value';

    protected PrivateClientConfigService $configService;

    public function __construct(PrivateClientConfigService $configService)
    {
        $this->configService = $configService;
        $this->middleware('role:'.Arr::join([RolesEnum::SeniorManagement, RolesEnum::Admin], '|'), ['only' => ['show', 'advanced']]);
    }

    private function getQuoteTypes()
    {
        return QuoteType::where('is_active', 1)
            ->whereIn('short_code', ['CAR', 'HEALTH', 'LIFE', 'HOME', 'YACHT'])
            ->select('id', 'text', 'short_code')
            ->get();
    }

    public function show(Request $request)
    {
        $selectedVersion = $request->input('version', null);
        $allVersions = $this->configService->getAllVersions();

        if ($selectedVersion === null && count($allVersions) > 0) {
            $selectedVersion = $allVersions[0];
        }

        $configurations = $this->configService->getConfigurationsByVersion($selectedVersion);
        $isCurrentVersion = $this->configService->isCurrentVersion($selectedVersion, $allVersions);

        $carMakes = CarMake::select(self::VALUE_TEXT, self::LABEL_TEXT)->where('is_active', true)->get();
        $insurers = InsuranceProvider::select(self::VALUE_TEXT, self::LABEL_TEXT)->where('is_active', true)->get();
        $locationAreas = SubArea::select(self::VALUE_TEXT, self::LABEL_TEXT)->get();

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
        $allVersions = $this->configService->getAllVersions();

        if ($selectedVersion === null && count($allVersions) > 0) {
            $selectedVersion = $allVersions[0];
        }

        $configurations = $this->configService->getConfigurationsByVersion($selectedVersion);
        $isCurrentVersion = $this->configService->isCurrentVersion($selectedVersion, $allVersions);
        $quoteTypes = $this->getQuoteTypes();

        return Inertia::render('Admin/PrivateClientConfig/Advanced', [
            'configurations' => $configurations,
            'allVersions' => $allVersions,
            'selectedVersion' => $selectedVersion,
            'isCurrentVersion' => $isCurrentVersion,
            'quoteTypes' => $quoteTypes,
            'quoteTypeCodeEnum' => [
                'CAR' => 'CAR',
                'HEALTH' => 'HEALTH',
                'LIFE' => 'LIFE',
                'HOME' => 'HOME',
                'YACHT' => 'YACHT',
            ],
        ]);
    }

    public function getLatestConfigByQuoteType(Request $request)
    {
        $quoteTypeId = $request->input('quote_type_id');

        if (!$quoteTypeId) {
            return response()->json(['error' => 'Quote type ID is required'], 400);
        }

        try {
            $responseData = $this->configService->getLatestConfigByQuoteType($quoteTypeId);
            return response()->json($responseData);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load configuration'], 500);
        }
    }

    public function upsert(Request $request)
    {
        try {
            // Basic validation
            $validated = $request->validate([
                'configurations' => 'required|array',
                'configurations.*.quote_type_id' => 'integer',
                'configurations.*.profiles' => 'required|array',
            ]);

            // Create new configuration version using service
            $this->configService->createNewConfigurationVersion($validated['configurations']);

            // Check if request came from advanced page
            $redirectRoute = $request->header('referer') && str_contains($request->header('referer'), 'advanced')
                ? 'admin.private-client-config.advanced'
                : 'admin.private-client-config.show';

            return redirect()->route($redirectRoute)
                ->with('message', 'Private Client Configuration updated successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while updating the configuration: '.$e->getMessage());
        }
    }
}
