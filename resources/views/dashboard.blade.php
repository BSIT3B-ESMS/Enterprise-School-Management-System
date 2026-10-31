@extends('layouts.app')
@section('title',$portal.' Portal')
@section('content')
<div class="page-heading"><div><h1>{{ $portal }} overview</h1><p class="text-secondary">Welcome, {{ auth()->user()->name }}. {{ $description }}</p></div><a class="btn btn-outline-secondary" href="{{ route('password.edit') }}">Account settings</a></div>
@if(auth()->user()->role==='registrar')
@include('partials.enrollment-pipeline')
@endif
<section aria-labelledby="enrollment-summary-title">
<h2 class="section-heading" id="enrollment-summary-title">Enrollment summary</h2>
<div class="metrics-grid">
@foreach(array_slice($metrics,0,4,true) as $label=>$value)
<div class="metric"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
@endforeach
</div></section>
<section class="dashboard-services" aria-labelledby="services-title">
<h2 class="h4" id="services-title">Enrollment services</h2><p>{{ auth()->user()->role==='registrar'?'Maintain academic records, import approved exports, prepare schedules, and process accepted enrollment.':(auth()->user()->role==='department_head'?'Review academic records and evaluation requests for your assigned programs, then prepare special academic proposals.':'Review your notifications, academic requirements, enrollment proposals, and registration records.') }}</p>
<div class="quick-actions">
<a href="/enrollments" class="btn btn-primary">{{ auth()->user()->role === 'student' ? 'View my enrollments' : 'Manage enrollments' }}</a>
@if(auth()->user()->role === 'registrar')
<a href="/term-calendar" class="btn btn-outline-primary">Semester & enrollment pipeline</a><a href="/students" class="btn btn-outline-primary">Students & sections</a>
<a href="/scheduler" class="btn btn-outline-primary">Open scheduler</a><a href="/records" class="btn btn-outline-primary">Find student records</a>
@elseif(auth()->user()->role === 'department_head')
<a href="/special-cases" class="btn btn-outline-primary">Review evaluations</a><a href="/records" class="btn btn-outline-primary">View academic records</a>
@else
<a href="/records" class="btn btn-outline-primary">My academic records</a><a href="/notifications" class="btn btn-outline-primary">View notifications</a>
@endif
</div></section>
@if(count($metrics) > 4)
<section aria-labelledby="academic-summary-title"><h2 id="academic-summary-title" class="section-heading">Academic operations</h2><div class="secondary-metrics">
@foreach(array_slice($metrics,4,null,true) as $label=>$value)
<div class="secondary-metric"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
@endforeach
</div></section>
@endif

@if($student)
<div class="card border-0 shadow-sm p-4 mt-4"><h2 class="h5">Your student information</h2><p>{{ $student->student_number??$student->admission_id }} · {{ $student->program }} · Year {{ $student->year_level }} · {{ $student->classification }}</p><p>Curriculum: {{ $student->curriculum }}</p><a href="/records/{{ $student->id }}">View academic history and prerequisites</a></div>
<div class="row g-4 mt-1"><div class="col-lg-7"><div class="card border-0 shadow-sm p-4 h-100"><h2 class="h5">Enrollment proposals and COR</h2>
@forelse($recent as $enrollment)
<div class="border-bottom py-3"><strong>{{ $enrollment->period }}</strong><p>{{ ucwords(str_replace('_',' ',$enrollment->status)) }} · Version {{ $enrollment->version }}</p><a href="/enrollments/{{ $enrollment->id }}" class="btn btn-sm btn-outline-primary">View subjects and enrollment</a>
@if($enrollment->status==='finalized')
<a href="/enrollments/{{ $enrollment->id }}/cor" class="btn btn-sm btn-primary">View COR</a>
@endif
</div>
@empty
<p>No enrollment proposal has been sent yet.</p>
@endforelse
</div></div><div class="col-lg-5"><div class="card border-0 shadow-sm p-4 h-100"><h2 class="h5">Back subjects and notifications</h2>
@forelse($backSubjects as $subject)
<p>{{ $subject->code }} · {{ $subject->name }}</p>
@empty
<p>No failed or incomplete prior-year requirements identified from the recorded history.</p>
@endforelse
<a href="/special-cases" class="mb-3">Request academic evaluation</a>
@foreach($notices as $notice)
<p class="small"><a href="{{ $notice->url }}">{{ $notice->title }}</a> · {{ $notice->created_at }}</p>
@endforeach
<a href="/notifications">All notifications</a></div></div></div>
<div class="card border-0 shadow-sm p-4 mt-4"><h2 class="h5">Class schedule for active periods</h2><p class="text-secondary">Weekly meetings from finalized enrollments. Proposed schedules appear in your enrollment proposal.</p><div class="table-responsive"><table class="table table-striped"><thead><tr><th>Period / subject</th><th>Day and time</th><th>Room / professor</th></tr></thead><tbody>
@forelse($schedule as $meeting)
<tr><td>{{ $meeting->period }}<br>{{ $meeting->code }} · {{ $meeting->name }}</td><td>{{ $meeting->day }} {{ substr($meeting->start_time,0,5) }}–{{ substr($meeting->end_time,0,5) }}</td><td>{{ $meeting->room }} · {{ $meeting->professor }}</td></tr>
@empty
<tr><td colspan="3">No finalized class schedule in an active period.</td></tr>
@endforelse
</tbody></table></div></div>
@endif
@endsection
