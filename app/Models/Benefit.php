<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A benefit an employee can have (for example GSIS membership). Benefits are never shown on a job posting: HR picks them in
 * the offer, and they are given to the employee when hired. An employee sees their own on the Benefits page.
 */
#[Fillable(['name', 'description', 'full_time_only', 'is_active'])]
class Benefit extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['full_time_only' => 'boolean', 'is_active' => 'boolean'];
    }

    /**
     * @return BelongsToMany<Employee, $this>
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_benefits')->withPivot(['granted_on', 'details'])->withTimestamps();
    }
}
