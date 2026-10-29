<?php

namespace App\Http\Requests\Concerns;

use Closure;

/**
 * Format checks for the government and tax ID numbers, shared by the employee form and the first-login setup form.
 */
trait GovernmentIdRules
{
    /**
     * Rules for the six ID numbers. `$required` lists the ones that must be filled in; the others may stay blank.
     *
     * @param  list<string>  $required
     * @return array<string, list<mixed>>
     */
    protected function governmentIdRules(array $required = []): array
    {
        $formats = [
            'tin' => [[9, 12], 'TIN must be 9 digits, or 12 with a branch code.'],
            'gsis' => [[11], 'GSIS number must be 11 digits.'],
            'sss' => [[10], 'SSS number must be 10 digits.'],
            'philhealth' => [[12], 'PhilHealth number must be 12 digits.'],
            'pagibig' => [[12], 'Pag-IBIG MID number must be 12 digits.'],
            'bir' => [[9, 10, 11, 12, 13], 'BIR number must be 9 to 13 digits.'],
        ];

        $rules = [];

        foreach ($formats as $key => [$counts, $message]) {
            $rules[$key] = [in_array($key, $required, true) ? 'required' : 'nullable', $this->digits($counts, $message)];
        }

        return $rules;
    }

    /**
     * A number made of digits, dashes, and spaces with one of the allowed digit counts.
     *
     * @param  list<int>  $counts
     */
    private function digits(array $counts, string $message): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($counts, $message): void {
            if (! preg_match('/^[\d\s-]+$/', (string) $value) || ! in_array(strlen((string) preg_replace('/\D/', '', (string) $value)), $counts, true)) {
                $fail($message);
            }
        };
    }
}
