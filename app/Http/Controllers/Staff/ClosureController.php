<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClosureRequest;
use App\Http\Requests\UpdateClosureRequest;
use App\Models\AssetClosure;
use App\Models\BilliardTable;
use App\Models\Lane;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClosureController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage-closures');

        $closures = AssetClosure::query()->with(['creator'])->latest('start_date')->get();
        $lanes = Lane::orderedByName();
        $tables = BilliardTable::orderedByName();

        return view('staff.closures.index', compact('closures', 'lanes', 'tables'));
    }

    public function store(StoreClosureRequest $request): RedirectResponse
    {
        $data = $request->validated();

        AssetClosure::create([
            'bookable_type' => $data['facility'] === 'lane' ? Lane::class : BilliardTable::class,
            'bookable_id' => $data['asset_id'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'],
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', 'Closure added.');
    }

    public function update(UpdateClosureRequest $request, AssetClosure $closure): RedirectResponse
    {
        $data = $request->validated();

        $closure->update([
            'bookable_type' => $data['facility'] === 'lane' ? Lane::class : BilliardTable::class,
            'bookable_id' => $data['asset_id'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'],
        ]);

        return back()->with('status', 'Closure updated.');
    }

    public function destroy(AssetClosure $closure): RedirectResponse
    {
        $this->authorize('manage-closures');

        $closure->delete();

        return back()->with('status', 'Closure removed.');
    }
}
