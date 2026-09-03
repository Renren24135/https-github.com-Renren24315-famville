<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRateTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-rates');
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'rate_per_hour' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
