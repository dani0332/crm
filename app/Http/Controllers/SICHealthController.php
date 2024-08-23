<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enums\PermissionsEnum;
use App\Models\HealthPlanType;
use App\Services\LookupService;
use App\Services\SICHealthConfigService;
use App\Repositories\NationalityRepository;

class SICHealthController extends Controller
{
    //
    public $sicHealthConfigService;
    public function __construct(SICHealthConfigService $sicHealthConfigService)
    {
        $this->middleware('permission:'.PermissionsEnum::SIC_HEALTH_CONFIG, ['only' => ['index', 'store',]]);
        $this->sicHealthConfigService = $sicHealthConfigService;

    }

    public function index()
    {
        $sicHealthConfig = $this->sicHealthConfigService->getEntity();
        $healthTypes = HealthPlanType::all();

        return inertia('Admin/SICHealth/SicHealthConfigForm', [
            'nationalities' => NationalityRepository::withActive()->get(),
            'sicHealthConfig' => $sicHealthConfig->data,
            'relations' => $sicHealthConfig->relations,
            'healthTypes' => $healthTypes,
            'memberCategories' => app(LookupService::class)->getMemberCategories(),
        ]);
    }

    public function store(Request $request)
    {
        $this->sicHealthConfigService->saveEntity($request->id ?? null, request()->all());

        return redirect()->route('admin.sic-health-config.index')->with('success', 'SIC Health Config saved successfully');

    }
}
