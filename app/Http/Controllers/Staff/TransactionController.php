<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view-reports');

        $filters = $request->only(['search', 'from', 'to', 'payment_method']);

        $query = Transaction::query()
            ->with(['booking.bookable', 'customer', 'cashier'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('receipt_no', 'like', "%{$search}%")
                        ->orWhereHas('booking', fn ($b) => $b->where('customer_name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($filters['payment_method'] ?? null, fn ($q, $method) => $q->where('payment_method', $method))
            ->latest();

        $totalAmount = (clone $query)->sum('amount');
        $totalCount = (clone $query)->count();

        $transactions = $query->paginate(20)->withQueryString();

        return view('staff.transactions.index', compact('transactions', 'filters', 'totalAmount', 'totalCount'));
    }
}
