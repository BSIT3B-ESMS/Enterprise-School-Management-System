@props(['document', 'label'])

{{-- A 201 file document list on the employee profile, filled by resources/js/employee-profile.js. --}}
<div {{ $attributes }}>
    <h3 class="employee-form-section">{{ $label }}</h3>
    <ul class="list-group document-list" data-profile-documents="{{ $document }}"></ul>
    <p class="small text-body-secondary mb-0" data-profile-documents-empty="{{ $document }}">No files attached.</p>
</div>
