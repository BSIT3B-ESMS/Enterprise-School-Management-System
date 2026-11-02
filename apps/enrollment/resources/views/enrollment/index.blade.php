

@extends('layouts.app')

@section('title','Enrollments')

@section('content')

<h1 class="h2 mb-4">{{ $isReport?'Enrollment report':'Enrollments' }}</h1><form method="get" class="row g-2 mb-4"><div class="col-12"><label for="enrollment-search" class="form-label">Student name</label><input id="enrollment-search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Find an enrollment by student name"></div><div class="col-md-3"><label class="form-label" for="report-period">Period</label><select name="period_id" id="report-period" class="form-select"><option value="">All periods</option>

@foreach($periods as $p)
<option value="{{ $p->id }}" @selected(request('period_id')==$p->id)>{{ $p->name }}</option>

@endforeach
</select></div><div class="col-md-3"><label class="form-label" for="report-program">Program</label><select name="program_id" id="report-program" class="form-select"><option value="">All programs</option>

@foreach($programs as $p)
<option value="{{ $p->id }}" @selected(request('program_id')==$p->id)>{{ $p->code }}</option>

@endforeach
</select></div><div class="col-md-3"><label class="form-label" for="report-status">Status</label><select name="status" id="report-status" class="form-select"><option value="">All statuses</option>

@foreach(['proposed','special_proposed','special_ready','special_accepted','accepted','revision_requested','finalized','term_closed'] as $status)
<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>

@endforeach
</select></div><div class="col-md-3 d-flex gap-2 align-items-end"><button class="btn btn-primary">Filter</button>

@if(auth()->user()->role==='registrar')
<button class="btn btn-outline-secondary" name="download" value="csv">Export CSV</button>

@endif
</div></form><div class="card border-0 shadow-sm p-4 mb-4"><div class="table-responsive"><table class="table table-hover"><thead><tr>@foreach(['name'=>'Student','period'=>'Period','classification'=>'Classification','status'=>'Status','version'=>'Version'] as $column=>$label)@include('partials.sort-heading',['column'=>$column,'label'=>$label,'defaultDirection'=>'desc'])@endforeach<th></th></tr></thead><tbody>

@forelse($rows as $row)<tr><td>{{ $row->name }}</td><td>{{ $row->period }}</td><td>{{ $row->classification }}</td><td><span class="badge text-bg-{{ $row->status==='finalized'?'success':(str_contains($row->status,'revision')?'warning':'secondary') }}">{{ ucwords(str_replace('_',' ',$row->status)) }}</span></td><td>{{ $row->version }}</td><td><a href="/enrollments/{{ $row->id }}">Review</a></td></tr>

@empty<tr><td colspan="6">No enrollment proposals yet.</td></tr>

@endforelse</tbody></table></div>{{ $rows->links('pagination::bootstrap-5') }}</div>

@if(auth()->user()->role!=='student' && !$isReport)<div class="card border-0 shadow-sm p-4"><h2 class="h4">Prepare {{ auth()->user()->role==='department_head'?'special academic':'final enrollment' }} proposal</h2><p class="text-secondary">Pending proposals do not reserve seats. Capacity and eligibility are checked again at finalization.</p><form method="post" action="/enrollments">@csrf<div class="row g-3"><div class="col-md-6"><label class="form-label" for="student_id">Student</label><select class="form-select" id="student_id" name="student_id" required>

@foreach($students as $s)<option value="{{ $s->id }}" @selected(old('student_id',$selectedStudent)==$s->id)>{{ $s->name }} · {{ $s->classification }}</option>

@endforeach</select></div><div class="col-md-6"><label class="form-label" for="period_id">Academic period</label><select class="form-select" id="period_id" name="period_id" required>

@foreach($periods as $p)<option value="{{ $p->id }}" @selected(old('period_id',$selectedPeriod)==$p->id)>{{ $p->name }}</option>

@endforeach</select></div></div><label for="offering-filter" class="form-label mt-4">Find classes</label><input id="offering-filter" class="form-control mb-3" placeholder="Search subject, section or period"><div id="offering-list">

@forelse($offerings as $o)<label class="d-block border rounded p-3 mb-2 offering-option" data-period="{{ $o->period_id }}" data-units="{{ $o->units }}"><input type="checkbox" name="offering_ids[]" value="{{ $o->id }}" @checked(in_array($o->id,old('offering_ids',$preselected))) class="form-check-input me-2">{{ $o->code }} · {{ $o->name }} · {{ $o->section }} · {{ $o->period }} <span class="text-secondary">({{ $o->enrolled_count }}/{{ $o->capacity }})</span></label>

@empty<p>Create class offerings and schedules first.</p>

@endforelse</div><p id="selected-summary" class="mt-3" role="status">No classes selected.</p><button class="btn btn-primary mt-3">Validate and send proposal</button></form></div>

@endif

@endsection

@push('scripts')
<script>
function filterClasses(){const query=$('#offering-filter').val().toLowerCase(),period=$('#period_id').val();$('.offering-option').each(function(){const same=String($(this).data('period'))===String(period);$(this).toggle(same&&$(this).text().toLowerCase().includes(query));if(!same)$(this).find('input').prop('checked',false);});summarizeClasses();}
function summarizeClasses(){let units=0,count=0;$('.offering-option input:checked').each(function(){count++;units+=Number($(this).closest('label').data('units'));});$('#selected-summary').text(count+' classes selected · '+units+' total units');}
$('#offering-filter').on('input',filterClasses);$('#period_id').on('change',filterClasses);$('#offering-list input').on('change',summarizeClasses);filterClasses();
</script>
@endpush
