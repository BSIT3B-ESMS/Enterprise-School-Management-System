@extends('layouts.employee')

@section('title', 'My Files · '.config('app.name'))

{{--
    The signed-in employee's own 201 file documents by category, filled in by resources/js/portal-files.js from the
    mock data. Document records hold only a filename, size, and date, so there is nothing to preview or download;
    each is marked "Filename only" (a record that carried file content would be marked "File available").
--}}
@section('content')
    <div class="d-grid gap-4" data-page="portal-files">
        <div>
            <h1 class="page-title">My Files</h1>
            <p class="text-body-secondary mt-1 mb-0" data-files-summary>Your 201 file documents, by category.</p>
        </div>

        <div class="card d-none" data-portal-missing>
            <div class="employees-empty">
                <strong>Employee record not found</strong>
                Your record may have been removed. Contact HR for help.
            </div>
        </div>

        <div class="card d-none" data-files-none>
            <div class="employees-empty">
                <strong>No files on record</strong>
                Documents added to your 201 file will appear here.
            </div>
        </div>

        <div class="row row-cols-1 row-cols-xl-2 g-4" data-files-categories></div>
    </div>
@endsection
