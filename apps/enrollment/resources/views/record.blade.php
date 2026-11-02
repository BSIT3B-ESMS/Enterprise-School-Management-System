

@extends('layouts.app')

@section('title','Academic record')

@section('content')

<h1 class="h2">{{ $student->name }}</h1><p>{{ $student->classification }} · Year {{ $student->year_level }}</p>

@if(auth()->user()->role==='registrar')<form method="post" action="/records/{{ $student->id }}" class="d-flex flex-wrap gap-2 mb-4">@csrf<label for="classification" class="visually-hidden">Classification</label><select id="classification" name="classification" class="form-select w-auto">

@foreach(['regular','irregular','transferee','returning'] as $type)<option @selected($student->classification===$type)>{{ $type }}</option>

@endforeach</select><label for="year_level" class="visually-hidden">Year level</label><input id="year_level" class="form-control w-auto" name="year_level" type="number" min="1" max="8" value="{{ $student->year_level }}" required><label for="student_number" class="visually-hidden">Student number</label><input class="form-control w-auto" id="student_number" name="student_number" placeholder="Student number" value="{{ $student->student_number }}" maxlength="50"><button class="btn btn-outline-primary">Update student details</button></form>

@endif
<div class="card border-0 shadow-sm p-4 mb-4"><h2 class="h5">Academic history</h2><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Subject</th><th>Period</th><th>Grade</th><th>Status</th><th>Remarks</th></tr></thead><tbody>

@forelse($records as $r)<tr><td>{{ $r->code }} · {{ $r->name }}</td><td>{{ $r->period }}</td><td>{{ $r->grade }}</td><td>{{ $r->status }}{{ $records->where('subject_id',$r->subject_id)->where('id','<',$r->id)->isNotEmpty()?' · Retake / later attempt':'' }}{{ !$r->active?' (inactive)':'' }}</td><td>{{ $r->remarks }}</td></tr>

@empty<tr><td colspan="5">No academic history has been entered.</td></tr>

@endforelse</tbody></table></div></div>
<div class="card border-0 shadow-sm p-4"><h2 class="h5">Curriculum requirements</h2><div class="table-responsive"><table class="table table-striped"><thead><tr><th>Subject</th><th>Units</th><th>Year / Semester</th><th>Prerequisites</th><th>Completion</th></tr></thead><tbody>

@foreach($requirements as $r)<tr><td>{{ $r->code }} · {{ $r->name }}</td><td>{{ $r->units }}</td><td>{{ $r->year_level }} / {{ $r->semester }}</td><td>
@foreach($prerequisites[$r->id]??[] as $prerequisite)
<div>{{ $prerequisite->code }}{{ $prerequisite->alternative_group?' (alternative group '.$prerequisite->alternative_group.')':'' }}{{ $prerequisite->minimum_grade!==null?' · minimum grade '.$prerequisite->minimum_grade:'' }}</div>
@endforeach
</td><td>{{ $records->contains(fn($record)=>$record->subject_id===$r->id && $record->active && in_array($record->status,['PASSED','CREDITED']))?'Completed / credited':'Still required' }}</td></tr>

@endforeach</tbody></table></div></div>

@if(auth()->user()->role==='department_head')
<div class="card border-0 shadow-sm p-4 mt-4"><h2 class="h5">Authorize prerequisite exception</h2><p class="text-secondary">Only approve an exception permitted by institutional policy. Record the authority and academic reasons in full.</p><form method="post" action="/academic-overrides">@csrf<input type="hidden" name="student_id" value="{{ $student->id }}"><label for="override-subject" class="form-label">Subject</label><select id="override-subject" name="subject_id" class="form-select mb-3">

@foreach($requirements as $r)
<option value="{{ $r->id }}">{{ $r->code }} · {{ $r->name }}</option>

@endforeach
</select><label for="override-period" class="form-label">Academic period</label><select id="override-period" name="period_id" class="form-select mb-3">

@foreach($periods as $p)
<option value="{{ $p->id }}">{{ $p->name }}</option>

@endforeach
</select><label for="override-reason" class="form-label">Authority and reason (at least 20 characters)</label><textarea id="override-reason" name="reason" class="form-control mb-3" required minlength="20" maxlength="3000"></textarea><button class="btn btn-outline-primary">Record authorized exception</button></form></div>

@endif

@if($overrides->isNotEmpty())
<h2 class="h5 mt-4">Recorded academic exceptions</h2>

@foreach($overrides as $o)
<p>Subject #{{ $o->subject_id }} · Period #{{ $o->period_id }} · {{ $o->reason }}</p>

@endforeach

@endif

<div class="card border-0 shadow-sm p-4 mt-4"><h2 class="h5">Assigned curriculum: {{ $curriculum->name }}</h2><p>Total curriculum units: {{ $requirements->sum('units') }}</p>
@foreach($requirements->groupBy(fn($r)=>'Year '.$r->year_level.' / Semester '.$r->semester) as $label=>$group)
<p>{{ $label }}: {{ $group->sum('units') }} units</p>
@endforeach
@if(auth()->user()->role==='registrar')
<form method="post" action="/records/{{ $student->id }}/curriculum">
@csrf
<input type="hidden" name="previous_curriculum_id" value="{{ $student->curriculum_id }}"><label for="new-curriculum" class="form-label">Assign a different curriculum version</label><select id="new-curriculum" name="curriculum_id" class="form-select mb-3" required><option value="">Choose an active version in this program</option>
@foreach($curriculums->where('id','!=',$student->curriculum_id) as $version)
<option value="{{ $version->id }}">{{ $version->name }}</option>
@endforeach
</select><label for="curriculum-reason" class="form-label">Verification and reason for reassignment</label><textarea id="curriculum-reason" name="reason" class="form-control mb-3" minlength="20" maxlength="3000" required></textarea><button class="btn btn-outline-primary">Record curriculum assignment</button></form>
@endif
@if($assignments->isNotEmpty())
<h3 class="h6 mt-4">Assignment history</h3><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Previous version</th><th>Assigned version</th><th>Registrar / reason</th></tr></thead><tbody>
@foreach($assignments as $assignment)
<tr><td>{{ $assignment->created_at }}</td><td>{{ $assignment->previous }}</td><td>{{ $assignment->curriculum }}</td><td>{{ $assignment->registrar }} · {{ $assignment->reason }}</td></tr>
@endforeach
</tbody></table></div>
@endif
</div>
@if($credits->isNotEmpty())
<div class="card border-0 shadow-sm p-4 mt-4"><h2 class="h5">Evaluated subject equivalencies</h2><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Original institution / subject</th><th>Equivalent subject</th><th>Decision and evidence</th></tr></thead><tbody>
@foreach($credits as $credit)
<tr><td>{{ $credit->source_institution }} · {{ $credit->source_subject }}</td><td>{{ $credit->code }}</td><td>{{ $credit->decision }} · {{ $credit->evidence_reference }}<br><a href="/special-cases/{{ $credit->special_case_id }}">View evaluation #{{ $credit->special_case_id }}</a></td></tr>
@endforeach
</tbody></table></div></div>
@endif
@endsection
