<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(): View
    {
        $transactions = Transaction::query()
            ->where('user_id', auth()->id())
            ->with('booking.bookable')
            ->latest()
            ->paginate(10);

        return view('customer.transactions.index', compact('transactions'));
    }
}
