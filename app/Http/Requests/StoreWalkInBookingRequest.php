<?php

namespace App\Http\Requests;

use App\Models\BilliardRateTier;
use App\Models\Lane;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWalkInBookingRequest extends FormRequest
{
    // Explicit, not implicit back() — the previous-URL guess can land a
    // validation error on an unrelated page (e.g. the dashboard) if the
    // session's tracked "previous" URL wasn't the POS page for whatever
    // reason, leaving the popup with no form in sight to fix.
    protected $redirectRoute = 'staff.pos.index';

    public function authorize(): bool
    {
        return $this->user()->can('manage-bookings');
    }

    public function rules(): array
    {
        $assetType = $this->input('asset_type');
        $durationMode = $this->input('duration_mode');

        // A lane or Fixed Hours table knows its price the moment scoresheets
        // /hours are chosen, so the cashier collects payment right here in
        // the same form — an Open Time table doesn't know its price until
        // the session ends, so it's still settled later via the POS list.
        $needsPayment = $assetType === 'lane' || ($assetType === 'table' && $durationMode === 'fixed');

        $amountDue = 0;
        if ($needsPayment && $assetType === 'lane' && $this->filled('lane_id') && $this->filled('scoresheets')) {
            $lane = Lane::find($this->input('lane_id'));
            $amountDue = $lane ? (float) $lane->price_per_scoresheet * (int) $this->input('scoresheets') : 0;
        } elseif ($needsPayment && $assetType === 'table' && $this->filled('hours')) {
            $tier = BilliardRateTier::rateForTime(now()->format('H:i:s'));
            $amountDue = $tier ? (float) $tier->rate_per_hour * (int) $this->input('hours') : 0;
        }

        return [
            'asset_type' => ['required', Rule::in(['lane', 'table'])],
            'lane_id' => ['required_if:asset_type,lane', 'nullable', 'integer', 'exists:lanes,id'],
            'table_id' => ['required_if:asset_type,table', 'nullable', 'integer', 'exists:billiard_tables,id'],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'scoresheets' => ['required_if:asset_type,lane', 'nullable', 'integer', 'min:1', 'max:20'],
            'duration_mode' => ['required_if:asset_type,table', 'nullable', Rule::in(['fixed', 'open'])],
            'hours' => ['required_if:duration_mode,fixed', 'nullable', 'integer', 'min:1', 'max:8'],
            'payment_method' => [
                Rule::requiredIf($needsPayment),
                'nullable', Rule::in(['cash', 'gcash']),
            ],
            'cash_tendered' => ['required_if:payment_method,cash', 'nullable', 'numeric', 'min:'.$amountDue],
            'gcash_reference' => ['required_if:payment_method,gcash', 'nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Please choose a payment method to collect for this walk-in.',
            'cash_tendered.min' => 'Cash tendered must cover the full amount due (₱:min).',
            'gcash_reference.required_if' => 'Please enter the GCash reference number.',
        ];
    }
}
