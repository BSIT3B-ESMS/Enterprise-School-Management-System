@extends('layouts.app')
@section('title','My grades')
@section('content')
<h1>My grades</h1><p class="text-secondary">Your curriculum results, prerequisites and upcoming subjects. Grades and credited subjects are view only.</p>
<div class="metrics-grid mb-4">@foreach($summary as $label=>$total)<div class="metric"><span>{{ $label }}</span><strong>{{ $total }}</strong></div>@endforeach</div>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Year / semester</th><th>Subject</th><th>Units</th><th>Prerequisites</th><th>Grade</th><th>Result</th><th>Submitted by</th><th>Period</th></tr></thead><tbody>
@forelse($requirements as $subject)
@php($record=$subject->result)
<tr><td>{{ $subject->year_level }} / {{ $subject->semester }}</td><td>{{ $subject->code }} · {{ $subject->name }}</td><td>{{ $subject->units }}</td><td>@forelse(($prerequisites[$subject->id]??collect())->groupBy('alternative_group') as $group=>$items)<div>{{ $items->pluck('code')->implode((int)$group===0?', ':' or ') }}@if((int)$group>0) <span class="small text-secondary">(one required)</span>@endif</div>@empty None @endforelse</td><td>{{ $record?->grade??'—' }}</td><td>{{ $record?->status??($subject->upcoming?'Upcoming':'No grade yet') }}</td><td>{{ $record?->professor??($record?'Academic records':'—') }}</td><td>{{ $record?->period??'—' }}</td></tr>
@empty<tr><td colspan="8">No curriculum subjects are assigned. Contact the Registrar to review your curriculum.</td></tr>@endforelse
</tbody></table></div><p class="small text-secondary">Upcoming subjects belong to a later curriculum semester or year. Official grades are posted by assigned professors; approved equivalencies appear as credited.</p><a href="/records/{{ $student->id }}">View all attempts, academic history and prerequisite decisions</a>
@endsection
