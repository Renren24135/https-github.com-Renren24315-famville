<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBilliardTableRequest;
use App\Http\Requests\UpdateBilliardTableRequest;
use App\Models\BilliardTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BilliardTableController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage-tables');

        $tables = BilliardTable::orderedByName();

        return view('staff.billiard-tables.index', compact('tables'));
    }

    public function store(StoreBilliardTableRequest $request): RedirectResponse
    {
        BilliardTable::create($request->validated());

        return back()->with('status', 'Billiard table added.');
    }

    public function update(UpdateBilliardTableRequest $request, BilliardTable $billiard_table): RedirectResponse
    {
        $billiard_table->update($request->validated());

        return back()->with('status', 'Billiard table updated.');
    }

    public function destroy(BilliardTable $billiard_table): RedirectResponse
    {
        $this->authorize('manage-tables');

        $billiard_table->delete();

        return back()->with('status', 'Billiard table removed.');
    }
}
