

@extends('layouts.app')

@section('title','Certificate of Registration')

@section('content')
<div class="d-print-none mb-4"><a href="/enrollments/{{ $e->id }}" class="btn btn-outline-secondary">Back to enrollment</a><button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button> <a class="btn btn-outline-primary" href="/enrollments/{{ $e->id }}/pdf/{{ $e->version }}">Download PDF</a></div><article class="bg-white p-4 p-lg-5"><p class="text-center mb-1">{{ $snapshot->institution }}</p><h1 class="h3 text-center mb-4">Certificate of Registration</h1><p><strong>{{ $snapshot->student->name }}</strong><br>{{ $snapshot->student->program??'' }}<br>Student number: {{ $snapshot->student->student_number ?? $snapshot->student->admission_id }}<br>{{ $snapshot->period }} · Year {{ $snapshot->student->year_level }}<br>Finalized: {{ $snapshot->finalized_at }}</p><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Code and subject</th><th>Units</th><th>Section</th><th>Schedule and professor</th></tr></thead><tbody>

@foreach($snapshot->items as $i)<tr><td>{{ $i->code }}<br>{{ $i->name }}</td><td>{{ $i->units }}</td><td>{{ $i->section }}</td><td>

@foreach($i->meetings as $m)<div>{{ $m->day }} {{ substr($m->start_time,0,5) }}–{{ substr($m->end_time,0,5) }} · {{ $m->room }} · {{ $m->professor }}</div>

@endforeach</td></tr>

@endforeach</tbody></table></div><p>Total units: {{ collect($snapshot->items)->sum('units') }}<br>Status: {{ ($historical??false)?'Historical finalized copy':'Finalized' }} · Registration {{ $e->id }} · Version {{ $e->version }}</p></article>
<style>@media print{.topbar{display:none!important}body{background:white}main{padding:0!important;max-width:none!important}article{padding:0!important}table{font-size:11px}@page{size:A4;margin:14mm}}</style>

@endsection