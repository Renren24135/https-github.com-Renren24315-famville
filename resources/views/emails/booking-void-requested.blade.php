@component('mail::message')
# Late Customer — Void Approval Needed

A cashier has flagged the following reservation as a **no-show / late arrival** at **{{ $companyName }}**. It needs your approval to be voided and released.

@component('mail::table')
| | |
| :----- | :----- |
| Customer | {{ $booking->customer->name ?? $booking->customer_name }} |
| Asset | {{ $booking->assetLabel() }} |
| Date | {{ $booking->booking_date->format('F d, Y') }} |
| Scheduled Time | {{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('g:i A') }} |
| Flagged By | {{ $booking->approver?->name ?? 'Cashier' }} |
| Reason | {{ $booking->cancel_reason }} |
@endcomponent

@component('mail::button', ['url' => route('staff.bookings.index', ['status' => 'void_pending'])])
Review & Approve
@endcomponent

Until approved, the lane/table stays held under this reservation.

Thanks,<br>
{{ $companyName }} Booking System
@endcomponent
