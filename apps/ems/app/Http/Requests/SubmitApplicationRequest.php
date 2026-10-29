<?php

namespace App\Http\Requests;

use App\Services\DocumentStore;
use Illuminate\Foundation\Http\FormRequest;

class SubmitApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isApplicant() ?? false;
    }

    /**
     * The resume is the education and work history typed in plus an uploaded file; the portfolio is an optional file or link.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $file = ['file', 'mimes:'.implode(',', DocumentStore::ALLOWED_EXTENSIONS), 'max:'.DocumentStore::MAX_KILOBYTES];

        return [
            'job_title_id' => ['required', 'integer', 'exists:job_titles,id'],
            'education' => ['required', 'array'],
            'education.*.level' => ['nullable', 'string', 'max:60'],
            'education.*.school' => ['nullable', 'string', 'max:150'],
            'education.*.course' => ['nullable', 'string', 'max:150'],
            'education.*.year_graduated' => ['nullable', 'string', 'max:20'],
            'work_history' => ['nullable', 'array'],
            'work_history.*.employer' => ['nullable', 'string', 'max:150'],
            'work_history.*.position' => ['nullable', 'string', 'max:150'],
            'work_history.*.from' => ['nullable', 'string', 'max:20'],
            'work_history.*.to' => ['nullable', 'string', 'max:20'],
            'resume' => ['required', ...$file],
            'portfolio' => ['nullable', ...$file],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'cover_note' => ['nullable', 'string', 'max:1500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resume.required' => 'Attach your resume (PDF, Word, or an image up to 5 MB).',
            'resume.max' => 'The resume must be 5 MB or smaller.',
            'portfolio.max' => 'The portfolio must be 5 MB or smaller.',
            'education.required' => 'Add at least one education entry.',
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    public function educationRows(): array
    {
        return $this->filledRows('education');
    }

    /**
     * @return list<array<string, string>>
     */
    public function workRows(): array
    {
        return $this->filledRows('work_history');
    }

    /**
     * A resume needs at least one education entry with a school.
     */
    public function after(): array
    {
        return [function ($validator): void {
            if (! collect($this->educationRows())->contains(fn (array $row): bool => filled($row['school'] ?? null))) {
                $validator->errors()->add('education', 'Add at least one education entry with the school.');
            }
        }];
    }

    /**
     * @return list<array<string, string>>
     */
    private function filledRows(string $key): array
    {
        return collect($this->input($key, []))
            ->map(fn ($row): array => collect((array) $row)->map(fn ($value): string => trim((string) $value))->all())
            ->filter(fn (array $row): bool => collect($row)->filter()->isNotEmpty())
            ->values()
            ->all();
    }
}
