<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-bookings');
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['approved', 'occupied', 'completed', 'void_pending', 'void_approved', 'void_denied', 'confirm_reservation_fee', 'confirm_balance_payment', 'confirm_gcash_payment'])],
            // The cashier must explain why they're flagging a void, and the
            // admin must explain why they're finalizing it — that admin
            // reason is what actually gets emailed to the customer.
            'cancel_reason' => [
                Rule::requiredIf(fn () => $this->input('status') === 'void_pending'),
                'nullable', 'string', 'max:255',
            ],
            'void_admin_reason' => [
                Rule::requiredIf(fn () => $this->input('status') === 'void_approved'),
                'nullable', 'string', 'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cancel_reason.required' => 'Please explain why you\'re flagging this booking for void.',
            'void_admin_reason.required' => 'Please explain why you\'re voiding this booking — the customer will see this.',
        ];
    }
}
