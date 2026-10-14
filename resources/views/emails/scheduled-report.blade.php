@component('mail::message')
@if ($recipientLabel)
# {{ $recipientLabel }}'s {{ $rangeLabel }} Sales Report

Sales summary for **{{ $recipientLabel }}** at **{{ $companyName }}** — {{ $from->format('M d, Y') }} to {{ $to->format('M d, Y') }}. Figures below reflect only the transactions {{ $recipientLabel }} personally processed.
@else
# {{ $rangeLabel }} Business Report

**{{ $companyName }}** — {{ $from->format('M d, Y') }} to {{ $to->format('M d, Y') }}
@endif

@component('mail::table')
| Metric | Value |
| :----- | ----: |
| {{ $recipientLabel ? $recipientLabel.'\'s Revenue' : 'Total Revenue' }} | ₱{{ number_format($summary['totalRevenue'], 2) }} |
| Transactions | {{ $summary['transactionCount'] }} |
@unless ($recipientLabel)
| Assets Occupied (now) | {{ $summary['occupiedCount'] }} / {{ $summary['totalAssets'] }} |
| Pending Bookings (now) | {{ $summary['pendingCount'] }} |
@endunless
@endcomponent

The full breakdown — revenue by day, peak hours, lane/table utilization, and every transaction{{ $recipientLabel ? ' '.$recipientLabel.' processed' : '' }} — is attached as a PDF.

Thanks,<br>
{{ $companyName }} Booking System
@endcomponent
