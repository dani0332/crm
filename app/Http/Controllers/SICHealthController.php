<?php

namespace App\Http\Controllers;

use App\Repositories\NationalityRepository;
use App\Services\LookupService;
use App\Services\SICHealthConfigService;
use Illuminate\Http\Request;

class SICHealthController extends Controller
{
    //
    public $sicHealthConfigService;
    public function __construct(SICHealthConfigService $sicHealthConfigService)
    {
        $this->sicHealthConfigService = $sicHealthConfigService;
    }

    public function index()
    {
        $sicHealthConfig = $this->sicHealthConfigService->getEntity();

        return inertia('Admin/SICHealth/SicHealthConfigForm', [
            'nationalities' => NationalityRepository::withActive()->get(),
            'sicHealthConfig' => $sicHealthConfig,
            'memberCategories' => app(LookupService::class)->getMemberCategories(),
        ]);
    }

    public function store(Request $request)
    {
        $this->sicHealthConfigService->saveEntity($request->id ?? null, request()->all());

        return redirect()->route('admin.sic')->with('success', 'SIC Health Config saved successfully');

    }
}
