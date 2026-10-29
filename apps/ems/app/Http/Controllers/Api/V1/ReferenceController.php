<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CollegeResource;
use App\Http\Resources\V1\JobTitleResource;
use App\Models\College;
use App\Models\JobTitle;
use App\Models\RateTier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class ReferenceController extends Controller
{
    public function colleges(): AnonymousResourceCollection
    {
        return CollegeResource::collection(College::query()->orderBy('code')->get());
    }

    public function jobTitles(Request $request): AnonymousResourceCollection
    {
        $request->attributes->set('rate_schedule', RateTier::scheduleOn());

        return JobTitleResource::collection(JobTitle::query()->with(['college', 'office'])->orderBy('title')->get());
    }

    /**
     * The monthly pay of every salary grade (Step 1) in force today, or on the date given.
     */
    public function rateTiers(Request $request): JsonResponse
    {
        $data = $request->validate(['on' => ['nullable', 'date']]);
        $on = isset($data['on']) ? Carbon::parse($data['on']) : now();

        return response()->json(['data' => collect(RateTier::scheduleOn($on))
            ->map(fn (string $amount, int $grade): array => ['salary_grade' => $grade, 'monthly_amount' => (float) $amount, 'as_of' => $on->toDateString()])
            ->values()]);
    }
}
