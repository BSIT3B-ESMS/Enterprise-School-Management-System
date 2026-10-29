{{--
    Issue an offer, or edit one that is still waiting for an answer (HR Admin). The summary of the position and the terms are built from
    the position and these choices; the standard terms are used unless HR writes its own. A full-time offer has no end of contract. A
    part-time offer ends with a semester or the academic year (PLP has two semesters): the part-time fields show only for Part-time.
--}}
@php
    $editing = isset($editOffer);
    $contractTerms = \App\Enums\ContractTerm::cases();
    $found = $editing && $editOffer->end_date ? app(\App\Services\AcademicCalendar::class)->termFor($editOffer->end_date) : null;
    $typeValue = old('employment_type', $editing ? $editOffer->employment_type->value : $defaultType->value);
    $hours = old('weekly_hours', $editing && $editOffer->employment_type === \App\Enums\EmploymentType::PartTime ? $editOffer->weekly_hours : 20);
    $startValue = $editing ? $editOffer->start_date->toDateString() : $defaultStart;
    $termValue = $found['term']->value ?? $application->jobTitle->contract_term?->value;
    $yearValue = $found['year'] ?? $application->jobTitle->academic_year;
    $chosenBenefits = old('benefit_ids', $editing ? ($editOffer->benefit_ids ?? []) : []);
@endphp

<form method="post" action="{{ $editing ? route('recruitment.offers.update', [$application, $editOffer]) : route('recruitment.offers.store', $application) }}" class="row g-3" novalidate>
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="col-md-6" data-employment-type>
        <x-select-field name="employment_type" label="Employment type" :options="['full_time' => 'Full-time', 'part_time' => 'Part-time']" :value="$typeValue" required />
    </div>
    <div class="col-md-6"><x-field name="start_date" type="date" label="Start of contract" :value="$startValue" :min="now()->toDateString()" required /></div>

    <div class="col-12" data-part-time-fields>
        <div class="row g-3">
            <div class="col-12"><p class="mb-0 small text-body-secondary">
                Part-time: the contract ends on the last day of the term you choose.
                @if ($termLabel) This post was opened for <strong>{{ $termLabel }}</strong>. @endif
            </p></div>
            <div class="col-md-4"><x-field name="weekly_hours" type="number" min="1" max="39" label="Weekly hours" :value="$hours" /></div>
            <div class="col-md-4">
                <x-select-field name="contract_term" label="Contract ends with" :options="collect($contractTerms)->mapWithKeys(fn ($term) => [$term->value => $term->label()])->all()" :value="$termValue" placeholder="Choose a term" />
            </div>
            <div class="col-md-4">
                <x-select-field name="academic_year" label="Academic year" :options="$academicYears" :value="$yearValue" placeholder="Choose a year" />
            </div>
        </div>
    </div>

    <div class="col-12">
        <span class="form-label d-block">Benefits in this offer</span>
        <div class="row row-cols-1 row-cols-md-2 g-1">
            @foreach ($benefits as $benefit)
                <div class="col">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="benefit_ids[]" value="{{ $benefit->id }}" id="benefit-{{ $benefit->id }}" @checked(in_array($benefit->id, $chosenBenefits))>
                        <label class="form-check-label" for="benefit-{{ $benefit->id }}">{{ $benefit->name }}@if ($benefit->full_time_only) <small class="text-body-secondary">(full-time only)</small> @endif</label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="col-12">
        <label for="offer-terms" class="form-label">Terms and conditions <small class="text-body-secondary">({{ $editing ? 'edit them, or clear the box to use the standard terms' : 'leave blank for the standard terms' }})</small></label>
        <textarea class="form-control" id="offer-terms" name="terms" rows="{{ $editing ? 8 : 4 }}" maxlength="6000">{{ old('terms', $editing ? $editOffer->terms : '') }}</textarea>
    </div>

    <div class="col-12 d-flex justify-content-end"><button type="submit" class="btn btn-primary">{{ $editing ? 'Save Offer' : 'Send Offer' }}</button></div>
</form>
