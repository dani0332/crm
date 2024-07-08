<?php

namespace App\Http\Controllers;

use App\Repositories\NationalityRepository;
use App\Services\LookupService;
use App\Services\SICHealthConfigService;
use Illuminate\Http\Request;

class SICHealthController extends Controller
{
    //
    private $sicHealthConfigService;
    public function __construct(SICHealthConfigService $sicHealthConfigService)
    {
        $this->sicHealthConfigService = $sicHealthConfigService;
    }

    public function sicHealthConfig()
    {
        $nationalities = NationalityRepository::withActive()->get();
        $sicHealthConfig = $this->sicHealthConfigService->getEntity();

        return inertia('Admin/SICHealth/SicHealthConfigForm', [
            'nationalities' => $nationalities,
            'sicHealthConfig' => $sicHealthConfig,
            'memberCategories' => app(LookupService::class)->getMemberCategories(),
        ]);
    }

    public function sicHealthConfigStore(Request $request)
    {
        $this->sicHealthConfigService->saveEntity($request->id ?? null, (object) request()->all());

        return redirect()->route('admin.sic-health-config')->with('success', 'SIC Health Config saved successfully');

    }
}
