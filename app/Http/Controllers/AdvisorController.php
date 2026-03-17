<?php

namespace App\Http\Controllers;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Requests\GetAdvisorsByQuoteTypeRequest;
use App\Models\User;

class AdvisorController extends Controller
{
    public function getAdvisorsByQuoteType(GetAdvisorsByQuoteTypeRequest $request)
    {
        $advisorRoles = $request->getQuoteType()->advisorRoles();
        $departmentIds = $request->getDepartmentIds();

        if ($request->getQuoteType() === QuoteTypes::SAVINGS) {
            $advisorRoles = [...$advisorRoles, RolesEnum::SavingsManager];
        }

        $users = User::whereHas('roles', function ($query) use ($advisorRoles) {
            $query->whereIn('name', $advisorRoles);
        });

        if (! empty($departmentIds)) {
            $users->whereHas('departments', function ($query) use ($departmentIds) {
                $query->whereIn('departments.id', $departmentIds);
            });
        }

        $users->activeUser()
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
