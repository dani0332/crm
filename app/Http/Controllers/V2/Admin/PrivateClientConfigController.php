<?php

namespace App\Http\Controllers\V2\Admin;

use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Models\PrivateClientConfig;
use App\Models\QuoteType;
use App\Services\PrivateClientConfigService;
use App\Traits\PrivateClient;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;

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
            ->whereIn('code', [
                quoteTypeCode::Car,
                quoteTypeCode::Health,
                quoteTypeCode::Life,
                quoteTypeCode::Home,
                quoteTypeCode::Yacht,
            ])
            ->select('id', 'text', 'code')
            ->get();
    }

    public function show()
    {
        $quoteTypes = $this->getQuoteTypes();

        return Inertia::render('Admin/PrivateClientConfig/Show', [
            'quoteTypes' => $quoteTypes,
            'quoteTypeCodeEnum' => [
                'Car' => quoteTypeCode::Car,
                'Health' => quoteTypeCode::Health,
                'Life' => quoteTypeCode::Life,
                'Home' => quoteTypeCode::Home,
                'Yacht' => quoteTypeCode::Yacht,
            ],
        ]);
    }

    public function getLatestConfigByQuoteType(Request $request)
    {
        $quoteTypeId = $request->input('quote_type_id');
        $version = $request->input('version');

        if (! $quoteTypeId) {
            return response()->json(['error' => 'Quote type ID is required'], 400);
        }

        try {
            if ($version) {
                // Get specific version
                $responseData = $this->configService->getConfigByVersionAndQuoteType($quoteTypeId, $version);
            } else {
                // Get latest version
                $responseData = $this->configService->getLatestConfigByQuoteType($quoteTypeId);
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load configuration'], 500);
        }
    }
    public function upsert(Request $request)
    {
        try {
            $validated = $request->validate([
                'quote_type_id' => 'required|integer',
                'config' => 'required|array',
                'quote_type' => 'required|string',
                'version' => 'required|integer',
            ]);

            $existingVersion = PrivateClientConfig::orderBy('version', 'desc')->first();
            $newVersion = $existingVersion ? $existingVersion->version + 1 : 1;

            $this->configService->createNewConfigurationVersion($validated);

            return redirect()->route('admin.private-client-config.show')
                ->with('message', 'Private Client Configuration updated successfully.')
                ->with('version', $newVersion);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while updating the configuration: '.$e->getMessage());
        }
    }
}
