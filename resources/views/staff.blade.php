

@extends('layouts.app')

@section('title','Staff accounts')

@section('content')

<h1 class="h2 mb-4">Staff accounts</h1><div class="row g-4"><div class="col-lg-7"><div class="card border-0 shadow-sm p-4"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead><tbody>

@foreach($users as $user)
<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ str_replace('_',' ',$user->role) }}</td><td>{{ $user->active?'Active':'Inactive' }}</td><td><form method="post" action="/staff/{{ $user->id }}/toggle">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $user->active?'Deactivate':'Activate' }}</button></form></td></tr>

@endforeach
</tbody></table></div>{{ $users->links('pagination::bootstrap-5') }}</div></div><div class="col-lg-5"><div class="card border-0 shadow-sm p-4"><h2 class="h5">Create authorized staff account</h2><form method="post" action="/staff">@csrf

@foreach(['name'=>'Full name','email'=>'Email','password'=>'Temporary password','password_confirmation'=>'Confirm temporary password'] as $field=>$label)
<label class="form-label mt-3" for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" type="{{ str_starts_with($field,'password')?'password':($field==='email'?'email':'text') }}" class="form-control" required>

@endforeach
<label class="form-label mt-3" for="role">Role</label><select id="role" name="role" class="form-select"><option value="department_head">Department Head</option><option value="registrar">Registrar</option></select><p class="small text-secondary mt-3">Temporary passwords need 12 characters, upper and lowercase letters, and a number. Assign Department Heads to their programs through Academic management.</p><button class="btn btn-primary mt-2">Create account</button></form></div></div></div>

@endsection