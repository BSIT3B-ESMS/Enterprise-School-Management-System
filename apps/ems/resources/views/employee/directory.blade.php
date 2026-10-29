@extends('layouts.employee')

@section('title', 'Directory · '.config('app.name'))

@section('content')
    <div class="d-grid gap-4">
        <div>
            <h1 class="page-title">Directory</h1>
            <p class="text-body-secondary mt-1 mb-0">Name, college or office, job title, and official email. Nothing else is shown.</p>
        </div>

        <form method="get" class="card employees-filters" data-auto-submit>
            <label for="directory-search" class="visually-hidden">Search the directory</label>
            <div class="search-field">
                <x-icon name="search" />
                <input type="search" class="form-control" id="directory-search" name="q" value="{{ $term }}" placeholder="Search by name, job title, or email..." autocomplete="off">
            </div>
        </form>

        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0 employees-table">
                    <thead><tr><th scope="col">Name</th><th scope="col">College or Office</th><th scope="col">Job Title</th><th scope="col">Official Email</th></tr></thead>
                    <tbody>
                        @forelse ($employees as $person)
                            <tr>
                                <td class="fw-medium">{{ $person->last_name }}, {{ $person->first_name }}</td>
                                <td>{{ collect([$person->college?->code, $person->office?->code])->filter()->implode(' · ') }}</td>
                                <td>{{ $person->jobTitle->title }}</td>
                                <td>{{ $person->official_email }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="employees-empty text-center"><strong>Nobody found</strong></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="employees-footer">{{ $employees->links() }}</div>
        </div>
    </div>
@endsection
