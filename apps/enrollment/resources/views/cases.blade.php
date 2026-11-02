

@extends('layouts.app')

@section('title','Academic evaluations')

@section('content')

<h1 class="h2 mb-4">Academic evaluations</h1>

@if(auth()->user()->role==='student')<div class="card border-0 shadow-sm p-4 mb-4"><h2 class="h5">Request evaluation or back subjects</h2><form method="post" action="/special-cases">@csrf<label class="form-label" for="period_id">Academic period</label><select id="period_id" name="period_id" class="form-select mb-3" required>

@foreach($periods as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>

@endforeach</select><label class="form-label" for="request">Subjects requested and academic circumstances</label><textarea id="request" class="form-control mb-3" name="request" required maxlength="5000"></textarea><label class="form-label" for="requested-subjects">Specific subjects requested (optional)</label><select multiple class="form-select mb-3" name="requested_subject_ids[]" id="requested-subjects">
@foreach($subjects as $subject)
<option value="{{ $subject->id }}">{{ $subject->code }} · {{ $subject->name }}</option>
@endforeach
</select><button class="btn btn-primary">Submit request</button></form></div>

@endif

@forelse($rows as $row)<article class="card border-0 shadow-sm p-4 mb-3"><div class="d-flex justify-content-between"><h2 class="h5">{{ $row->name }} · {{ $row->classification }}</h2><span>{{ $row->status }}</span></div><p class="text-secondary">{{ $row->period }}</p><p style="white-space:pre-wrap">{{ $row->request }}</p>

@if($row->evaluation)<p><strong>Evaluation:</strong> {{ $row->evaluation }}</p>

@endif

<a href="/special-cases/{{ $row->id }}" class="btn btn-outline-primary align-self-start">View evaluation and subject credits</a></article>
@empty<p>No academic evaluation requests.</p>

@endforelse{{ $rows->links('pagination::bootstrap-5') }}

@endsection
