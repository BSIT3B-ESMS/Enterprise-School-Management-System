@php
$groups = ['Workspace' => [
    ['dashboard', 'Overview', ['dashboard', 'registrar', 'department-head', 'student']],
    ['enrollments', 'Enrollments', ['enrollments*']],
    ['records', 'Academic records', ['records*']],
    ['special-cases', 'Academic evaluations', ['special-cases*']],
    ['notifications', 'Notifications', ['notifications*']],
    ['activity', 'Activity', ['activity*']],
]];
if (auth()->user()->role === 'registrar') {
    $groups['Administration'] = [
        ['term-calendar', 'Semester & pipeline', ['term-calendar*']],
        ['students', 'Students', ['students*']],
        ['admission-years', 'Admission year', ['admission-years*']],
        ['academic-years', 'Academic year', ['academic-years*']],
        ['special-enrollment', 'Special enrollment', ['special-enrollment*']],
        ['colleges', 'Colleges & programs', ['colleges*']],
        ['sections', 'Sections', ['sections*']],
        ['scheduler', 'Scheduler', ['scheduler*']],
        ['professors', 'Professors', ['professors*']],
        ['academic/subjects', 'Subjects & prerequisites', ['academic/subjects']],
        ['academic/rooms', 'Rooms', ['academic/rooms']],
        ['reports', 'Enrollment reports', ['reports*']],
        ['staff', 'Staff accounts', ['staff*']],
        ['imports', 'System imports', ['imports*']],
        ['audit', 'Audit history', ['audit*']],
        ['settings', 'System settings', ['settings*']],
    ];
}
if(auth()->user()->role==='student') array_splice($groups['Workspace'],1,0,[['grades','Grades',['grades*']],['waiting-list','Subject requests',['waiting-list*']]]);
if(auth()->user()->role==='department_head') array_unshift($groups['Workspace'],['special-students','Special students',['special-students*']]);
if(auth()->user()->role==='professor') $groups=['Teaching'=>[['professor','Submit grades',['professor*']],['notifications','Notifications',['notifications*']],['activity','Activity',['activity*']]]];
@endphp
<nav class="portal-navigation" aria-label="Portal navigation">
@foreach($groups as $heading => $links)
<div class="nav-group"><h2 class="nav-heading">{{ $heading }}</h2>
@foreach($links as [$path, $label, $patterns])
<a href="/{{ $path }}" class="nav-item {{ request()->is(...$patterns) ? 'is-active' : '' }}" @if(request()->is(...$patterns)) aria-current="page" @endif>{{ $label }}</a>
@endforeach
</div>
@endforeach
@if(auth()->user()->role === 'registrar')
<details class="academic-menu" @if(request()->is('academic/*')) open @endif>
<summary>Academic management</summary>
@foreach(config('academic') as $resource => $fields)
<a href="/academic/{{ $resource }}" class="nav-item {{ request()->is('academic/'.$resource) ? 'is-active' : '' }}" @if(request()->is('academic/'.$resource)) aria-current="page" @endif>{{ ucwords(str_replace('_',' ',$resource)) }}</a>
@endforeach
</details>
@endif
<a href="{{ route('password.edit') }}" class="nav-item {{ request()->is('account/*') ? 'is-active' : '' }}" @if(request()->is('account/*')) aria-current="page" @endif>Account settings</a>
</nav>
