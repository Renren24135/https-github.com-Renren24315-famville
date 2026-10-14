<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLaneRequest;
use App\Http\Requests\UpdateLaneRequest;
use App\Models\Lane;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LaneController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage-lanes');

        $lanes = Lane::orderedByName();

        return view('staff.lanes.index', compact('lanes'));
    }

    public function store(StoreLaneRequest $request): RedirectResponse
    {
        Lane::create($request->validated());

        return back()->with('status', 'Lane added.');
    }

    public function update(UpdateLaneRequest $request, Lane $lane): RedirectResponse
    {
        $lane->update($request->validated());

        return back()->with('status', 'Lane updated.');
    }

    public function destroy(Lane $lane): RedirectResponse
    {
        $this->authorize('manage-lanes');

        $lane->delete();

        return back()->with('status', 'Lane removed.');
    }
}
