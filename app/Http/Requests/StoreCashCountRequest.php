<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'count_1' => ['required', 'integer', 'min:0', 'max:9999'],
            'count_5' => ['required', 'integer', 'min:0', 'max:9999'],
            'count_10' => ['required', 'integer', 'min:0', 'max:9999'],
            'count_20' => ['required', 'integer', 'min:0', 'max:9999'],
            'count_100' => ['required', 'integer', 'min:0', 'max:9999'],
            'count_200' => ['required', 'integer', 'min:0', 'max:9999'],
            'count_500' => ['required', 'integer', 'min:0', 'max:9999'],
            'count_1000' => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
