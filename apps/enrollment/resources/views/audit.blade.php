

@extends('layouts.app')

@section('title','Audit history')

@section('content')

<h1 class="h2 mb-4">Audit history</h1><div class="card border-0 shadow-sm p-4"><div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Date</th><th>Account</th><th>Action</th><th>Entity</th><th>Details</th></tr></thead><tbody>

@forelse($logs as $log)
<tr><td>{{ $log->created_at }}</td><td>{{ $log->name??'System' }}</td><td>{{ $log->action }}</td><td>{{ $log->entity }} #{{ $log->entity_id }}</td><td><details><summary>View</summary><pre class="small">{{ $log->details }}</pre></details></td></tr>

@empty
<tr><td colspan="5">No audit entries yet.</td></tr>

@endforelse
</tbody></table></div>{{ $logs->links('pagination::bootstrap-5') }}</div>

@endsection