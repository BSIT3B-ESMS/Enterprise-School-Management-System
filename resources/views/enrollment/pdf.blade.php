<!doctype html><html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#111}h1{text-align:center;font-size:18px}.institution{text-align:center}table{width:100%;border-collapse:collapse;margin-top:20px}td,th{border:1px solid #bbb;padding:7px;text-align:left}thead{display:table-header-group}tr{page-break-inside:avoid}</style></head><body><p class="institution">{{ $snapshot->institution }}</p><h1>Certificate of Registration</h1><p><strong>{{ $snapshot->student->name }}</strong><br>{{ $snapshot->student->program??'' }}<br>Student number: {{ $snapshot->student->student_number??$snapshot->student->admission_id }}<br>{{ $snapshot->period }} · Year {{ $snapshot->student->year_level }}<br>Finalized: {{ $snapshot->finalized_at }}</p><table><thead><tr><th>Subject</th><th>Units</th><th>Section</th><th>Schedule</th></tr></thead><tbody>

@foreach($snapshot->items as $i)
<tr><td>{{ $i->code }}<br>{{ $i->name }}</td><td>{{ $i->units }}</td><td>{{ $i->section }}</td><td>

@foreach($i->meetings as $m)
<div>{{ $m->day }} {{ substr($m->start_time,0,5) }}–{{ substr($m->end_time,0,5) }}<br>{{ $m->room }} · {{ $m->professor }}</div>

@endforeach
</td></tr>

@endforeach
</tbody></table><p>Total units: {{ collect($snapshot->items)->sum('units') }}<br>Status: {{ ($historical??false)?'Historical finalized copy':'Finalized' }} · Registration {{ $e->id }} · Version {{ $e->version }}</p></body></html>