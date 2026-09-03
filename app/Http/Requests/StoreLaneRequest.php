<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLaneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-lanes');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'unique:lanes,name'],
            'price_per_scoresheet' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'status' => ['required', Rule::in(['available', 'occupied', 'reserved', 'maintenance'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
