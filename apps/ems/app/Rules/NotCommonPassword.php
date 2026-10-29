<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects easy-to-guess passwords: well-known ones, ones built on obvious words, keyboard or number runs, and one
 * repeated character.
 */
class NotCommonPassword implements ValidationRule
{
    private const COMMON = [
        'password', 'passw0rd', 'p@ssw0rd', 'p@ssword', 'qwerty', 'qwertyuiop', 'asdfghjkl', 'zxcvbnm', 'letmein', 'welcome',
        'iloveyou', 'admin', 'administrator', 'abc123', 'monkey', 'dragon', 'football', 'baseball', 'sunshine', 'princess',
        'superman', 'batman', 'master', 'login', 'changeme', 'default', 'secret', 'pasig', 'plpasig', 'plp', 'ems', 'employee',
        'pamantasan', 'lungsod', 'philippines', 'pilipinas', 'mahalkita', 'kumusta',
    ];

    private const RUNS = ['0123456789', '9876543210', 'abcdefghijklmnopqrstuvwxyz', 'qwertyuiop', 'asdfghjkl', 'zxcvbnm'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = mb_strtolower((string) $value);
        $letters = preg_replace('/[^a-z]/', '', strtr($password, ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a', '$' => 's']));

        if ($this->isTooEasy($password, (string) $letters)) {
            $fail('This password is too easy to guess. Avoid common words, names of the school, and simple patterns.');
        }
    }

    private function isTooEasy(string $password, string $letters): bool
    {
        if (preg_match('/^(.)\1+$/u', $password) === 1) {
            return true;
        }

        foreach (self::COMMON as $word) {
            if ($password === $word || $letters === $word || str_contains($letters, 'password') || str_contains($password, 'qwerty')) {
                return true;
            }
        }

        foreach (self::RUNS as $run) {
            if (strlen($password) >= 6 && (str_contains($run, $password) || str_contains($run, strrev($password)))) {
                return true;
            }
        }

        return false;
    }
}
