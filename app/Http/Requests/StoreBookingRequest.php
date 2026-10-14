<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    // Explicit, not implicit back() — the previous-URL guess can land a
    // validation error on an unrelated page if the session's tracked
    // "previous" URL wasn't the booking wizard for whatever reason,
    // leaving the popup with no form in sight to fix.
    protected $redirectRoute = 'customer.bookings.create';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'asset_type' => ['required', Rule::in(['lane', 'table'])],
            'lane_id' => ['required_if:asset_type,lane', 'nullable', 'integer', 'exists:lanes,id'],
            'table_id' => ['required_if:asset_type,table', 'nullable', 'integer', 'exists:billiard_tables,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i', 'after_or_equal:14:00', 'before_or_equal:22:00'],
            'scoresheets' => ['required_if:asset_type,lane', 'nullable', 'integer', 'min:1', 'max:20'],
            'duration_mode' => ['required_if:asset_type,table', 'nullable', Rule::in(['fixed', 'open'])],
            // Must also gate on asset_type: the "Fixed Hours" table radio
            // stays checked by default even while the Lane tab is active, so
            // checking duration_mode alone wrongly demands "hours" on a lane
            // booking, where it's irrelevant (1 scoresheet = 1 hour, fixed).
            'hours' => [
                Rule::requiredIf(fn () => $this->input('asset_type') === 'table' && $this->input('duration_mode') === 'fixed'),
                'nullable', 'integer', 'min:1', 'max:8',
            ],
            // Every online booking pays an upfront reservation fee — a
            // percentage of the price for a lane/Fixed Hours table, or a flat
            // amount for an Open Time table (whose price isn't known yet).
            // GCash is the only way to pay it, since it's collected before
            // the customer ever sets foot in the shop.
            'reservation_fee_method' => ['required', Rule::in(['gcash'])],
            // Not required when the customer is about to pay instantly
            // through PayMongo instead of typing in a reference number for
            // a manual payment they already sent — PayMongoController
            // fills this in itself once the webhook confirms the charge.
            'reservation_fee_reference' => [
                Rule::requiredIf(fn () => ! $this->boolean('pay_via_paymongo')),
                'nullable', 'string', 'max:100',
            ],
            // Just a stated preference for the remaining balance, shown to
            // the cashier — unlike the fee, it doesn't need a reference
            // number now and applies to every booking, Open Time included.
            'balance_payment_method' => ['required', Rule::in(['gcash', 'walkin'])],
            // Optional even when GCash is picked — this is only a stated
            // preference, so a customer who hasn't paid yet just leaves it
            // blank and settles at the counter instead.
            'balance_payment_reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'lane_id.required_if' => 'Please choose which lane you would like to book.',
            'table_id.required_if' => 'Please choose which table you would like to book.',
            'hours.required' => 'Please enter how many hours you would like to play.',
            'start_time.after_or_equal' => 'We open at 2:00 PM. Please choose a time from 2:00 PM to 10:00 PM.',
            'start_time.before_or_equal' => 'We close at 10:00 PM. Please choose a time from 2:00 PM to 10:00 PM.',
            'reservation_fee_method.required' => 'Please choose how you will pay the 10% reservation fee.',
            'reservation_fee_reference.required_if' => 'Please enter your GCash reference number.',
            'balance_payment_method.required' => 'Please choose how you plan to pay the remaining balance.',
        ];
    }
}
