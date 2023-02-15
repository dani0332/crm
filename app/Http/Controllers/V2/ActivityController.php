<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityRequest;
use App\Repositories\ActivityRepository;
use App\Repositories\PersonalQuoteRepository;

class ActivityController extends Controller
{
    /**
     * @param ActivityRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ActivityRequest $request)
    {
        ActivityRepository::create($request->validated());
        return back()->with('message' , 'Activity created successfully');
    }

    /**
     * @param ActivityRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($id, ActivityRequest $request)
    {
        ActivityRepository::update($id, $request->validated());
        return back()->with('message' , 'Activity updated successfully');
    }
}
