@extends('layouts.employee')

@section('title', 'Directory · '.config('app.name'))

{{--
    Directory of the signed-in employee's own department, filled in by resources/js/portal-directory.js from the
    mock data. Only people in that department are passed to the page (with name, position, and work email), so
    there is no department filter and search covers just those colleagues.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="portal-directory">
        <div>
            <h1 class="page-title">Directory</h1>
            <p class="text-body-secondary mt-1 mb-0" data-directory-subtitle>Find your colleagues.</p>
        </div>

        <div class="card d-none" data-portal-missing>
            <div class="employees-empty">
                <strong>Employee record not found</strong>
                Your record may have been removed. Contact HR for help.
            </div>
        </div>

        <div class="card d-none" data-directory-no-department>
            <div class="employees-empty">
                <strong>No department assigned</strong>
                Your profile has no department, so there are no colleagues to show. Contact HR to have one assigned.
            </div>
        </div>

        <div class="d-grid gap-4" data-portal-content>
            <div class="card employees-filters">
                <label for="directory-search" class="visually-hidden">Search your department</label>
                <div class="search-field">
                    <x-icon name="search" />
                    <input type="search" class="form-control" id="directory-search"
                        placeholder="Search by name, position, or email..." autocomplete="off" data-directory-search>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-directory-clear>Clear Search</button>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="p-3">
                    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" data-directory-list></div>
                </div>

                <div class="employees-empty d-none" data-directory-empty>
                    <strong>No colleagues match</strong>
                    Try a different search.
                </div>

                <div class="employees-footer d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <span aria-live="polite" data-directory-summary></span>
                    <nav aria-label="Directory pages">
                        <ul class="pagination pagination-sm mb-0" data-directory-pagination></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
@endsection
