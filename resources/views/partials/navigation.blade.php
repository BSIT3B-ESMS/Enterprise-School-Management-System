<nav class="d-flex flex-column gap-2" aria-label="Portal navigation"><a href="/dashboard" class="btn btn-sm btn-outline-secondary">Dashboard</a><a href="/notifications" class="btn btn-sm btn-outline-secondary">Notifications</a><a href="/activity" class="btn btn-sm btn-outline-secondary">Activity</a><a href="/records" class="btn btn-sm btn-outline-secondary">Academic records</a><a href="/enrollments" class="btn btn-sm btn-outline-secondary">Enrollments</a><a href="/special-cases" class="btn btn-sm btn-outline-secondary">Academic evaluations</a>

@if(auth()->user()->role==='registrar')<a href="/reports" class="btn btn-sm btn-outline-secondary">Enrollment reports</a><a href="/staff" class="btn btn-sm btn-outline-secondary">Staff accounts</a><a href="/professors" class="btn btn-sm btn-outline-secondary">Professors</a><a href="/audit" class="btn btn-sm btn-outline-secondary">Audit history</a><a href="/imports" class="btn btn-sm btn-outline-secondary">System imports</a><a href="/scheduler" class="btn btn-sm btn-outline-secondary">Scheduler</a><div class="dropdown"><button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Academic management</button><ul class="dropdown-menu">

@foreach(config('academic') as $resource=>$fields)<li><a class="dropdown-item" href="/academic/{{ $resource }}">{{ ucwords(str_replace('_',' ',$resource)) }}</a></li>

@endforeach</ul></div>

@endif</nav>