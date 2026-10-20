@extends('layouts.admin')

@section('title', 'Accounts · '.config('app.name'))

{{--
    HR Head account administration: issue a login to an employee (with a one-time temporary password), reset a password,
    deactivate or reactivate an account, and assign roles. Accounts are deactivated, never deleted, so history stays.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="accounts">
        <div>
            <h1 class="page-title">Accounts</h1>
            <p class="text-body-secondary mt-1 mb-0">Logins for HR and staff. Applicants register themselves on the Careers website.</p>
        </div>

        @if (session('issued_password'))
            @php($issued = session('issued_password'))
            <div class="alert alert-warning mb-0" role="alert">
                <strong>One-time temporary password for {{ $issued['name'] }}</strong>
                <div>Email: <span class="user-select-all">{{ $issued['email'] }}</span></div>
                <div>Password: <code class="user-select-all">{{ $issued['password'] }}</code></div>
                <div class="small">
                    Shown only once. Hand it over in person or through an approved channel. It expires {{ $issued['expires'] }},
                    and the person must choose their own password at first sign-in.
                </div>
            </div>
        @endif

        <section class="card employee-form-card" aria-labelledby="create-account-title">
            <h2 class="fs-5" id="create-account-title">Create an account</h2>

            @if ($employeesWithoutAccount->isEmpty())
                <p class="text-body-secondary mb-0">Every employee already has an account.</p>
            @else
                <form method="post" action="{{ route('accounts.store') }}" class="row g-3 align-items-end">
                    @csrf

                    <div class="col-md-6">
                        <label for="account-employee" class="form-label">Employee</label>
                        <select class="form-select" id="account-employee" name="employee_id" required>
                            <option value="">Select employee</option>
                            @foreach ($employeesWithoutAccount as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->last_name }}, {{ $employee->first_name }} ({{ $employee->employee_no }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="account-role" class="form-label">Role</label>
                        <select class="form-select" id="account-role" name="role" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected($role === \App\Enums\Role::Employee)>{{ str($role->value)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Create Account</button>
                    </div>
                </form>
            @endif
        </section>

        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col">Two-factor</th>
                            <th scope="col">Last sign-in</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accounts as $account)
                            <tr>
                                <td>{{ $account->name }}</td>
                                <td>{{ $account->email }}</td>
                                <td>
                                    @if (auth()->user()->isNot($account))
                                        <form method="post" action="{{ route('accounts.role', $account) }}" class="d-flex gap-2">
                                            @csrf
                                            @method('PUT')
                                            <label class="visually-hidden" for="role-{{ $account->id }}">Role of {{ $account->name }}</label>
                                            <select class="form-select form-select-sm" id="role-{{ $account->id }}" name="role">
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->value }}" @selected($account->role === $role)>{{ str($role->value)->replace('_', ' ')->title() }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
                                        </form>
                                    @else
                                        {{ str($account->role->value)->replace('_', ' ')->title() }} (you)
                                    @endif
                                </td>
                                <td>
                                    <span class="ems-chip">{{ $account->is_active ? 'Active' : 'Deactivated' }}</span>
                                    @if ($account->must_change_password)
                                        <span class="ems-chip">Temporary password</span>
                                    @endif
                                </td>
                                <td>{{ $account->two_factor_confirmed_at ? 'On' : 'Off' }}</td>
                                <td class="text-nowrap">{{ $account->last_login_at?->format('M j, Y g:i A') ?? 'Never' }}</td>
                                <td class="text-nowrap">
                                    @if (auth()->user()->isNot($account))
                                        <form method="post" action="{{ route('accounts.reset-password', $account) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Reset Password</button>
                                        </form>

                                        @if ($account->is_active)
                                            <form method="post" action="{{ route('accounts.deactivate', $account) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Deactivate</button>
                                            </form>
                                        @else
                                            <form method="post" action="{{ route('accounts.reactivate', $account) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Reactivate</button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
