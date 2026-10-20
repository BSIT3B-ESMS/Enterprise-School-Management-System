@extends('layouts.admin')

@section('title', 'Pay Rates · '.config('app.name'))

{{--
    Reference base pay by pay band and step, as in force today. Adding rates never changes old ones: it adds a new set
    of eight amounts from an effective date, and the history stays below. Nothing here computes pay.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="rates">
        <div class="d-flex align-items-center gap-3">
            <a class="page-back" href="{{ route('job-titles.index') }}" aria-label="Back to job titles"><x-icon name="back" /></a>
            <div>
                <h1 class="page-title">Pay Rates</h1>
                <p class="text-body-secondary mt-1 mb-0">Monthly base pay in force today, by pay band and step</p>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 employees-table">
                    <thead>
                        <tr>
                            <th scope="col">Pay band</th>
                            <th scope="col">Job titles</th>
                            @foreach (range(1, 8) as $step)
                                <th scope="col" class="text-end">Step {{ $step }}</th>
                            @endforeach
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($schedule as $band => $steps)
                            <tr @class(['table-active' => $band === $grade])>
                                <td class="tabular-nums fw-medium">{{ $band }}</td>
                                <td>{{ ($titlesByGrade[$band] ?? collect())->pluck('title')->unique()->take(3)->implode(', ') ?: '—' }}</td>
                                @foreach (range(1, 8) as $step)
                                    <td class="text-end tabular-nums">{{ isset($steps[$step]) ? number_format((float) $steps[$step]) : '—' }}</td>
                                @endforeach
                                <td class="text-end">
                                    @can('manage-reference-data')
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('rates.index', ['grade' => $band]) }}#new-rates">Change</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($grade)
            <section class="card employee-form-card" id="new-rates" aria-labelledby="new-rates-title">
                <h2 class="fs-5" id="new-rates-title">Pay band {{ $grade }}: new rates</h2>
                <p class="text-body-secondary">
                    Enter the monthly amounts from the date they take effect. The old rates are kept, so earlier dates still use them.
                </p>

                @can('manage-reference-data')
                    <form method="post" action="{{ route('rates.store') }}" class="row g-3" novalidate>
                        @csrf
                        <input type="hidden" name="salary_grade" value="{{ $grade }}">

                        <div class="col-md-4"><x-field name="effective_date" type="date" label="Effective date" required /></div>
                        <div class="col-12"></div>

                        @foreach (range(1, 8) as $step)
                            <div class="col-6 col-md-3">
                                <label for="amount-{{ $step }}" class="form-label">Step {{ $step }}</label>
                                <input type="number" step="0.01" min="0" class="form-control tabular-nums{{ $errors->has('amounts.'.($step - 1)) ? ' is-invalid' : '' }}"
                                    id="amount-{{ $step }}" name="amounts[]" value="{{ old('amounts.'.($step - 1), $schedule[$grade][$step] ?? '') }}" required>
                                @error('amounts.'.($step - 1))
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach

                        @error('amounts')
                            <div class="col-12 text-danger small">{{ $message }}</div>
                        @enderror

                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Save New Rates</button>
                        </div>
                    </form>
                @endcan

                <h3 class="fs-6 mt-4">History</h3>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Effective from</th>
                                @foreach (range(1, 8) as $step)
                                    <th scope="col" class="text-end">Step {{ $step }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $date => $tiers)
                                <tr>
                                    <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($date)->format('M j, Y') }}</td>
                                    @foreach (range(1, 8) as $step)
                                        <td class="text-end tabular-nums">{{ ($tier = $tiers->firstWhere('step', $step)) ? number_format((float) $tier->monthly_amount) : '—' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
@endsection
