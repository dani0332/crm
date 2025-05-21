<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetAdvisorsByQuoteTypeRequest;
use App\Models\User;

class AdvisorController extends Controller
{
    public function getAdvisorsByQuoteType(GetAdvisorsByQuoteTypeRequest $request)
    {
        $advisorRoles = $request->getQuoteType()->advisorRoles();

        $users = User::whereHas('roles', function ($query) use ($advisorRoles) {
            $query->whereIn('name', $advisorRoles);
        })
            ->activeUser()
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
