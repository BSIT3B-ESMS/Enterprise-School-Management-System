<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveCollegeRequest extends FormRequest
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
        $ignore = $this->route('college')?->getKey();

        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('colleges', 'code')->ignore($ignore)],
            'name' => ['required', 'string', 'max:255', Rule::unique('colleges', 'name')->ignore($ignore)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
