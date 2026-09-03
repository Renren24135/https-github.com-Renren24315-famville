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
        $amountDue = (float) $this->route('booking')?->total_amount;

        return [
            'payment_method' => ['required', Rule::in(['cash'])],
            'cash_tendered' => ['required', 'numeric', 'min:'.$amountDue],
        ];
    }

    public function messages(): array
    {
        return [
            'cash_tendered.min' => 'Cash tendered must cover the full amount due (₱:min).',
        ];
    }
}
