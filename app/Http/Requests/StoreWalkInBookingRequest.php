<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWalkInBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-bookings');
    }

    public function rules(): array
    {
        return [
            'asset_type' => ['required', Rule::in(['lane', 'table'])],
            'asset_id' => ['required', 'integer'],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'scoresheets' => ['required_if:asset_type,lane', 'nullable', 'integer', 'min:1', 'max:20'],
            'hours' => ['required_if:asset_type,table', 'nullable', 'integer', 'min:1', 'max:8'],
        ];
    }
}
