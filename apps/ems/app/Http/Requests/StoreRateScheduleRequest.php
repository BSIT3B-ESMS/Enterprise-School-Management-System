<?php

namespace App\Http\Requests;

use App\Models\RateTier;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreRateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-reference-data');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'salary_grade' => ['required', 'integer', Rule::exists('rate_tiers', 'salary_grade')],
            'effective_date' => ['required', 'date'],
            'monthly_amount' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ];
    }

    /**
     * A salary grade cannot get two rates for the same date.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $exists = RateTier::query()
                ->where('salary_grade', $this->integer('salary_grade'))
                ->whereDate('effective_date', $this->date('effective_date')->toDateString())
                ->exists();

            if ($exists) {
                $validator->errors()->add('effective_date', 'This salary grade already has a rate from that date. Choose a later date.');
            }
        }];
    }
}
