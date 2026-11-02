

@extends('layouts.app')

@section('title','Enrollment proposal')

@section('content')

<p class="small text-secondary mb-2">Proposal version {{ $e->version }}</p><h1 class="h2 mt-2">{{ $e->name }}</h1><p>{{ $e->period }} · <strong>{{ ucwords(str_replace('_',' ',$e->status)) }}</strong></p><p>{{ $e->program }} · Year {{ $e->year_level }} · {{ $e->classification }} · {{ $e->academic_year }} / Semester {{ $e->semester }}</p><div class="card border-0 shadow-sm p-4"><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Subject</th><th>Units</th><th>Section</th><th>Schedule</th></tr></thead><tbody>

@foreach($items as $item)<tr><td>{{ $item->code }}<br>{{ $item->name }}</td><td>{{ $item->units }}</td><td>{{ $item->section }}</td><td>

@foreach($meetings[$item->offering_id]??[] as $meeting)<div>{{ $meeting->day }} {{ substr($meeting->start_time,0,5) }}–{{ substr($meeting->end_time,0,5) }} · {{ $meeting->room }} · {{ $meeting->professor }}</div>

@endforeach</td></tr>

@endforeach</tbody><tfoot><tr><th>Total units</th><td colspan="3">{{ $items->sum('units') }}</td></tr></tfoot></table></div>

@if(auth()->user()->role==='student' && in_array($e->status,['proposed','special_proposed']))<form method="post" action="/enrollments/{{ $e->id }}/decision">@csrf<input type="hidden" name="version" value="{{ $e->version }}"><label for="remarks" class="form-label">Remarks (required when requesting revision)</label><textarea id="remarks" class="form-control mb-3" name="remarks" maxlength="2000"></textarea><button class="btn btn-primary" name="decision" value="accept">Accept and finalize enrollment</button><button class="btn btn-outline-secondary" name="decision" value="decline">Request revision</button></form>

@endif

@if(auth()->user()->role==='registrar' && $e->status==='accepted')<form method="post" action="/enrollments/{{ $e->id }}/finalize">@csrf<button class="btn btn-primary">Finalize accepted enrollment</button></form>

@endif

@if($e->status==='finalized')<a class="btn btn-primary" href="/enrollments/{{ $e->id }}/cor">View and print COR</a> <a href="/enrollments/{{ $e->id }}/pdf" class="btn btn-outline-primary">Download PDF</a>

@endif</div>
@php($calendar=$items->flatMap(fn($item)=>collect($meetings[$item->offering_id]??[])->map(fn($meeting)=>(object)array_merge((array)$meeting,['code'=>$item->code]))))
@include('partials.week-timetable',['caption'=>'Proposed weekly schedule'])

@if(auth()->user()->role==='registrar' && $e->status==='finalized')
<form method="post" action="/enrollments/{{ $e->id }}/revise" class="mt-3">@csrf<label class="form-label" for="revision-reason">Reason for authorized revision</label><input id="revision-reason" name="remarks" required class="form-control mb-2" maxlength="2000"><button class="btn btn-outline-secondary">Reopen enrollment for revision</button></form>

@endif
<h2 class="h5 mt-4">Decision history</h2><ul class="list-group">

@foreach($events as $event)<li class="list-group-item">Version {{ $event->version }} · {{ str_replace('_',' ',$event->action) }} · {{ $event->created_at }} 

@if($event->remarks)<p class="mb-0">{{ $event->remarks }}</p>

@endif</li>

@endforeach</ul>

@if($versions->isNotEmpty())
<h2 class="h5 mt-4">COR versions</h2><ul class="list-group">

@foreach($versions as $version)
<li class="list-group-item">Version {{ $version->version }} · {{ $version->created_at }} · <a href="/enrollments/{{ $e->id }}/cor/{{ $version->version }}">View saved COR</a> · <a href="/enrollments/{{ $e->id }}/pdf/{{ $version->version }}">PDF</a></li>

@endforeach
</ul>

@endif

@endsection
