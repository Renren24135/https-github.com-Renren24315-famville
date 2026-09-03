<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-settings');
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['required', 'string', 'max:255'],
            'company_phone' => ['required', 'string', 'max:50'],
            'company_email' => ['required', 'email', 'max:255'],
            'company_facebook' => ['nullable', 'string', 'max:255'],
            'opening_hours' => ['required', 'string', 'max:255'],
        ];
    }
}
