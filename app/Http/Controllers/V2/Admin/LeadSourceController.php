<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadSourceRequest;
use App\Models\LeadSource;
use Illuminate\Http\JsonResponse;

class LeadSourceController extends Controller
{
    /**
     * Store a newly created lead source in storage.
     */
    public function store(LeadSourceRequest $request): JsonResponse
    {
        $leadSource = LeadSource::create([
            'name' => $request->name,
            'code' => $request->code ?? null, // Accept code from request (can be null)
            'is_active' => true, // Always set to true
            'is_applicable_for_rules' => true, // Always set to true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lead source created successfully.',
            'data' => [
                'id' => $leadSource->id,
                'name' => $leadSource->name,
                'code' => $leadSource->code,
                'is_active' => $leadSource->is_active,
                'is_applicable_for_rules' => $leadSource->is_applicable_for_rules,
            ],
        ], 201);
    }
}
