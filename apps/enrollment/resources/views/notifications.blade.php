

@extends('layouts.app')

@section('title','Notifications')

@section('content')
<h1 class="h2 mb-4">Notifications</h1><div class="list-group">

@forelse($notifications as $n)
<div class="list-group-item p-3"><strong>{{ $n->title }}</strong> <span class="badge text-bg-secondary">{{ $n->read_at?'Read':'New' }}</span><p class="text-secondary small mb-2">{{ $n->created_at }}</p><form method="post" action="/notifications/{{ $n->id }}/read">@csrf<button class="btn btn-sm btn-outline-primary">Open details</button></form></div>

@empty
<p>No notifications yet.</p>

@endforelse
</div>{{ $notifications->links('pagination::bootstrap-5') }}

@endsection