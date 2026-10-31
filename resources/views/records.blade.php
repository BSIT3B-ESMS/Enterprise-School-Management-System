

@extends('layouts.app')

@section('title','Student records')

@section('content')

<h1 class="h2 mb-4">Student records</h1><div class="card border-0 shadow-sm p-4"><form class="d-flex gap-2 mb-3" method="get"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search all student records" aria-label="Search all student records"><select name="classification" class="form-select" aria-label="Student classification"><option value="">All classifications</option>@foreach(['regular','irregular','transferee','returning'] as $type)<option value="{{ $type }}" @selected(request('classification')===$type)>{{ ucfirst($type) }}</option>@endforeach</select><button class="btn btn-outline-primary">Search</button></form><input class="form-control mb-3" id="record-search" placeholder="Filter this page" aria-label="Filter students"><div class="table-responsive"><table class="table table-striped table-hover"><thead><tr>@foreach(['name'=>'Student','admission_id'=>'Admission ID','curriculum'=>'Curriculum','classification'=>'Classification','year_level'=>'Year'] as $column=>$label)@include('partials.sort-heading',['column'=>$column,'label'=>$label,'defaultSort'=>'name'])@endforeach<th></th></tr></thead><tbody>

@forelse($students as $s)<tr><td>{{ $s->name }}</td><td>{{ $s->admission_id }}</td><td>{{ $s->curriculum }}</td><td>{{ $s->classification }}</td><td>{{ $s->year_level }}</td><td><a href="/records/{{ $s->id }}">View academic records</a></td></tr>

@empty<tr><td colspan="6">No student records available.</td></tr>

@endforelse</tbody></table></div>{{ $students->links('pagination::bootstrap-5') }}</div>

@endsection

@push('scripts')<script>$('#record-search').on('input',function(){let term=$(this).val().toLowerCase();$('tbody tr').each(function(){$(this).toggle($(this).text().toLowerCase().includes(term));});});</script>

@endpush
