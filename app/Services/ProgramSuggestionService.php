<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Program;
use App\Models\Strand;
use Illuminate\Support\Collection;

/**
 * Suggested programs (FR-06): the open programs staff linked to the
 * applicant's SHS strand in the strand-to-program table (FR-26). No strand
 * yet, or none linked: no suggestions.
 */
class ProgramSuggestionService
{
    /** @return Collection<int, Program> */
    public function suggestFor(Applicant $applicant): Collection
    {
        return $this->forStrand($applicant->strand);
    }

    /** @return Collection<int, Program> */
    public function forStrand(?string $strand): Collection
    {
        if (! $strand) {
            return collect();
        }

        return Strand::where('name', $strand)->where('is_active', true)->first()
            ?->programs()->where('is_open', true)->orderBy('name')->get() ?? collect();
    }

    /** strand => names of the open programs suggested for it (the applicant form, FR-06). */
    public function namesByStrand(): array
    {
        return Strand::where('is_active', true)->with(['programs' => fn ($q) => $q->where('is_open', true)->orderBy('name')])->get()
            ->mapWithKeys(fn (Strand $s) => [$s->name => $s->programs->pluck('name')->all()])->all();
    }

    /** strand => suggested program codes, for the office panel and the form. */
    public function table(): array
    {
        return Strand::with(['programs' => fn ($q) => $q->orderBy('code')])->orderBy('sort_order')->orderBy('name')->get()
            ->mapWithKeys(fn (Strand $s) => [$s->name => $s->programs->pluck('code')->all()])->all();
    }
}
