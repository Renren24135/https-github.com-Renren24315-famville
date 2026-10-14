<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('process-pos');
    }

    public function rules(): array
    {
        /** @var \App\Models\Booking|null $booking */
        $booking = $this->route('booking');
        $amountDue = $booking?->remainingBalance() ?? 0;

        return [
            'payment_method' => ['required', Rule::in(['cash', 'gcash'])],
            'cash_tendered' => ['required_if:payment_method,cash', 'nullable', 'numeric', 'min:'.$amountDue],
            'gcash_reference' => ['required_if:payment_method,gcash', 'nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'cash_tendered.min' => 'Cash tendered must cover the full amount due (₱:min).',
            'gcash_reference.required_if' => 'Please enter the GCash reference number.',
        ];
    }
}
