{{--
    The open positions as cards (title, college or office, employment type, location, base pay, a short description) with a
    search, an Employment Type filter, and a College filter. The filters submit as a normal GET form. Used on the public PLP
    Careers page and inside the Applicant interface. Benefits are not shown here: HR chooses them in the offer.
--}}
@php
    $detailsRoute = $context === 'public' ? 'careers.show' : 'applicant.positions.show';
    $typeLabel = fn ($position) => \App\Enums\EmploymentType::tryFrom((string) $position->employment_type)?->label() ?? 'Not specified';
@endphp

<section class="d-grid gap-3" aria-labelledby="positions-open-title">
    <div>
        <h2 class="fs-4 mb-1 d-flex align-items-center gap-2" id="positions-open-title">
            {{ $title ?? 'Open Positions' }} <span class="ems-chip tabular-nums">{{ $positions->count() }}</span>
        </h2>
        @isset($subtitle)
            <p class="text-body-secondary mb-0">{{ $subtitle }}</p>
        @endisset
    </div>

    <form method="get" class="card employees-filters" data-auto-submit>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="positions-search" class="visually-hidden">Search positions</label>
                <div class="search-field">
                    <x-icon name="search" />
                    <input type="search" class="form-control" id="positions-search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search positions..." autocomplete="off">
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <label for="positions-type" class="visually-hidden">Employment type</label>
                <select class="form-select" id="positions-type" name="type">
                    <option value="">All Employment Types</option>
                    <option value="full_time" @selected(($filters['type'] ?? '') === 'full_time')>Full-Time</option>
                    <option value="part_time" @selected(($filters['type'] ?? '') === 'part_time')>Part-Time</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label for="positions-college" class="visually-hidden">College</label>
                <select class="form-select" id="positions-college" name="college">
                    <option value="">All Colleges</option>
                    @foreach ($colleges as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($filters['college'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    @if ($positions->isEmpty())
        <div class="card">
            <div class="employees-empty">
                <strong>{{ $stats['positions'] === 0 ? 'No open positions' : 'No positions match your search' }}</strong>
                {{ $stats['positions'] === 0 ? 'There is nothing to apply for right now. Please check back later.' : 'Try another search or filter.' }}
            </div>
        </div>
    @else
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
            @foreach ($positions as $position)
                @include('careers._card', ['position' => $position])
            @endforeach
        </div>
    @endif
</section>
