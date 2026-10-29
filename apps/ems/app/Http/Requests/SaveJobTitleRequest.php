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

/**
 * A job title is one shared title (for example Instructor I) used in every college and office ticked on the form. The name, category,
 * and salary grade are the same everywhere, so editing them changes all of them.
 */
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
            'units' => ['required', 'array', 'min:1'],
            'units.*' => ['required', 'regex:/^(college|office):\d+$/', 'distinct'],
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
            'units.required' => 'Choose at least one college or office that uses this job title.',
            'units.min' => 'Choose at least one college or office that uses this job title.',
            'units.*.regex' => 'Choose colleges and offices from the list.',
            'salary_grade.exists' => 'Choose a salary grade from the list.',
        ];
    }

    /**
     * Every chosen college or office must exist, and none may already have a different job title with this name.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $title = $this->string('title')->trim()->toString();
            $own = $this->route('jobTitle') ? $this->groupIds($this->route('jobTitle')) : [];

            foreach ($this->unitList() as $unit) {
                $exists = $unit['college_id'] ? College::query()->whereKey($unit['college_id'])->exists() : Office::query()->whereKey($unit['office_id'])->exists();

                if (! $exists) {
                    $validator->errors()->add('units', 'One of the chosen colleges or offices does not exist.');

                    return;
                }

                $taken = JobTitle::query()->where('title', $title)->where('college_id', $unit['college_id'])->where('office_id', $unit['office_id'])->whereNotIn('id', $own)->exists();

                if ($taken) {
                    $validator->errors()->add('title', 'A college or office you chose already has a job title with this name.');

                    return;
                }
            }
        }];
    }

    /**
     * The chosen colleges and offices as ids.
     *
     * @return list<array{key: string, college_id: ?int, office_id: ?int}>
     */
    public function unitList(): array
    {
        return collect((array) $this->input('units'))->map(function (string $unit): array {
            [$type, $id] = explode(':', $unit);

            return ['key' => $unit, 'college_id' => $type === 'college' ? (int) $id : null, 'office_id' => $type === 'office' ? (int) $id : null];
        })->values()->all();
    }

    /**
     * The ids of every copy of a job title (the same name and category in each college or office).
     *
     * @return list<int>
     */
    public function groupIds(JobTitle $jobTitle): array
    {
        return JobTitle::query()->where('title', $jobTitle->title)->where('category', $jobTitle->category)->pluck('id')->all();
    }
}
