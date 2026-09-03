<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWalkInBookingRequest;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Models\BilliardTable;
use App\Models\Booking;
use App\Models\Lane;
use App\Services\BookingCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-bookings');

        $status = $request->query('status', 'pending');

        $bookings = Booking::query()
            ->with(['bookable', 'customer'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('booking_date')
            ->latest('start_time')
            ->paginate(15)
            ->withQueryString();

        return view('staff.bookings.index', compact('bookings', 'status'));
    }

    public function storeWalkIn(StoreWalkInBookingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $asset = $data['asset_type'] === 'lane'
            ? Lane::findOrFail($data['asset_id'])
            : BilliardTable::findOrFail($data['asset_id']);

        if ($asset->status !== \App\Enums\AssetStatus::Available) {
            return back()->withErrors(['asset_id' => 'That '.$data['asset_type'].' is not currently available.']);
        }

        if ($asset instanceof BilliardTable && ! \App\Models\BilliardRateTier::rateForTime(now()->format('H:i:s'))) {
            return back()->withErrors(['asset_id' => 'There is no billiard rate configured for the current time. Please add a matching rate tier first.']);
        }

        $booking = Booking::create([
            'bookable_type' => $asset::class,
            'bookable_id' => $asset->id,
            'user_id' => null,
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'source' => 'walkin',
            'status' => 'pending',
            'booking_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'scoresheets' => $data['scoresheets'] ?? null,
            'hours' => $data['hours'] ?? null,
            'payment_status' => 'unpaid',
            'created_by' => auth()->id(),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        BookingCalculator::startSession($booking->fresh());

        return back()->with('status', 'Walk-in session started for '.$asset->name.'.');
    }

    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking): RedirectResponse
    {
        $data = $request->validated();
        $asset = $booking->bookable;

        switch ($data['status']) {
            case 'approved':
                $booking->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
                break;

            case 'occupied':
                BookingCalculator::startSession($booking);
                break;

            case 'completed':
                $booking->update(['status' => 'completed']);
                $asset->update(['status' => 'available']);
                break;

            case 'rejected':
                $booking->update([
                    'status' => 'rejected',
                    'cancel_reason' => $data['cancel_reason'] ?? null,
                ]);
                break;

            case 'cancelled':
                $booking->update([
                    'status' => 'cancelled',
                    'cancel_reason' => $data['cancel_reason'] ?? null,
                ]);
                $asset->update(['status' => 'available']);
                break;
        }

        return back()->with('status', 'Booking updated.');
    }
}
