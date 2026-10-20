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
            'amounts' => ['required', 'array', 'size:8'],
            'amounts.*' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['amounts.*' => 'amount'];
    }

    /**
     * A band cannot get two sets of rates for the same date, and its steps must not go down as the step number rises.
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
                $validator->errors()->add('effective_date', 'This pay band already has rates from that date. Choose a later date.');
            }

            $amounts = array_map('floatval', array_values($this->input('amounts')));
            $sorted = $amounts;
            sort($sorted);

            if ($amounts !== $sorted) {
                $validator->errors()->add('amounts', 'Each step must pay the same as or more than the step before it.');
            }
        }];
    }
}
