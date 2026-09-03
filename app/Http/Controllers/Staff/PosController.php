<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Models\Booking;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        $this->authorize('process-pos');

        $pending = Booking::query()
            ->with('bookable')
            ->where('payment_status', 'unpaid')
            ->whereIn('status', ['occupied', 'completed'])
            ->latest('session_started_at')
            ->get();

        return view('staff.pos.index', compact('pending'));
    }

    public function pay(ProcessPaymentRequest $request, Booking $booking): RedirectResponse
    {
        abort_if($booking->payment_status === \App\Enums\PaymentStatus::Paid, 422, 'Already paid.');

        $data = $request->validated();
        $tendered = (float) $data['cash_tendered'];

        $transaction = Transaction::create([
            'receipt_no' => Transaction::nextReceiptNo(),
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'cashier_id' => auth()->id(),
            'amount' => $booking->total_amount,
            'cash_tendered' => $tendered,
            'change_due' => round($tendered - (float) $booking->total_amount, 2),
            'payment_method' => $data['payment_method'],
            'status' => 'paid',
        ]);

        $booking->update(['payment_status' => 'paid']);

        return redirect()->route('staff.pos.receipt', $transaction)->with('status', 'Payment recorded.');
    }

    public function receipt(Transaction $transaction): View
    {
        $this->authorize('process-pos');

        $transaction->load(['booking.bookable', 'cashier', 'customer']);

        return view('staff.pos.receipt', compact('transaction'));
    }
}
