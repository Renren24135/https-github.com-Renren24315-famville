<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashCountRequest;
use App\Mail\CashCountReportMail;
use App\Models\CashCount;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class CashCountController extends Controller
{
    /**
     * Record the cashier's end-of-shift denomination count, email it to
     * management, and log the cashier out — all in one submission, so the
     * count can never be skipped by dismissing the modal.
     */
    public function store(StoreCashCountRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $now = now();

        $quantities = [
            1 => (int) $data['count_1'],
            5 => (int) $data['count_5'],
            10 => (int) $data['count_10'],
            20 => (int) $data['count_20'],
            100 => (int) $data['count_100'],
            200 => (int) $data['count_200'],
            500 => (int) $data['count_500'],
            1000 => (int) $data['count_1000'],
        ];

        $totalCounted = CashCount::totalFor($quantities);
        $expectedAmount = (float) ReportService::todaySummary($user->id)['totalRevenue'];

        $cashCount = CashCount::create([
            'user_id' => $user->id,
            'count_1' => $quantities[1],
            'count_5' => $quantities[5],
            'count_10' => $quantities[10],
            'count_20' => $quantities[20],
            'count_100' => $quantities[100],
            'count_200' => $quantities[200],
            'count_500' => $quantities[500],
            'count_1000' => $quantities[1000],
            'total_counted' => $totalCounted,
            'expected_amount' => $expectedAmount,
            'difference' => round($totalCounted - $expectedAmount, 2),
            'counted_at' => $now,
        ]);

        $recipients = ReportService::recipientEmails();

        if ($recipients->isNotEmpty()) {
            $this->queueMailSafely(fn () => Mail::to($recipients->first())
                ->cc($recipients->skip(1)->all())
                ->queue(new CashCountReportMail($cashCount->load('user'))));
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Cash count recorded and emailed to management. You have been logged out.');
    }
}
