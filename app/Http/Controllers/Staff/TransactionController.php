<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\BilliardTable;
use App\Models\Lane;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view-reports');

        $filters = $request->only(['search', 'from', 'to', 'payment_method']);
        $type = $request->query('type', 'all');
        $isAdmin = auth()->user()->hasRole('admin');

        // Whether the visitor changed anything from the plain "today" default
        // — used to decide whether to show the "Clear filters" button, since
        // the auto-applied today date range shouldn't count as an active filter.
        $hasActiveFilters = $request->boolean('all_time')
            || $request->filled('search') || $request->filled('from') || $request->filled('to')
            || $request->filled('payment_method') || $type !== 'all';

        // "This Month" (the param is still named all_time for backwards
        // compatibility) resets to zero every new month by construction —
        // it's just the current calendar month's transactions, not truly
        // unlimited. The month that just closed isn't lost, though: it's
        // snapshotted into monthly_revenue_archives (see Monthly Archive)
        // the moment the new month starts. A custom from/to still overrides
        // either default, so a wider historical search is still possible.
        $viewingMonth = $request->boolean('all_time');
        if (! $viewingMonth) {
            $filters['from'] = $filters['from'] ?? now()->toDateString();
            $filters['to'] = $filters['to'] ?? now()->toDateString();
        } else {
            $filters['from'] = $filters['from'] ?? now()->startOfMonth()->toDateString();
            $filters['to'] = $filters['to'] ?? now()->endOfMonth()->toDateString();
        }

        $query = Transaction::query()
            ->with(['booking.bookable', 'booking.transactions', 'customer', 'cashier'])
            // A reservation-fee-only transaction (session balance left for
            // walk-in payment at the counter) isn't the full picture yet —
            // keep it out of Payment History until the booking's actually
            // completed and the whole amount has been collected, instead of
            // showing a confusing partial receipt.
            ->where(function ($q) {
                $q->whereDoesntHave('booking', function ($b) {
                    $b->where('payment_status', 'unpaid')
                        ->where('reservation_fee', '>', 0)
                        ->whereColumn('reservation_fee', 'transactions.amount');
                });
            })
            ->when(! $isAdmin, fn ($q) => $q->where('cashier_id', auth()->id()))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('receipt_no', 'like', "%{$search}%")
                        ->orWhereHas('booking', fn ($b) => $b->where('customer_name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($filters['payment_method'] ?? null, fn ($q, $method) => $q->where('payment_method', $method))
            ->when($type === 'lane', fn ($q) => $q->whereHas('booking', fn ($b) => $b->where('bookable_type', Lane::class)))
            ->when($type === 'table', fn ($q) => $q->whereHas('booking', fn ($b) => $b->where('bookable_type', BilliardTable::class)))
            ->latest();

        // The amount total still sums every collected payment (so a fee
        // paid separately from the balance still counts toward revenue),
        // but a booking paid in two parts (fee + balance) is one sale from
        // the business's perspective — the transaction count and the
        // listing both fold it down to the single, later row instead of
        // counting/showing it twice.
        $totalAmount = (clone $query)->sum('amount');

        $dedupedQuery = (clone $query)->where(function ($q) {
            $q->whereNull('booking_id')
                ->orWhereIn('id', function ($sub) {
                    $sub->selectRaw('MAX(id)')
                        ->from('transactions as t2')
                        ->whereColumn('t2.booking_id', 'transactions.booking_id');
                });
        });

        $totalCount = (clone $dedupedQuery)->count();
        $transactions = (clone $dedupedQuery)->paginate(20)->withQueryString();

        return view('staff.transactions.index', compact('transactions', 'filters', 'totalAmount', 'totalCount', 'isAdmin', 'type', 'viewingMonth', 'hasActiveFilters'));
    }
}
