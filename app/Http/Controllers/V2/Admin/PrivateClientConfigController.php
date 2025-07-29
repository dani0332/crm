<?php

namespace App\Http\Controllers\V2\Admin;

use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V2\Admin\PrivateClientConfig\GetPrivateClientConfigRequest;
use App\Http\Requests\V2\Admin\PrivateClientConfig\PrivateClientConfigRequest;
use App\Services\Logger\LoggerService;
use App\Services\PrivateClientConfigService;
use Illuminate\Support\Arr;
use Inertia\Inertia;

class PrivateClientConfigController extends Controller
{
    public function __construct(protected PrivateClientConfigService $configService)
    {
        $this->middleware('role:'.Arr::join([RolesEnum::SeniorManagement, RolesEnum::Admin], '|'), ['only' => ['show', 'upsert']]);
    }

    public function show()
    {
        $quoteTypes = $this->configService->getAllowedQuoteTypes();

        return Inertia::render('Admin/PrivateClientConfig/Show', [
            'quoteTypes' => $quoteTypes,
        ]);
    }

    public function getLatestConfigByQuoteType(GetPrivateClientConfigRequest $request)
    {
        try {
            $quoteTypeId = $request->quote_type_id;
            $version = $request->version;

            $responseData = $this->configService->getLatestConfigByQuoteType($quoteTypeId, $version);

            return response()->json($responseData);
        } catch (\Exception $e) {
            LoggerService::error('Failed to load private client configuration', [
                'quote_type_id' => $request->quote_type_id,
                'version' => $request->version,
            ], $e);

            return response()->json(['error' => 'Failed to load configuration'], 500);
        }
    }

    public function upsert(PrivateClientConfigRequest $request)
    {
        try {
            $validated = $request->validated();

            $config = $this->configService->createNewConfigurationVersion($validated);

            return redirect()->route('admin.private-client-config.show')
                ->with('message', 'Private Client Configuration updated successfully.')
                ->with('version', $config->version);

        } catch (\Exception $e) {
            LoggerService::error('Failed to update private client configuration', [
                'quote_type_id' => $request->quote_type_id,
                'version' => $request->version,
            ], $e);

            return redirect()->back()->with('error', 'An error occurred while updating the configuration: '.$e->getMessage());
        }
    }
}
