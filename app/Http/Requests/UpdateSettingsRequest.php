<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'report_recipients' => ['nullable', 'string', 'max:500'],
            'gcash_number' => ['required', 'string', 'max:50'],
            'gcash_account_name' => ['required', 'string', 'max:255'],
            'gcash_qr' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $raw = $this->input('report_recipients');

            if (! $raw) {
                return;
            }

            foreach (explode(',', $raw) as $email) {
                $email = trim($email);

                if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $validator->errors()->add('report_recipients', "\"{$email}\" is not a valid email address.");

                    return;
                }
            }
        });
    }
}
