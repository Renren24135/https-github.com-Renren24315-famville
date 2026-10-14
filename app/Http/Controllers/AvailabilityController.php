<?php

namespace App\Http\Controllers;

use App\Models\BilliardTable;
use App\Models\Lane;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AvailabilityController extends Controller
{
    /**
     * Public timetable lookup — used by the "check availability" popup on
     * the lane/table cards for both the marketing page and logged-in
     * dashboards, so it deliberately has no auth requirement.
     */
    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        if (! in_array($type, ['lane', 'table'], true)) {
            throw ValidationException::withMessages(['type' => 'Invalid asset type.']);
        }

        $asset = $type === 'lane'
            ? Lane::findOrFail($id)
            : BilliardTable::findOrFail($id);

        $date = $validated['date'] ?? now()->toDateString();

        return response()->json([
            'asset_name' => $asset->name,
            'date' => $date,
            'slots' => AvailabilityService::timetableFor($asset, $date),
        ]);
    }
}
