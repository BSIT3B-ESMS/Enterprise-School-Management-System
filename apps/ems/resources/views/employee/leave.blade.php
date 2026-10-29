@extends('layouts.employee')

@section('title', 'My Leave · '.config('app.name'))

@section('content')
    @include('leave._mine', ['storeRoute' => 'employee.leave.store', 'cancelRoute' => 'employee.leave.cancel'])
@endsection
