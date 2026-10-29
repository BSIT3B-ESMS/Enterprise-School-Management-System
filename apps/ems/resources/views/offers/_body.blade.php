{{--
    An offer as the person reads it: the summary of the position, the benefits HR included, the confirmed start of contract (and the
    end of contract for a part-time post), and the terms and conditions. For a change of assignment it shows the current terms beside
    the new ones.
--}}
@php($summary = $offer->summary)

<h3 class="fs-6 mt-2">Summary of Position</h3>
<dl class="row mb-2">
    <dt class="col-sm-4">Position</dt><dd class="col-sm-8">{{ $summary['position'] }}</dd>
    <dt class="col-sm-4">College or Office</dt><dd class="col-sm-8">{{ $summary['unit'] ?: 'Not specified' }}</dd>
    <dt class="col-sm-4">Employment type</dt><dd class="col-sm-8">{{ $summary['employment_type'] }}@if ($offer->employment_type === \App\Enums\EmploymentType::PartTime), {{ $summary['weekly_hours'] }} hours a week @endif</dd>
    <dt class="col-sm-4">Monthly base pay</dt><dd class="col-sm-8">{{ $summary['monthly_pay'] ? '₱'.number_format($summary['monthly_pay'], 2).' (Salary Grade '.$summary['salary_grade'].', Step 1)' : 'Salary Grade '.$summary['salary_grade'] }}</dd>
    <dt class="col-sm-4">Start of contract</dt><dd class="col-sm-8">{{ $offer->start_date->format('F j, Y') }}</dd>
    <dt class="col-sm-4">End of contract</dt>
    <dd class="col-sm-8">
        @if ($offer->end_date)
            {{ $offer->end_date->format('F j, Y') }}@if (! empty($summary['term'])) ({{ $summary['term'] }}) @endif
        @else
            None (full-time)
        @endif
    </dd>
    @if (! empty($summary['benefits']))
        <dt class="col-sm-4">Benefits</dt><dd class="col-sm-8">{{ implode(', ', $summary['benefits']) }}</dd>
    @endif
</dl>

@if (! empty($summary['current']))
    <h3 class="fs-6">Your current terms</h3>
    <dl class="row mb-2 text-body-secondary">
        <dt class="col-sm-4">Position</dt><dd class="col-sm-8">{{ $summary['current']['position'] }}</dd>
        <dt class="col-sm-4">College or Office</dt><dd class="col-sm-8">{{ $summary['current']['unit'] ?: 'Not specified' }}</dd>
        <dt class="col-sm-4">Employment type</dt><dd class="col-sm-8">{{ $summary['current']['employment_type'] }}</dd>
        <dt class="col-sm-4">End of contract</dt><dd class="col-sm-8">{{ $summary['current']['end_date'] ? \Illuminate\Support\Carbon::parse($summary['current']['end_date'])->format('F j, Y') : 'None' }}</dd>
    </dl>
@endif

@if ($offer->reason)
    <p class="mb-2"><strong>Reason:</strong> {{ $offer->reason }}</p>
@endif

<h3 class="fs-6">Terms and Conditions</h3>
<div class="border rounded p-3 small" style="white-space: pre-line">{{ $offer->terms }}</div>
