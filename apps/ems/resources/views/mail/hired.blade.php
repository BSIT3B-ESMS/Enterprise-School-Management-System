<x-mail::message>
# Welcome to PLP, {{ $name }}

You are hired as **{{ $position }}**, starting {{ $startDate->format('F j, Y') }}.

Sign in to the Employee Management System with:

- **Email:** {{ $loginEmail }}
- **First password:** your employee ID, **{{ $employeeNo }}**

You will be asked to choose your own password and to fill in your emergency contact and government and tax details the first time you sign in.

Your applicant account stays available.

Thank you,<br>
Pamantasan ng Lungsod ng Pasig, Human Resources
</x-mail::message>
