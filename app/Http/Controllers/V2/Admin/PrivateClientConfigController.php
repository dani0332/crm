<?php

namespace App\Http\Controllers\V2\Admin;

use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Models\CarMake;
use App\Models\InsuranceProvider;
use App\Models\PrivateClientConfig;
use App\Models\PrivateClientConfigHistory;
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
        $locationAreas = SubArea::select('code as value', 'text as label')->get()->toArray();

        return Inertia::render('Admin/PrivateClientConfig/Show', [
            'configurations' => $configurations,
            'carMakes' => $carMakes,
            'insurers' => $insurers,
            'locationAreas' => $locationAreas,
        ]);
    }

    public function upsert(Request $request)
    {
        try {
            // Validate incoming request
            $validated = $request->validate([
                'configurations' => 'required|array',
                'configurations.*.quote_type_id' => 'required|integer',
                'configurations.*.field_name' => 'required|string|max:255',
                'configurations.*.operator' => 'required|string',
                'configurations.*.value' => 'nullable|string',
                'configurations.*.currency_type_id' => 'nullable|integer',
                'configurations.*.status' => 'required|integer|in:0,1',
            ]);

            DB::beginTransaction();

            foreach ($validated['configurations'] as $config) {
                $existingConfig = PrivateClientConfig::where([
                    'quote_type_id' => $config['quote_type_id'],
                    'field_name' => $config['field_name'],
                    'currency_type_id' => $config['currency_type_id'] ?? null,
                ])->first();

                if ($existingConfig) {
                    $hasChanges = $existingConfig->operator != $config['operator'] ||
                        $existingConfig->value != $config['value'] ||
                        $existingConfig->status != $config['status'];

                    if ($hasChanges) {
                        PrivateClientConfigHistory::create([
                            'pcp_config_id' => $existingConfig->id,
                            'quote_type_id' => $existingConfig->quote_type_id,
                            'field_name' => $existingConfig->field_name,
                            'operator' => $existingConfig->operator,
                            'value' => $existingConfig->value,
                            'currency_type_id' => $existingConfig->currency_type_id,
                            'status' => $existingConfig->status,
                            'version' => $existingConfig->version,
                        ]);

                        // Update with incremented version
                        $existingConfig->update([
                            'operator' => $config['operator'],
                            'field_name' => $config['field_name'],
                            'operator' => $config['operator'],
                            'value' => $config['value'],
                            'status' => $config['status'],
                            'currency_type_id' => $config['currency_type_id'] ?? null,
                            'version' => $existingConfig->version + 1,
                        ]);
                    }
                } else {
                    PrivateClientConfig::create([
                        'quote_type_id' => $config['quote_type_id'],
                        'field_name' => $config['field_name'],
                        'operator' => $config['operator'],
                        'value' => $config['value'],
                        'currency_type_id' => $config['currency_type_id'] ?? null,
                        'status' => $config['status'],
                        'version' => 1,
                    ]);
                }
            }

            DB::commit();

            return redirect()->back()->with('message', 'Private Client Configuration updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'An error occurred while updating the configuration: '.$e->getMessage());
        }
    }
}
