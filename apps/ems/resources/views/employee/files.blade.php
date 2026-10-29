@extends('layouts.employee')

@section('title', 'My Files · '.config('app.name'))

@section('content')
    <div class="d-grid gap-4">
        <div>
            <h1 class="page-title">My Files</h1>
            <p class="text-body-secondary mt-1 mb-0">Documents in your 201 file. HR can see them too.</p>
        </div>

        <section class="card employee-form-card">
            <h2 class="fs-5">Add a document</h2>
            <form method="post" action="{{ route('employee.files.store') }}" enctype="multipart/form-data" class="row g-3" novalidate>
                @csrf
                <div class="col-md-4"><x-select-field name="kind" label="Type" :options="$kinds" required /></div>
                <div class="col-md-5">
                    <label for="file" class="form-label is-required">File</label>
                    <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx" required>
                    @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100">Upload</button></div>
            </form>
        </section>

        <section class="card employee-form-card">
            <h2 class="fs-5">Your documents</h2>
            <ul class="list-group list-group-flush">
                @forelse ($documents as $document)
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <span><span class="fw-medium">{{ $kinds[$document->kind] ?? ucfirst($document->kind) }}</span> · {{ $document->original_name }} <small class="text-body-secondary">({{ $document->humanSize() }}, {{ $document->created_at->format('M j, Y') }})</small></span>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('documents.show', $document->uuid) }}" target="_blank" rel="noopener">Open</a>
                    </li>
                @empty
                    <li class="list-group-item px-0 text-body-secondary">No documents yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
