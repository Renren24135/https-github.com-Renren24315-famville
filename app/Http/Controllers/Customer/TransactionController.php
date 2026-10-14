<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(): View
    {
        $query = Transaction::query()->where('user_id', auth()->id());

        // Computed on the unpaginated query so "Total Spent" reflects every
        // transaction, not just the 10 shown on the current page.
        $totalSpent = (clone $query)->sum('amount');
        $transactionCount = (clone $query)->count();

        $transactions = $query->with('booking.bookable')->latest()->paginate(10);

        return view('customer.transactions.index', compact('transactions', 'totalSpent', 'transactionCount'));
    }
}
