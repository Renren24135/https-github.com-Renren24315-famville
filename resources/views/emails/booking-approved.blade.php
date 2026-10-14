@component('mail::message')
# Your booking is approved

Hi {{ $booking->customer->name ?? $booking->customer_name }},

Your reservation at **{{ $companyName }}** has been approved:

@component('mail::table')
| | |
| :----- | :----- |
| Reference No. | {{ $booking->bookingNo() }} |
| Asset | {{ $booking->assetLabel() }} |
| Date | {{ $booking->booking_date->format('F d, Y') }} |
| Time | {{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('g:i A') }} |
| Quantity | {{ $booking->scoresheets ? $booking->scoresheets.' scoresheet(s)' : $booking->hours.' hour(s)' }} |
@if ($booking->reservation_fee > 0)
| Reservation Fee | ₱{{ number_format($booking->reservation_fee, 2) }} (already paid) |
| Balance at Counter | ₱{{ number_format($booking->remainingBalance(), 2) }} |
@else
| Amount Due | ₱{{ number_format($booking->total_amount, 2) }} (payable at the counter) |
@endif
@endcomponent

@component('mail::panel')
Please arrive **on time**. If you arrive more than **10 minutes** after your scheduled start time, your reservation will be cancelled and the lane/table released to other customers.
@endcomponent

@component('mail::button', ['url' => route('customer.bookings.show', $booking)])
View Booking
@endcomponent

See you soon,<br>
{{ $companyName }}
@endcomponent
