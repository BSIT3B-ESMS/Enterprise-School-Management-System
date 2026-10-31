@extends('layouts.app')
@section('title','Colleges and programs')
@section('content')
<div class="page-heading"><div><h1>Colleges and programs offered</h1><p class="text-secondary">Organize academic programs and subject scopes.</p></div><a class="btn btn-primary" href="/academic/colleges">Add or rename colleges</a></div>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>College</th><th>Programs offered</th><th>Actions</th></tr></thead><tbody>@forelse($colleges as $college)<tr><td><strong>{{ $college->name }}</strong><br>{{ $college->abbreviation }} · {{ $college->active?'Active':'Inactive' }}</td><td>@forelse($programs[$college->id]??[] as $program)<div>{{ $program->code }} · {{ $program->name }} @if(!$program->active)<span class="badge text-bg-secondary">Inactive</span>@endif</div>@empty No programs yet.@endforelse</td><td><a href="/academic/colleges?edit={{ $college->id }}">Rename college</a><br><a href="/academic/programs">Add or manage programs</a><br><a href="/students?college_id={{ $college->id }}">Students</a></td></tr>@empty<tr><td colspan="3">Add a college to group its programs.</td></tr>@endforelse</tbody></table></div>
@if(($programs['']??collect())->isNotEmpty())<h2 class="h5">Programs not assigned to a college</h2>@foreach($programs[''] as $program)<p>{{ $program->code }} · <a href="/academic/programs?edit={{ $program->id }}">Assign college</a></p>@endforeach
@endif
@endsection
