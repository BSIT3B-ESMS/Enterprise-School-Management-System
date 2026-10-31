

@extends('layouts.app')

@section('title','My account')

@section('content')
<section class="mb-4"><h1 class="h3">My account</h1><p class="text-secondary">These details are view only. Identity and academic information are maintained by Admission and authorized staff.</p><div class="table-responsive"><table class="table account-details"><tbody>@foreach($details as $label=>$value)<tr><th scope="row">{{ $label }}</th><td>{{ $value }}</td></tr>@endforeach</tbody></table></div></section>
<div class="card border-0 shadow-sm p-4 mx-auto" style="max-width:560px"><h2 class="h3">Change your password</h2><p class="text-secondary">Use at least 12 characters, with uppercase and lowercase letters and a number.</p><form method="post" action="{{ route('password.update') }}">@csrf

@foreach(['current_password'=>'Current password','password'=>'New password','password_confirmation'=>'Confirm new password'] as $field=>$label)<label class="form-label mt-3" for="{{ $field }}">{{ $label }}</label><input class="form-control" type="password" id="{{ $field }}" name="{{ $field }}" required autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}">

@endforeach
<button class="btn btn-primary mt-4">Save password</button></form></div>

@endsection
