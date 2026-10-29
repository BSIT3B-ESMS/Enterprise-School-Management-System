<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveOfficeRequest extends FormRequest
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
        $ignore = $this->route('office')?->getKey();

        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('offices', 'code')->ignore($ignore)],
            'name' => ['required', 'string', 'max:255', Rule::unique('offices', 'name')->ignore($ignore)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
