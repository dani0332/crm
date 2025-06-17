<?php

namespace App\Http\Controllers\V2\Admin;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeShortCode;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
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
            'quoteTypes' => $quoteTypes
        ]);
    }

    public function getLatestConfigByQuoteType(Request $request)
    {
        $quoteTypeId = $request->input('quote_type_id');

        if (! $quoteTypeId) {
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
