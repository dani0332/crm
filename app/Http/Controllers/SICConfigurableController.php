<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\SicConfigRequest;
use App\Models\HealthPlanType;
use App\Repositories\NationalityRepository;
use App\Services\LookupService;
use App\Services\SICConfigurableService;

class SICConfigurableController extends Controller
{
    //
    public $sicConfigurableService;
    public function __construct(SICConfigurableService $sicConfigurableService)
    {
        $this->middleware('permission:'.PermissionsEnum::SIC_HEALTH_CONFIG, ['only' => ['index', 'store']]);
        $this->sicConfigurableService = $sicConfigurableService;

    }

    public function index()
    {
        $sicConfigurable = $this->sicConfigurableService->getEntity();

        return inertia('Admin/SICHealth/SicHealthConfigForm', [
            'nationalities' => NationalityRepository::withActive()->get(),
            'sicConfigurable' => $sicConfigurable->data,
            'relations' => $sicConfigurable->relations,
            'healthTypes' => HealthPlanType::all(),
            'memberCategories' => app(LookupService::class)->getMemberCategories(),
        ]);
    }

    public function store(SicConfigRequest $request)
    {
        $this->sicConfigurableService->saveEntity($request);

        return redirect()->route('admin.sic-health-config.index')->with('success', 'SIC Health Config saved successfully');

    }
}
