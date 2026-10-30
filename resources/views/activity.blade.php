

@extends('layouts.app')

@section('title','Enrollment activity')

@section('content')

<h1 class="h2 mb-4">Enrollment activity</h1><div class="list-group">

@forelse($events as $e)
<a class="list-group-item list-group-item-action p-3" href="/enrollments/{{ $e->enrollment_id }}"><strong>{{ $e->name }}</strong> · {{ ucwords(str_replace('_',' ',$e->action)) }}<br><span class="small text-secondary">Version {{ $e->version }} · {{ $e->created_at }}</span></a>

@empty
<p>No enrollment activity yet.</p>

@endforelse
</div>{{ $events->links('pagination::bootstrap-5') }}

@endsection