<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBilliardTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-tables');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('billiard_tables', 'name')->ignore($this->route('billiard_table'))],
            'status' => ['required', Rule::in(['available', 'occupied', 'reserved', 'maintenance'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
