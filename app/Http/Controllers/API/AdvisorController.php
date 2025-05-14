<?php

namespace App\Http\Controllers\API;

use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdvisorController extends Controller
{
    /**
     * Get advisors based on quote type
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAdvisorsByQuoteType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quote_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $quoteType = $request->input('quote_type');

        // Try to find the QuoteTypes enum value
        $quoteTypeEnum = QuoteTypes::tryFrom($quoteType);

        if (! $quoteTypeEnum) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid quote type provided',
            ], 422);
        }

        // Get advisor roles for this quote type
        $advisorRoles = $quoteTypeEnum->advisorRoles();

        if (empty($advisorRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'No advisor roles found for this quote type',
            ], 404);
        }

        // Get users with these roles
        $users = User::whereHas('roles', function ($query) use ($advisorRoles) {
            $query->whereIn('name', $advisorRoles);
        })
            ->where('is_active', 1)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
            'count' => $users->count(),
        ]);
    }
}
