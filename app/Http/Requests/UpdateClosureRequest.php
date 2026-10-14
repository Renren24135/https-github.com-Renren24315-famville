<?php

namespace App\Http\Requests;

use App\Models\BilliardTable;
use App\Models\Booking;
use App\Models\Lane;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateClosureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-closures');
    }

    public function rules(): array
    {
        return [
            'facility' => ['required', Rule::in(['lane', 'table'])],
            'asset_id' => [
                'nullable', 'integer',
                Rule::exists($this->input('facility') === 'lane' ? 'lanes' : 'billiard_tables', 'id'),
            ],
            // No "must be today or later" here — editing a closure (e.g.
            // fixing a typo in the reason) shouldn't start failing once its
            // own start date has passed.
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $bookableType = $this->input('facility') === 'lane' ? Lane::class : BilliardTable::class;
            $assetId = $this->input('asset_id') ?: null;

            $conflicts = Booking::query()
                ->with('bookable')
                ->where('bookable_type', $bookableType)
                ->when($assetId, fn ($q) => $q->where('bookable_id', $assetId))
                ->whereIn('status', ['pending', 'approved', 'occupied'])
                ->whereBetween('booking_date', [$this->input('start_date'), $this->input('end_date')])
                ->orderBy('booking_date')
                ->get();

            if ($conflicts->isEmpty()) {
                return;
            }

            $list = $conflicts->take(5)->map(
                fn (Booking $b) => $b->customer_name.' — '.$b->assetLabel().' ('.$b->booking_date->format('M d').')'
            )->implode('; ');

            if ($conflicts->count() > 5) {
                $list .= '; +'.($conflicts->count() - 5).' more';
            }

            $validator->errors()->add(
                'start_date',
                'That range already has existing bookings: '.$list.'. Resolve them first before closing these dates.'
            );
        });
    }
}
