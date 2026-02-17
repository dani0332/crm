<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2\Admin;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuyLeads\AdminBuyLeadIndexRequest;
use App\Models\BuyLeadRequest;
use App\Models\User;
use App\Services\BuyLeads\BuyLeadService;
use Illuminate\Http\JsonResponse;

class AdminBuyLeadController extends Controller
{
    public function __construct(
        private readonly BuyLeadService $buyLeadService
    ) {
        $this->middleware('permission:'.PermissionsEnum::BUY_LEADS_ADMIN);
    }

    /**
     * Display the admin buy leads requests page with filters
     */
    public function index(AdminBuyLeadIndexRequest $request)
    {
        $filters = $request->only(['user_id', 'quote_type', 'status', 'request_type', 'date']);

        $data['requests'] = $this->buyLeadService->getAllRequestsForAdmin($filters);
        $data['filters'] = $filters;

        $data['users'] = User::whereHas('buyLeadRequests')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => [
                'label' => "$user->name ({$user->email})",
                'value' => $user->id,
            ]);

        return inertia('Admin/BuyLeadRequests/Index', $data);
    }

    /**
     * Expire a buy lead request
     */
    public function expire(BuyLeadRequest $buyLeadRequest): JsonResponse
    {
        if (! $buyLeadRequest->can_be_expired) {
            return response()->json([
                'message' => 'This request cannot be expired, it is already completed or expired.',
            ], 422);
        }

        $success = $buyLeadRequest->expire();

        if ($success) {
            return response()->json([
                'message' => 'Buy lead request expired successfully.',
            ]);
        }

        return response()->json([
            'message' => 'Failed to expire buy lead request.',
        ], 500);
    }
}
