<?php

namespace App\Http\Resources\V1;

use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin College
 */
class CollegeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return ['code' => $this->code, 'name' => $this->name, 'is_active' => $this->is_active];
    }
}
