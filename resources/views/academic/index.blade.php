

@extends('layouts.app')

@section('title',ucwords(str_replace('_',' ',$resource)))

@section('content')

<h1 class="h2 mb-4">{{ ucwords(str_replace('_',' ',$resource)) }}</h1>
<div class="row g-4"><div class="col-xl-8"><div class="card border-0 shadow-sm p-4"><form class="d-flex gap-2 mb-3"><label class="visually-hidden" for="search">Search records</label><input id="search" class="form-control" name="search" placeholder="Search records" value="{{ request('search') }}"><button class="btn btn-outline-primary">Search</button></form><div class="table-responsive"><table class="table table-striped table-hover align-middle"><thead><tr><th>ID</th>

@foreach($fields as $field=>$rule)<th>{{ ucwords(str_replace('_',' ',$field)) }}</th>

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

@if($row->active)<a href="?edit={{ $row->id }}" class="btn btn-sm btn-outline-primary mb-1">Edit</a><form method="post" action="/academic/{{ $resource }}/{{ $row->id }}/deactivate" class="confirm-action">@csrf<button class="btn btn-sm btn-outline-secondary">Deactivate</button></form>
@else
<form method="post" action="/academic/{{ $resource }}/{{ $row->id }}/activate">@csrf<button class="btn btn-sm btn-outline-primary">Activate</button></form>

@endif</td></tr>

@empty<tr><td colspan="20" class="text-secondary py-5 text-center">No records yet. Add the first record to get started.</td></tr>

@endforelse</tbody></table></div>{{ $rows->links('pagination::bootstrap-5') }}</div></div><div class="col-xl-4"><div class="card border-0 shadow-sm p-4"><h2 class="h5">{{ $editing?'Edit record #'.$editing->id:'Add record' }}</h2><form method="post" action="/academic/{{ $resource }}{{ $editing?'/'.$editing->id:'' }}">@csrf

@foreach($fields as $field=>$rule)<label class="form-label mt-3" for="{{ $field }}">{{ ucwords(str_replace('_',' ',$field)) }}</label>

@if(isset($choices[$field]))<select class="form-select" id="{{ $field }}" name="{{ $field }}" @required(str_contains($rule,'required'))><option value="">Select…</option>

@foreach($choices[$field] as $value=>$label)<option value="{{ $value }}" @selected(old($field,$editing?->$field)==$value)>{{ $label }}</option>

@endforeach</select>

@else<input class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field,$editing?->$field) }}" type="{{ str_contains($rule,'date_format')?'time':(str_contains($rule,'integer')?'number':'text') }}" @required(str_contains($rule,'required'))>

@endif

@endforeach
<button class="btn btn-primary mt-4">{{ $editing?'Save changes':'Create record' }}</button></form></div></div></div>

@endsection

@push('scripts')<script>$('.confirm-action').on('submit',function(){return confirm('Deactivate this record while retaining its history?');});</script>

@endpush