@extends('layouts.app')
@section('title','System settings')
@section('content')
<h1>System settings</h1><p class="text-secondary">Institution identity and enrollment defaults. Changes are recorded in audit history.</p>
<div class="row g-4"><div class="col-lg-7"><section class="card p-4 border-0 shadow-sm"><h2 class="h5">Academic policy</h2>
<form method="post" action="/settings">@csrf
<label for="institution_name" class="form-label">Institution name</label><input class="form-control mb-3" id="institution_name" name="institution_name" value="{{ old('institution_name',$values['institution_name']) }}" required maxlength="200">
<label for="grade_order" class="form-label">Minimum-grade prerequisite comparison</label><select id="grade_order" name="grade_order" class="form-select" aria-describedby="grade-help">
@foreach(['unconfigured'=>'Not configured — minimum-grade rules remain blocked','lower'=>'Lower numeric grades are better (e.g. 1.00 to 5.00)','higher'=>'Higher numeric grades are better (e.g. percentages)'] as $key=>$label)<option value="{{ $key }}" @selected(old('grade_order',$values['grade_order'])===$key)>{{ $label }}</option>@endforeach
</select><p id="grade-help" class="small text-secondary mt-2">Choose the scale used by the stored academic records. The supplied reference app uses lower-is-better transmuted grades; this does not convert existing grades.</p>
<label for="default_section_capacity" class="form-label">Default capacity for new sections</label><input type="number" min="1" max="500" id="default_section_capacity" name="default_section_capacity" class="form-control mb-4" value="{{ old('default_section_capacity',$values['default_section_capacity']) }}" required>
<button class="btn btn-primary">Save settings</button></form></section></div>
<div class="col-lg-5"><section class="card p-4 border-0 shadow-sm"><h2 class="h5">Service configuration</h2><dl><dt>Database</dt><dd>{{ config('database.default') }}</dd><dt>Account email delivery</dt><dd>{{ config('mail.default') === 'smtp' ? 'SMTP configured — delivery must be verified' : 'Local capture — invitations and reset links are not delivered to inboxes' }}</dd><dt>Institution seal</dt><dd><img src="{{ asset('branding/plp-seal.png') }}" alt="PLP seal" width="64" height="64"></dd></dl><p class="small text-secondary mb-0">Mail credentials, HTTPS and integration access belong in the private server environment. See the deployment guide for setup and verification.</p></section></div></div>
@endsection
