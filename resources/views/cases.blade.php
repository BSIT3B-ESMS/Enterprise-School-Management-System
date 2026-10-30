

@extends('layouts.app')

@section('title','Academic evaluations')

@section('content')

<h1 class="h2 mb-4">Academic evaluations</h1>

@if(auth()->user()->role==='student')<div class="card border-0 shadow-sm p-4 mb-4"><h2 class="h5">Request evaluation or back subjects</h2><form method="post" action="/special-cases">@csrf<label class="form-label" for="period_id">Academic period</label><select id="period_id" name="period_id" class="form-select mb-3" required>

@foreach($periods as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>

@endforeach</select><label class="form-label" for="request">Subjects requested and academic circumstances</label><textarea id="request" class="form-control mb-3" name="request" required maxlength="5000"></textarea><button class="btn btn-primary">Submit request</button></form></div>

@endif

@forelse($rows as $row)<article class="card border-0 shadow-sm p-4 mb-3"><div class="d-flex justify-content-between"><h2 class="h5">{{ $row->name }} · {{ $row->classification }}</h2><span>{{ $row->status }}</span></div><p class="text-secondary">{{ $row->period }}</p><p style="white-space:pre-wrap">{{ $row->request }}</p>

@if($row->evaluation)<p><strong>Evaluation:</strong> {{ $row->evaluation }}</p>

@endif

@if(auth()->user()->role==='department_head' && $row->status==='pending')<form method="post" action="/special-cases/{{ $row->id }}/evaluate">@csrf<label class="form-label" for="evaluation-{{ $row->id }}">Academic findings and reasons</label><textarea id="evaluation-{{ $row->id }}" name="evaluation" class="form-control mb-3" required maxlength="5000"></textarea><button name="status" value="approved" class="btn btn-primary">Approve evaluation</button><button name="status" value="rejected" class="btn btn-outline-secondary">Reject request</button></form>

@endif</article>

@empty<p>No academic evaluation requests.</p>

@endforelse{{ $rows->links('pagination::bootstrap-5') }}

@endsection