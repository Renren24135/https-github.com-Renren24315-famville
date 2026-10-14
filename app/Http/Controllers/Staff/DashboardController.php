<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Mail\ScheduledReportMail;
use App\Models\BilliardTable;
use App\Models\Booking;
use App\Models\Lane;
use App\Models\Transaction;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function index(): View
    {
        $isAdmin = auth()->user()->hasRole('admin');
        $cashierId = $isAdmin ? null : auth()->id();

        $summary = ReportService::todaySummary($cashierId);

        $todayRevenue = $summary['totalRevenue'];
        $todayTransactionCount = $summary['transactionCount'];
        $pendingCount = $summary['pendingCount'];
        $occupiedCount = $summary['occupiedCount'];
        $totalAssets = $summary['totalAssets'];
        $revenueByDayFor = function (?string $bookableClass) use ($cashierId) {
            return collect(range(6, 0))->map(function (int $daysAgo) use ($cashierId, $bookableClass) {
                $date = now()->subDays($daysAgo);

                return [
                    'label' => $date->format('M d'),
                    'total' => (float) Transaction::whereDate('created_at', $date)
                        ->when($bookableClass, fn ($q) => $q->whereHas('booking', fn ($q2) => $q2->where('bookable_type', $bookableClass)))
                        ->when($cashierId, fn ($q) => $q->where('cashier_id', $cashierId))
                        ->sum('amount'),
                ];
            });
        };
        $revenueByDay = $revenueByDayFor(null);
        $revenueByDayLane = $revenueByDayFor(Lane::class);
        $revenueByDayTable = $revenueByDayFor(BilliardTable::class);
        $peakHours = $summary['peakHours'];
        $peakHoursLane = $summary['peakHoursLane'];
        $peakHoursTable = $summary['peakHoursTable'];
        $laneUtilization = $summary['laneUtilization'];
        $tableUtilization = $summary['tableUtilization'];
        $recentTransactions = $summary['transactions']->sortByDesc('created_at')->take(8)->values();

        // Live venue board — every lane/table's current status at a glance,
        // the signature "venue control board" the cashier dashboard needs.
        $lanes = Lane::orderedByName();
        $tables = BilliardTable::orderedByName();

        // A short, actionable queue right on the dashboard — same
        // visibility rule as Staff\BookingController::index() (an
        // unclaimed pending online booking is shared, everything else is
        // scoped to whoever claimed it), just capped to the 5 most urgent.
        $userId = auth()->id();
        $pendingBookings = Booking::query()
            ->with(['bookable', 'customer'])
            ->where('status', 'pending')
            ->when(! $isAdmin, function ($q) use ($userId) {
                $q->where(function ($q2) use ($userId) {
                    $q2->where(function ($q3) {
                        $q3->where('status', 'pending')->where('source', 'online');
                    })->orWhere('approved_by', $userId)->orWhere('created_by', $userId);
                });
            })
            ->oldest('booking_date')->oldest('start_time')
            ->take(5)
            ->get();

        return view('staff.dashboard', compact(
            'isAdmin',
            'todayRevenue',
            'todayTransactionCount',
            'pendingCount',
            'occupiedCount',
            'totalAssets',
            'revenueByDay',
            'revenueByDayLane',
            'revenueByDayTable',
            'peakHours',
            'peakHoursLane',
            'peakHoursTable',
            'laneUtilization',
            'tableUtilization',
            'recentTransactions',
            'lanes',
            'tables',
            'pendingBookings',
        ));
    }

    public function emailReportNow(): RedirectResponse
    {
        $this->authorize('send-reports');

        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');

        // Both admin and cashier reports go to the configured recipients
        // (e.g. the owner's inbox) — a cashier can't email a report to just
        // anyone, only to management. What differs is the content: admin
        // gets the full business-wide report, a cashier's report is scoped
        // to only the sales they personally processed, labeled with their
        // name so management knows whose figures they're looking at.
        $recipients = ReportService::recipientEmails();

        if ($recipients->isEmpty()) {
            return back()->withErrors(['report' => 'No report recipients configured. Add one in Settings first.']);
        }

        $summary = ReportService::todaySummary($isAdmin ? null : $user->id);
        $recipientLabel = $isAdmin ? null : $user->name;

        try {
            Mail::to($recipients->first())
                ->cc($recipients->skip(1)->all())
                ->send(new ScheduledReportMail($summary, 'On-Demand', $recipientLabel));
        } catch (Throwable $e) {
            Log::error('Failed to send on-demand report email: '.$e->getMessage(), ['exception' => $e]);

            return back()->withErrors(['report' => 'Could not send the email right now — please try again in a moment.']);
        }

        return back()->with('status', 'Report emailed to: '.$recipients->implode(', '));
    }
}
