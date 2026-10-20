<?php

namespace App\Http\Resources\V1;

use App\Models\JobTitle;
use App\Models\RateTier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobTitle
 */
class JobTitleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $schedule = $request->attributes->get('rate_schedule') ?? RateTier::scheduleOn();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category->value,
            'college' => $this->college?->code,
            'office' => $this->office?->code,
            'salary_grade' => $this->salary_grade,
            'base_rate' => isset($schedule[$this->salary_grade]) ? (float) $schedule[$this->salary_grade] : null,
            'is_active' => $this->is_active,
        ];
    }
}
