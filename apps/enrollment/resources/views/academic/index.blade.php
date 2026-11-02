

@extends('layouts.app')

@section('title',ucwords(str_replace('_',' ',$resource)))

@section('content')

<h1 class="h2 mb-4">{{ ucwords(str_replace('_',' ',$resource)) }}</h1>
@if($resource==='subjects')<div class="d-flex flex-wrap gap-2 mb-4"><a class="btn btn-outline-primary" href="/academic/subjects">All subjects</a><a class="btn btn-outline-primary" href="/academic/subjects?scope=general">General education</a><a class="btn btn-outline-primary" href="/academic/subjects?scope=college">College common</a><a class="btn btn-outline-primary" href="/academic/subjects?scope=program">Program major</a></div><p class="text-secondary">Scope organizes the catalog. Curriculum requirements determine each program’s year and semester assignments. Weekly hours are contact hours; verify them separately from academic units.</p>@endif
<div class="row g-4"><div class="col-xl-8"><div class="card border-0 shadow-sm p-4"><form class="d-flex gap-2 mb-3"><label class="visually-hidden" for="search">Search records</label><input id="search" class="form-control" name="search" placeholder="Search records" value="{{ request('search') }}"><select name="active" class="form-select" aria-label="Record status"><option value="">All statuses</option><option value="1" @selected(request('active')==='1')>Active</option><option value="0" @selected(request('active')==='0')>Inactive</option></select><button class="btn btn-outline-primary">Search</button></form><div class="table-responsive"><table class="table table-striped table-hover align-middle"><thead><tr><th>ID</th>

@foreach($fields as $field=>$rule)@include('partials.sort-heading',['column'=>$field,'label'=>ucwords(str_replace('_',' ',$field)),'defaultDirection'=>'desc'])

@endforeach

@if(in_array($resource,['sections','offerings']))
<th>Enrolled / Capacity</th><th>Available seats</th>

@endif
<th>Status</th><th></th></tr></thead><tbody>

@forelse($rows as $row)<tr><td>{{ $row->id }}</td>

@foreach($fields as $field=>$rule)<td>{{ $choices[$field][$row->$field] ?? $row->$field }}</td>

@endforeach

@if(in_array($resource,['sections','offerings']))
<td>{{ $row->enrolled_count }} / {{ $row->capacity }}</td><td>{{ max(0,$row->capacity-$row->enrolled_count) }}

@if($row->enrolled_count>=$row->capacity)
<div class="small text-secondary">Full. Registrar approval is required to create another section.</div>

@endif
</td>

@endif
<td><span class="badge {{ $row->active?'text-bg-success':'text-bg-secondary' }}">{{ $row->active?'Active':'Inactive' }}</span></td><td>

@if($resource==='curriculums')
<a href="/curriculums/{{ $row->id }}" class="btn btn-sm btn-outline-secondary mb-1">View units</a>
@endif
@if($row->active)<a href="?edit={{ $row->id }}" class="btn btn-sm btn-outline-primary mb-1">Edit</a><form method="post" action="/academic/{{ $resource }}/{{ $row->id }}/deactivate" class="confirm-action">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $resource==='rooms'?'Mark unavailable':'Deactivate' }}</button></form>
@else
<form method="post" action="/academic/{{ $resource }}/{{ $row->id }}/activate">@csrf<button class="btn btn-sm btn-outline-primary">{{ $resource==='rooms'?'Mark available':'Activate' }}</button></form>

@endif</td></tr>

@empty<tr><td colspan="20" class="text-secondary py-5 text-center">No records yet. Add the first record to get started.</td></tr>

@endforelse</tbody></table></div>{{ $rows->links('pagination::bootstrap-5') }}</div></div><div class="col-xl-4"><div id="record-editor" class="card border-0 shadow-sm p-4"><h2 class="h5">{{ $editing?'Edit record #'.$editing->id:'Add record' }}</h2><form method="post" action="/academic/{{ $resource }}{{ $editing?'/'.$editing->id:'' }}">@csrf

@foreach($fields as $field=>$rule)<label class="form-label mt-3" for="{{ $field }}">{{ ucwords(str_replace('_',' ',$field)) }}</label>

@if(isset($choices[$field]))<select class="form-select" id="{{ $field }}" name="{{ $field }}" @required(str_contains($rule,'required'))><option value="">Select…</option>

@foreach($choices[$field] as $value=>$label)<option value="{{ $value }}" @selected(old($field,$editing?->$field??($resource==='sections' && $field==='capacity'?$defaultCapacity:null))==$value)>{{ $label }}</option>

@endforeach</select>

@else<input class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field,$editing?->$field??($resource==='sections' && $field==='capacity'?$defaultCapacity:null)) }}" type="{{ str_contains($rule,'date_format')?'time':(str_contains($rule,'integer')?'number':'text') }}" @required(str_contains($rule,'required'))>

@endif

@endforeach
@if($resource==='subjects')
<input type="hidden" name="manage_prerequisites" value="1"><label for="prerequisite_ids" class="form-label mt-4">Prerequisite subjects</label>
<select multiple id="prerequisite_ids" name="prerequisite_ids[]" class="form-select" size="6" aria-describedby="prerequisite-help">
@foreach($prerequisiteChoices as $choice)<option value="{{ $choice->id }}" @selected(in_array($choice->id,old('prerequisite_ids',$selectedPrerequisites)))>{{ $choice->code }} · {{ $choice->name }}</option>@endforeach
</select><p id="prerequisite-help" class="small text-secondary mt-2">Choose none for an introductory subject. New selections are mandatory prerequisites. Existing alternative groups and minimum grades are retained; manage those in <a href="/academic/prerequisites">Prerequisites</a>.</p>
@endif<button class="btn btn-primary mt-4">{{ $editing?'Save changes':'Create record' }}</button></form></div></div></div>

@if($editing)
<div class="modal fade" id="edit-record-modal" tabindex="-1" aria-labelledby="edit-record-title" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-5" id="edit-record-title">Edit academic record</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body" id="edit-record-body"></div><div class="modal-footer"><a href="/academic/{{ $resource }}" class="btn btn-outline-secondary">Return to records</a></div></div></div></div>
@endif
@endsection

@push('scripts')<script>$('.confirm-action').on('submit',function(){return confirm('Deactivate this record while retaining its history?');});</script>

<script>
$('#subject_id').on('change',function(){const selected=$(this).val();$('#required_subject_id option').each(function(){$(this).prop('disabled',this.value===selected);});if($('#required_subject_id').val()===selected)$('#required_subject_id').val('');}).trigger('change');
@if($editing)
$('#record-editor').appendTo('#edit-record-body');new bootstrap.Modal(document.getElementById('edit-record-modal')).show();
@endif
</script>
@endpush
