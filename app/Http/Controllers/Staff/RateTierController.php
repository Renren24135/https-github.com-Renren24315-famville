<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRateTierRequest;
use App\Http\Requests\UpdateRateTierRequest;
use App\Models\BilliardRateTier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RateTierController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage-rates');

        $rateTiers = BilliardRateTier::query()->orderBy('start_time')->get();

        return view('staff.rate-tiers.index', compact('rateTiers'));
    }

    public function store(StoreRateTierRequest $request): RedirectResponse
    {
        BilliardRateTier::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('status', 'Rate tier added.');
    }

    public function update(UpdateRateTierRequest $request, BilliardRateTier $rate_tier): RedirectResponse
    {
        $rate_tier->update($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('status', 'Rate tier updated.');
    }

    public function destroy(BilliardRateTier $rate_tier): RedirectResponse
    {
        $this->authorize('manage-rates');

        $rate_tier->delete();

        return back()->with('status', 'Rate tier removed.');
    }
}
