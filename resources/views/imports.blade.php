

@extends('layouts.app')

@section('title','System imports')

@section('content')

<h1 class="h2 mb-4">System imports</h1><div class="card border-0 shadow-sm p-4 mb-4"><h2 class="h5">Import approved JSON export</h2><p class="text-secondary">This development importer uses the proposed version 1.0 contract. Confirm the actual export with the Admission and EMS teams before using institutional records. Up to 1,000 records and 2 MB per file.</p><form method="post" action="/imports" enctype="multipart/form-data">@csrf<label class="form-label" for="source">Source</label><select id="source" name="source" class="form-select mb-3"><option value="admission">Admission</option><option value="ems">Employee Management System</option></select><label class="form-label" for="curriculum_id">Curriculum for new Admission students</label><select name="curriculum_id" id="curriculum_id" class="form-select mb-3"><option value="">Not applicable for EMS</option>

@foreach($curriculums as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>

@endforeach</select><label class="form-label" for="file">JSON file</label><input id="file" name="file" type="file" accept=".json,application/json" class="form-control mb-3" required><button class="btn btn-primary">Validate and import</button></form></div>
<div class="card border-0 shadow-sm p-4"><h2 class="h5">Import history</h2><div class="table-responsive"><table class="table table-striped"><thead><tr><th>Date</th><th>Source</th><th>Received</th><th>Created</th><th>Updated</th><th>Rejected</th><th>Validation details</th></tr></thead><tbody>

@forelse($logs as $log)<tr><td>{{ $log->created_at }}</td><td>{{ $log->source }}</td><td>{{ $log->received }}</td><td>{{ $log->created }}</td><td>{{ $log->updated }}</td><td>{{ $log->rejected }}</td><td>

@foreach(json_decode($log->errors)??[] as $error)<div>Row {{ $error->row }}: {{ $error->error }}</div>

@endforeach</td></tr>

@empty<tr><td colspan="7">No imports yet.</td></tr>

@endforelse</tbody></table></div>{{ $logs->links('pagination::bootstrap-5') }}</div>

@endsection