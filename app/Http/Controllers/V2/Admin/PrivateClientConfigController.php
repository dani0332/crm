<?php

namespace App\Http\Controllers\V2\Admin;

use Inertia\Inertia;
use App\Enums\RolesEnum;
use App\Enums\QuoteTypes;
use App\Models\QuoteType;
use Illuminate\Support\Arr;
use App\Enums\quoteTypeCode;
use Illuminate\Http\Request;
use App\Traits\PrivateClient;
use Illuminate\Validation\Rule;
use App\Models\PrivateClientConfig;
use App\Http\Controllers\Controller;
use App\Services\PrivateClientConfigService;

class PrivateClientConfigController extends Controller
{
    use PrivateClient;

    public function __construct(protected PrivateClientConfigService $configService)
    {
        $this->middleware('role:'.Arr::join([RolesEnum::SeniorManagement, RolesEnum::Admin], '|'), ['only' => ['show', 'upsert']]);
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
            'quoteTypes' => $quoteTypes
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
            $responseData = $this->configService->getLatestConfigByQuoteType($quoteTypeId, $version);

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
                'quote_type' => ['required', Rule::enum(QuoteTypes::class)],
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
