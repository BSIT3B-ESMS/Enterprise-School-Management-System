@extends('layouts.public')

@section('title', $position->title.' · '.config('app.name'))

@section('content')
    @include('careers._details')
@endsection
