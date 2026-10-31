@extends('layouts.app')
@section('title','Curriculum overview')
@section('content')
<a href="/academic/curriculums">All curriculum versions</a><h1 class="h2 mt-3">{{ $curriculum->name }}</h1><p>{{ $curriculum->program }} · {{ $curriculum->academic_year??'Academic year not recorded' }} · {{ $curriculum->active?'Active':'Inactive' }}</p><p>Total active curriculum units: <strong>{{ $requirements->where('active',true)->sum('units') }}</strong></p>
@foreach($requirements->groupBy(fn($r)=>'Year '.$r->year_level.' · Semester '.$r->semester) as $label=>$group)
<div class="card border-0 shadow-sm p-4 mb-3"><h2 class="h5">{{ $label }} · {{ $group->where('active',true)->sum('units') }} units</h2><div class="table-responsive"><table class="table table-striped"><thead><tr><th>Subject</th><th>Units</th><th>Requirement</th><th>Status</th></tr></thead><tbody>
@foreach($group as $row)
<tr><td>{{ $row->code }} · {{ $row->source_title??$row->name }}</td><td>{{ $row->units }}</td><td>{{ $row->elective?'Elective':'Required' }}</td><td>{{ $row->active?'Active':'Inactive / historical' }}</td></tr>
@endforeach
</tbody></table></div></div>
@endforeach
@endsection

