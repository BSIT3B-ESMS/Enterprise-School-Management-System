@extends('layouts.admin')

@section('title', $heading.' · '.config('app.name'))

{{-- Edit form shared by Colleges and Offices (both have a code, a name, and an active flag). --}}
@section('content')
    <div class="d-grid gap-4">
        <div class="d-flex align-items-center gap-3">
            <a class="page-back" href="{{ $back }}" aria-label="Back"><x-icon name="back" /></a>
            <h1 class="page-title">{{ $heading }}</h1>
        </div>

        <form method="post" action="{{ $action }}" class="card employee-form-card row g-3" novalidate>
            @csrf
            @method('PUT')

            <div class="col-md-3"><x-field name="code" label="Code" :value="$unit->code" required /></div>
            <div class="col-md-9"><x-field name="name" label="Name" :value="$unit->name" required /></div>

            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="field-is_active" name="is_active" value="1"
                        @checked(old('is_active', $unit->is_active))>
                    <label class="form-check-label" for="field-is_active">Active</label>
                </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="{{ $back }}">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
@endsection
