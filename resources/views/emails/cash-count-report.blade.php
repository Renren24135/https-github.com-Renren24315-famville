@component('mail::message')
# End-of-Shift Cash Count

**{{ $cashCount->user->name }}** at **{{ $companyName }}** counted out at {{ $cashCount->counted_at->format('M d, Y g:i A') }}.

@component('mail::table')
| Denomination | Quantity | Subtotal |
| :----------- | -------: | -------: |
| &#8369;1,000 | {{ $cashCount->count_1000 }} | &#8369;{{ number_format($cashCount->count_1000 * 1000, 2) }} |
| &#8369;500 | {{ $cashCount->count_500 }} | &#8369;{{ number_format($cashCount->count_500 * 500, 2) }} |
| &#8369;200 | {{ $cashCount->count_200 }} | &#8369;{{ number_format($cashCount->count_200 * 200, 2) }} |
| &#8369;100 | {{ $cashCount->count_100 }} | &#8369;{{ number_format($cashCount->count_100 * 100, 2) }} |
| &#8369;20 | {{ $cashCount->count_20 }} | &#8369;{{ number_format($cashCount->count_20 * 20, 2) }} |
| &#8369;10 | {{ $cashCount->count_10 }} | &#8369;{{ number_format($cashCount->count_10 * 10, 2) }} |
| &#8369;5 | {{ $cashCount->count_5 }} | &#8369;{{ number_format($cashCount->count_5 * 5, 2) }} |
| &#8369;1 | {{ $cashCount->count_1 }} | &#8369;{{ number_format($cashCount->count_1 * 1, 2) }} |
@endcomponent

@component('mail::table')
| | |
| :----- | ----: |
| **Total Cash Counted** | **&#8369;{{ number_format($cashCount->total_counted, 2) }}** |
| Expected (today's sales) | &#8369;{{ number_format($cashCount->expected_amount, 2) }} |
| Difference | &#8369;{{ number_format($cashCount->difference, 2) }} {{ $cashCount->difference == 0 ? '(matched)' : ($cashCount->difference > 0 ? '(over)' : '(short)') }} |
@endcomponent

Thanks,<br>
{{ $companyName }} Booking System
@endcomponent
