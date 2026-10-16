@props(['title'])

{{-- A note for 201 file information this system does not record yet. --}}
<div class="employees-empty profile-empty border rounded">
    <strong>{{ $title }}</strong>
    {{ $slot }}
</div>
