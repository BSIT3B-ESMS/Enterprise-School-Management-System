@extends('layouts.app')
@section('title','Academic evaluation')
@section('content')
<a href="/special-cases" class="btn btn-sm btn-outline-secondary mb-3">All evaluations</a>
<h1 class="h2">Evaluation #{{ $case->id }} · {{ $student->name }}</h1>
<p>{{ $student->classification }} · {{ $period->name }} · <span class="badge text-bg-secondary">{{ $case->status }}</span></p>
<a href="/records/{{ $student->id }}">Review academic records and curriculum</a>
<div class="card border-0 shadow-sm p-4 my-4">
<h2 class="h5">Student request</h2><p style="white-space:pre-wrap">{{ $case->request }}</p>
@if($requested->isNotEmpty())
<p>Requested subjects: {{ $requested->pluck('code')->implode(', ') }}</p>
@endif
@if($case->evaluation)
<h2 class="h5 mt-3">Academic findings</h2><p style="white-space:pre-wrap">{{ $case->evaluation }}</p><p class="text-secondary">Reviewed by {{ $reviewer }} · {{ $evaluatedAt??$case->updated_at }}</p>
@endif
@if($case->forwarded_at)
<p>Forwarded to Registrar: {{ $case->forwarded_at }}</p>
@endif
</div>
@if($credits->isNotEmpty())
<div class="card border-0 shadow-sm p-4 mb-4"><h2 class="h5">Subject equivalency decisions</h2><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Original school and subject</th><th>Grade / units</th><th>PLP equivalent</th><th>Decision</th><th>Evidence and reason</th></tr></thead><tbody>
@foreach($credits as $credit)
<tr><td>{{ $credit->source_institution }}<br>{{ $credit->source_subject }}</td><td>{{ $credit->source_grade ?? 'Not supplied' }} / {{ $credit->source_units }}</td><td>{{ $credit->code }} · {{ $credit->name }}</td><td>{{ $credit->decision }}</td><td>{{ $credit->evidence_reference }}<br>{{ $credit->reason }}</td></tr>
@endforeach
</tbody></table></div></div>
@endif
@if(auth()->user()->role==='department_head' && $case->status==='pending')
<form method="post" action="/special-cases/{{ $case->id }}/evaluate" class="card border-0 shadow-sm p-4">
@csrf
<h2 class="h5">Record evaluation</h2><p>Review the student’s official records. Add one row for each subject equivalency decision. Approved rows create credited academic records when this evaluation is approved.</p>
<div id="credit-rows">
@foreach(old('credits',[]) as $i=>$credit)
@include('partials.credit-row',['index'=>$i,'credit'=>$credit])
@endforeach
</div>
<button type="button" id="add-credit" class="btn btn-outline-primary align-self-start mb-3">Add subject equivalency</button>
<label for="evaluation" class="form-label">Findings, eligible subjects, and approval authority</label><textarea id="evaluation" name="evaluation" class="form-control mb-3" required maxlength="5000">{{ old('evaluation') }}</textarea>
<div class="d-flex gap-2 flex-wrap"><button name="status" value="approved" class="btn btn-primary">Approve evaluation and credits</button><button name="status" value="rejected" class="btn btn-outline-secondary">Reject evaluation</button></div>
</form>
<template id="credit-template">
@include('partials.credit-row',['index'=>'__INDEX__','credit'=>[]])
</template>
<form method="post" action="/special-cases/{{ $case->id }}/defer" class="my-4">@csrf<h2 class="h5">Defer unavailable subjects</h2><p>Available only when every requested subject is unavailable or its prerequisites are unmet.</p><label for="defer-reason" class="form-label">Reason</label><textarea id="defer-reason" name="reason" class="form-control mb-3" required maxlength="2000"></textarea><button class="btn btn-outline-secondary">Defer to waiting list</button></form>
@endif
@if(auth()->user()->role==='department_head' && $case->status==='approved')
<div class="card border-0 shadow-sm p-4"><h2 class="h5">Build the proposed schedule</h2><p>Eligible primary-section classes stay assigned. Add requested subjects from available sections. Conflicts, prerequisites and capacity are checked before publishing.</p><form method="post" action="/special-cases/{{ $case->id }}/forward-to-registrar">
@csrf
<h3 class="h6">Primary section · fixed assignments</h3>
@forelse($builder['base'] as $class)<input type="hidden" name="offering_ids[]" value="{{ $class->id }}"><div class="border-bottom py-2">{{ $class->code }} · {{ $class->name }} · {{ $class->section }} @foreach($class->meetings as $meeting)<span class="d-block small text-secondary">{{ $meeting->day }} {{ substr($meeting->start_time,0,5) }}–{{ substr($meeting->end_time,0,5) }} · {{ $meeting->room }}</span>@endforeach</div>@empty<p class="text-secondary">No eligible primary-section classes are scheduled.</p>@endforelse
<h3 class="h6 mt-4">Requested additional classes</h3>@forelse($builder['extras'] as $class)<label class="d-block border rounded p-3 mb-2"><input type="checkbox" name="offering_ids[]" value="{{ $class->id }}"> {{ $class->code }} · {{ $class->name }} · {{ $class->section }} @foreach($class->meetings as $meeting)<span class="d-block small text-secondary">{{ $meeting->day }} {{ substr($meeting->start_time,0,5) }}–{{ substr($meeting->end_time,0,5) }} · {{ $meeting->room }}</span>@endforeach</label>@empty<p class="text-secondary">No eligible requested additions are offered. Unavailable requests remain on the subject waiting list.</p>@endforelse
<div class="d-flex flex-wrap gap-2 mt-3"><button class="btn btn-primary" name="action" value="send">Send to student</button><button class="btn btn-outline-primary" name="action" value="forward">Forward draft to Registrar</button></div></form></div>
@php($calendar=$builder['base']->merge($builder['extras'])->flatMap(fn($class)=>$class->meetings->map(fn($m)=>(object)array_merge((array)$m,['code'=>$class->code,'choice_id'=>$builder['extras']->contains('id',$class->id)?$class->id:0]))))
@include('partials.week-timetable',['caption'=>'Assignment preview · primary-section classes stay fixed. Select or drag requested classes onto their recorded day.'])
@endif
@endsection
@push('scripts')
<script>
let creditIndex = {{ count(old('credits',[])) }};
$('#add-credit').on('click',function(){ $('#credit-rows').append($('#credit-template').html().replaceAll('__INDEX__',String(creditIndex++))); });
$('#credit-rows').on('click','.remove-credit',function(){ $(this).closest('fieldset').remove(); });
function previewAssignments(){$('.timetable-meeting[data-choice]').each(function(){const id=Number(this.dataset.choice);$(this).toggle(!id||$('input[name="offering_ids[]"][value="'+id+'"]:checked').length>0);});}
$('input[type="checkbox"][name="offering_ids[]"]').on('change',previewAssignments).closest('label').attr('draggable','true').on('dragstart',function(e){e.originalEvent.dataTransfer.setData('text/plain',$(this).find('input').val());});
$('.week-cell').on('dragover',e=>e.preventDefault()).on('drop',function(e){e.preventDefault();const id=e.originalEvent.dataTransfer.getData('text/plain');if($('.timetable-meeting[data-choice="'+id+'"]').closest('[data-day="'+this.dataset.day+'"]').length){$('input[name="offering_ids[]"][value="'+id+'"]').prop('checked',true);previewAssignments();}});previewAssignments();
</script>
@endpush
