<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .subtitle { color: #666; margin-top: 2px; margin-bottom: 18px; }
        .kpis { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .kpis td { width: 25%; padding: 10px; border: 1px solid #ddd; text-align: center; }
        .kpis .value { font-size: 16px; font-weight: bold; display: block; }
        .kpis .label { font-size: 10px; color: #666; }
        h2 { font-size: 14px; border-bottom: 1px solid #ccc; padding-bottom: 4px; margin-top: 22px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { border: 1px solid #ddd; padding: 5px 8px; text-align: left; font-size: 11px; }
        table.data th { background: #f2f2f2; }
        .text-end { text-align: right; }
        .muted { color: #888; }
    </style>
</head>
<body>
    @if ($recipientLabel ?? null)
        <h1>{{ $companyName }} — Sales Report for {{ $recipientLabel }}</h1>
        <p class="subtitle">{{ $from->format('M d, Y') }} &ndash; {{ $to->format('M d, Y') }} &middot; Generated {{ now()->format('M d, Y g:i A') }} &middot; Reflects only transactions processed by {{ $recipientLabel }}</p>
    @else
        <h1>{{ $companyName }} — Business Report</h1>
        <p class="subtitle">{{ $from->format('M d, Y') }} &ndash; {{ $to->format('M d, Y') }} &middot; Generated {{ now()->format('M d, Y g:i A') }}</p>
    @endif

    <table class="kpis">
        <tr>
            <td><span class="value">&#8369;{{ number_format($summary['totalRevenue'], 2) }}</span><span class="label">{{ ($recipientLabel ?? null) ? strtoupper($recipientLabel).'\'S REVENUE' : 'TOTAL REVENUE' }}</span></td>
            <td><span class="value">{{ $summary['transactionCount'] }}</span><span class="label">TRANSACTIONS</span></td>
            @unless ($recipientLabel ?? null)
                <td><span class="value">{{ $summary['occupiedCount'] }}/{{ $summary['totalAssets'] }}</span><span class="label">ASSETS OCCUPIED (NOW)</span></td>
                <td><span class="value">{{ $summary['pendingCount'] }}</span><span class="label">PENDING BOOKINGS (NOW)</span></td>
            @endunless
        </tr>
    </table>

    <h2>Revenue by Day</h2>
    <table class="data">
        <thead><tr><th>Date</th><th class="text-end">Revenue</th></tr></thead>
        <tbody>
            @foreach ($summary['revenueByDay'] as $day)
                <tr><td>{{ $day['label'] }}</td><td class="text-end">&#8369;{{ number_format($day['total'], 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    @if ($recipientLabel ?? null)
        <p class="muted">The sections below reflect overall venue activity, not just {{ $recipientLabel }}'s sessions.</p>
    @endif

    <h2>Peak Hours (sessions started)</h2>
    <table class="data">
        <thead><tr><th>Hour</th><th class="text-end">Sessions</th></tr></thead>
        <tbody>
            @forelse ($summary['peakHours'] as $row)
                <tr><td>{{ str_pad($row['hour'], 2, '0', STR_PAD_LEFT) }}:00</td><td class="text-end">{{ $row['total'] }}</td></tr>
            @empty
                <tr><td colspan="2" class="muted">No session data for this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Bowling Lane Utilization</h2>
    <table class="data">
        <thead><tr><th>Lane</th><th class="text-end">Sessions</th></tr></thead>
        <tbody>
            @foreach ($summary['laneUtilization'] as $lane)
                <tr><td>{{ $lane->name }}</td><td class="text-end">{{ $lane->completed_count }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Billiard Table Utilization</h2>
    <table class="data">
        <thead><tr><th>Table</th><th class="text-end">Sessions</th></tr></thead>
        <tbody>
            @foreach ($summary['tableUtilization'] as $table)
                <tr><td>{{ $table->name }}</td><td class="text-end">{{ $table->completed_count }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>{{ ($recipientLabel ?? null) ? $recipientLabel."'s Transactions" : 'Transactions' }}</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Date</th><th>Asset</th>
                @unless ($recipientLabel ?? null)<th>Cashier</th>@endunless
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($summary['transactions']->sortByDesc('created_at') as $txn)
                <tr>
                    <td>{{ $txn->created_at->format('M d, g:i A') }}</td>
                    <td>{{ $txn->booking?->assetLabel() ?? '—' }}</td>
                    @unless ($recipientLabel ?? null)<td>{{ $txn->cashier?->name ?? '—' }}</td>@endunless
                    <td class="text-end">&#8369;{{ number_format($txn->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ ($recipientLabel ?? null) ? 3 : 4 }}" class="muted">No transactions for this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
