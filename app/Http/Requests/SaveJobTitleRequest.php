<?php

namespace App\Http\Requests;

use App\Enums\JobTitleCategory;
use App\Models\College;
use App\Models\JobTitle;
use App\Models\Office;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveJobTitleRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(JobTitleCategory::class)],
            'unit' => ['required', 'regex:/^(college|office):\d+$/'],
            'salary_grade' => ['required', 'integer', Rule::exists('rate_tiers', 'salary_grade')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'unit.regex' => 'Choose the college or office this job title belongs to.',
            'salary_grade.exists' => 'Choose a pay band from the list.',
        ];
    }

    /**
     * The college or office is chosen in one field ("college:3" or "office:2"). It must exist, and the title must not
     * already be used in the same college or office.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('unit') || $validator->errors()->has('title')) {
                return;
            }

            ['college_id' => $collegeId, 'office_id' => $officeId] = $this->unitIds();
            $exists = $collegeId ? College::query()->whereKey($collegeId)->exists() : Office::query()->whereKey($officeId)->exists();

            if (! $exists) {
                $validator->errors()->add('unit', 'That college or office does not exist.');

                return;
            }

            $taken = JobTitle::query()
                ->where('title', $this->string('title')->trim()->toString())
                ->where('college_id', $collegeId)
                ->where('office_id', $officeId)
                ->when($this->route('jobTitle'), fn ($query, $jobTitle) => $query->whereKeyNot($jobTitle->getKey()))
                ->exists();

            if ($taken) {
                $validator->errors()->add('title', 'That college or office already has a job title with this name.');
            }
        }];
    }

    /**
     * @return array{college_id: ?int, office_id: ?int}
     */
    public function unitIds(): array
    {
        [$type, $id] = explode(':', (string) $this->input('unit'));

        return [
            'college_id' => $type === 'college' ? (int) $id : null,
            'office_id' => $type === 'office' ? (int) $id : null,
        ];
    }
}
