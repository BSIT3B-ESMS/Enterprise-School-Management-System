@extends('layouts.admin')

@section('title', 'My Leave · '.config('app.name'))

@section('content')
    @include('leave._mine', ['storeRoute' => 'my-leave.store', 'cancelRoute' => 'my-leave.cancel'])
@endsection
