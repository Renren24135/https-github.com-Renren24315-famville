<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\BilliardTable;
use App\Models\Booking;
use App\Models\Lane;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();

        $todayRevenue = Transaction::whereDate('created_at', $today)->sum('amount');
        $pendingCount = Booking::where('status', 'pending')->count();
        $occupiedCount = Lane::where('status', 'occupied')->count() + BilliardTable::where('status', 'occupied')->count();
        $totalAssets = Lane::count() + BilliardTable::count();

        $revenueByDay = collect(range(6, 0))->map(function (int $daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);

            return [
                'label' => $date->format('M d'),
                'total' => (float) Transaction::whereDate('created_at', $date)->sum('amount'),
            ];
        });

        $peakHours = Booking::query()
            ->whereIn('status', ['occupied', 'completed'])
            ->whereNotNull('start_time')
            ->selectRaw('HOUR(start_time) as hour, COUNT(*) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn ($row) => ['hour' => (int) $row->hour, 'total' => (int) $row->total]);

        $laneUtilization = Lane::withCount(['bookings as completed_count' => function ($q) {
            $q->whereIn('status', ['occupied', 'completed']);
        }])->get();

        $tableUtilization = BilliardTable::withCount(['bookings as completed_count' => function ($q) {
            $q->whereIn('status', ['occupied', 'completed']);
        }])->get();

        $recentTransactions = Transaction::with(['booking.bookable', 'cashier'])->latest()->limit(8)->get();

        return view('staff.dashboard', compact(
            'todayRevenue',
            'pendingCount',
            'occupiedCount',
            'totalAssets',
            'revenueByDay',
            'peakHours',
            'laneUtilization',
            'tableUtilization',
            'recentTransactions',
        ));
    }
}
