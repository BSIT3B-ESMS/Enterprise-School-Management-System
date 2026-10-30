

@extends('layouts.app')

@section('title','Professor records')

@section('content')

<h1 class="h2 mb-4">Professor records</h1><p class="text-secondary">Identity and employment information comes from EMS. Use Academic management to maintain teaching authorizations and availability.</p><div class="card border-0 shadow-sm p-4"><div class="table-responsive"><table class="table table-striped table-hover"><thead><tr><th>Employee ID</th><th>Name</th><th>Department</th><th>Employment</th><th>Status</th></tr></thead><tbody>

@forelse($professors as $p)
<tr><td>{{ $p->employee_id }}</td><td>{{ $p->name }}</td><td>{{ $p->department }}</td><td>{{ str_replace('_',' ',$p->employment_type) }}</td><td>{{ $p->active?'Active':'Inactive' }}</td></tr>

@empty
<tr><td colspan="5">No EMS professor records imported yet.</td></tr>

@endforelse
</tbody></table></div>{{ $professors->links('pagination::bootstrap-5') }}</div>

@endsection