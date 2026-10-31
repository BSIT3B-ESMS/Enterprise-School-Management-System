@extends('layouts.app')
@section('title','Professor records')
@section('content')
<div class="page-heading"><div><h1>Professor records</h1><p class="text-secondary">EMS identities, teaching authorizations and recorded availability.</p></div><a href="/imports" class="btn btn-primary">Import EMS records</a></div>
<form class="row g-3 mb-4" method="get">
<div class="col-lg-4"><label for="prof-search" class="form-label">Find a professor</label><input id="prof-search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name, employee ID or email"></div>
<div class="col-md-3"><label for="department" class="form-label">Department</label><select id="department" name="department" class="form-select"><option value="">All departments</option>@foreach($departments as $department)<option @selected(request('department')===$department)>{{ $department }}</option>@endforeach</select></div>
<div class="col-md-3"><label for="employment_type" class="form-label">Employment</label><select id="employment_type" name="employment_type" class="form-select"><option value="">All types</option>@foreach(['FULL_TIME'=>'Full-time','PART_TIME'=>'Part-time'] as $key=>$label)<option value="{{ $key }}" @selected(request('employment_type')===$key)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-2"><label for="active" class="form-label">Status</label><select id="active" name="active" class="form-select"><option value="">All statuses</option><option value="1" @selected(request('active')==='1')>Active</option><option value="0" @selected(request('active')==='0')>Inactive</option></select></div>
<div class="col-12"><button class="btn btn-primary">Apply filters</button> <a href="/professors" class="btn btn-outline-secondary">Clear</a></div>
</form>
<div class="card border-0 shadow-sm p-4"><div class="table-responsive"><table class="table table-striped table-hover"><thead><tr>
@foreach(['employee_id'=>'Employee ID','name'=>'Professor','department'=>'Department','employment_type'=>'Employment','active'=>'Status','last_synced_at'=>'Last EMS sync'] as $column=>$label)
@include('partials.sort-heading',['column'=>$column,'label'=>$label,'defaultSort'=>'name'])
@endforeach
<th>Teaching and availability</th></tr></thead><tbody>
@forelse($professors as $p)
<tr><td>{{ $p->employee_id }}</td><td><strong>{{ $p->name }}</strong><div class="small text-secondary">{{ $p->email }}</div></td><td>{{ $p->department }}</td><td>{{ str_replace('_',' ',$p->employment_type) }}</td><td><span class="badge {{ $p->active?'text-bg-success':'text-bg-secondary' }}">{{ $p->active?'Active':'Inactive' }}</span></td><td>{{ $p->last_synced_at??'Not recorded' }}</td><td><details><summary>View subjects and hours</summary><ul class="small mt-2">@forelse($qualifications[$p->id]??[] as $subject)<li>{{ $subject->code }} · {{ $subject->name }}</li>@empty<li>No authorized subjects recorded.</li>@endforelse</ul><ul class="small">@forelse($availability[$p->id]??[] as $window)<li>{{ $window->day }} {{ substr($window->start_time,0,5) }}–{{ substr($window->end_time,0,5) }}</li>@empty<li>No availability recorded.</li>@endforelse</ul></details></td></tr>
@empty<tr><td colspan="7">No professors match these filters. Clear filters or import an approved EMS export.</td></tr>@endforelse
</tbody></table></div>{{ $professors->links('pagination::bootstrap-5') }}</div>
<div class="d-flex flex-wrap gap-2 mt-3"><a href="/academic/professor_subjects" class="btn btn-outline-primary">Manage teaching authorizations</a><a href="/academic/professor_availabilities" class="btn btn-outline-primary">Manage availability</a></div>
@endsection
