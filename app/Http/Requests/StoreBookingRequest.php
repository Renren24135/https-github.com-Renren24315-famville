<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'asset_type' => ['required', Rule::in(['lane', 'table'])],
            'asset_id' => ['required', 'integer'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'scoresheets' => ['required_if:asset_type,lane', 'nullable', 'integer', 'min:1', 'max:20'],
            'hours' => ['required_if:asset_type,table', 'nullable', 'integer', 'min:1', 'max:8'],
        ];
    }
}
