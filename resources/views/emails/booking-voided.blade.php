@component('mail::message')
# Your Reservation Was Cancelled

Hi {{ $customerName }},

Your reservation at **{{ $companyName }}** has been cancelled.

@component('mail::table')
| | |
| :----- | :----- |
| Asset | {{ $assetLabel }} |
| Date | {{ $bookingDate }} |
| Scheduled Time | {{ $scheduledTime }} |
| Reason | {{ $reason }} |
@endcomponent

The lane/table has been released to other customers. Feel free to book another slot any time.

@component('mail::button', ['url' => route('customer.bookings.create')])
Book Another Slot
@endcomponent

Sorry we missed you,<br>
{{ $companyName }}
@endcomponent
