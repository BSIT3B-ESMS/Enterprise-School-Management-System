

@extends('layouts.app')

@section('title','Visual scheduler')

@section('content')

<h1 class="h2">Visual class scheduler</h1><a class="btn btn-outline-primary mb-3" href="/scheduler/rooms">Open room-by-hour scheduler</a><p class="text-secondary">Drag an offering onto a day and hour, then choose its professor, room and end time. All assignments are validated by the server. You can also enter the schedule directly.</p><div class="row g-4"><div class="col-xl-3"><div class="card border-0 shadow-sm p-3"><label for="academic-year-filter" class="form-label">Academic year</label><select id="academic-year-filter" class="form-select mb-3"><option value="">All academic years</option>@foreach($periods->pluck('academic_year')->unique() as $year)<option>{{ $year }}</option>@endforeach</select><label for="semester-filter" class="form-label">Semester</label><select id="semester-filter" class="form-select mb-3"><option value="">All semesters</option><option value="1">Semester 1</option><option value="2">Semester 2</option><option value="3">Summer</option></select><label for="period-filter" class="form-label">Academic period</label><select id="period-filter" class="form-select mb-3">

@foreach($periods as $p)<option value="{{ $p->id }}" data-academic-year="{{ $p->academic_year }}" data-semester="{{ $p->semester }}">{{ $p->name }}</option>

@endforeach
</select><label class="form-label" for="program-filter">Program</label><select id="program-filter" class="form-select mb-3"><option value="">All programs</option>

@foreach($programs as $p)
<option value="{{ $p->id }}">{{ $p->code }}</option>

@endforeach
</select><label class="form-label" for="year-filter">Year level</label><select id="year-filter" class="form-select mb-3"><option value="">All years</option>

@for($year=1;$year<=8;$year++)
<option value="{{ $year }}">{{ $year }}</option>

@endfor
</select><label class="form-label" for="section-filter">Section</label><select id="section-filter" class="form-select mb-3"><option value="">All sections</option>

@foreach($sections as $s)
<option value="{{ $s->id }}" data-program="{{ $s->program_id }}" data-year="{{ $s->year_level }}" data-period="{{ $s->period_id }}">{{ $s->name }}</option>

@endforeach
</select><label class="form-label" for="floor-filter">Floor</label><select id="floor-filter" class="form-select mb-3"><option value="">All floors</option>

@foreach($rooms->pluck('floor')->unique() as $floor)
<option value="{{ $floor }}">{{ $floor }}</option>

@endforeach
</select><label class="form-label" for="room-filter">Room</label><select id="room-filter" class="form-select mb-3"><option value="">All rooms</option>

@foreach($rooms as $r)
<option value="{{ $r->id }}">{{ $r->code }}</option>

@endforeach
</select><label class="form-label" for="day-filter">Day</label><select id="day-filter" class="form-select mb-3"><option value="">All days</option>

@foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
<option>{{ $day }}</option>

@endforeach
</select><h2 class="h6">Available professors</h2><p class="small text-secondary">Matches the meeting editor’s subject, day and time.</p><div id="available-professors" class="mb-4" role="status"></div><h2 class="h6">Class offerings</h2>

@foreach($offerings as $o)<button draggable="true" type="button" class="btn btn-outline-secondary text-start mb-2 draggable-offering" data-program="{{ $o->program_id }}" data-year="{{ $o->year_level }}" data-section="{{ $o->section_id }}" data-id="{{ $o->id }}" data-period="{{ $o->period_id }}">{{ $o->code }} · {{ $o->section }}</button>

@endforeach
</div></div><div class="col-xl-9"><div class="card border-0 shadow-sm p-3"><div class="table-responsive"><table class="table table-bordered scheduler-grid"><thead><tr><th>Time</th>

@foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)<th class="day-column" data-day="{{ $day }}">{{ $day }}</th>

@endforeach
</tr></thead><tbody>

@for($hour=7;$hour<21;$hour++)<tr><th>{{ sprintf('%02d:00',$hour) }}</th>

@foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)<td tabindex="0" data-day="{{ $day }}" data-time="{{ sprintf('%02d:00',$hour) }}" class="schedule-slot day-column">

@foreach($schedules as $c)

@if($c->day===$day && (int)substr($c->start_time,0,2)<=$hour && ((int)substr($c->end_time,0,2)+((int)substr($c->end_time,3,2)>0?1:0))>$hour)<div class="schedule-event border rounded p-2 mb-1 small" draggable="true" data-record="{{ json_encode($c) }}" data-program="{{ $c->program_id }}" data-year="{{ $c->year_level }}" data-section="{{ $c->section_id }}" data-floor="{{ $c->floor }}" data-room="{{ $c->room_id }}" data-period="{{ $c->period_id }}"><strong>{{ $c->code }}</strong><br>{{ substr($c->start_time,0,5) }}–{{ substr($c->end_time,0,5) }}<br>{{ $c->room }} · {{ $c->professor }}<br><button type="button" class="btn btn-sm btn-link edit-meeting">Edit meeting</button></div>

@endif

@endforeach
</td>

@endforeach
</tr>

@endfor
</tbody></table></div></div></div></div>
<div class="card border-0 shadow-sm p-4 mt-4" id="schedule-form"><h2 class="h5" id="meeting-form-title">Create meeting</h2><p id="professor-details" class="text-secondary" role="status"></p><div id="schedule-result" role="status"></div><form id="meeting-editor" method="post" action="/academic/schedules">@csrf<div class="row g-3">

@foreach(['offering_id'=>'Class offering','professor_id'=>'Professor','room_id'=>'Room'] as $field=>$label)<div class="col-md-4"><label class="form-label" for="{{ $field }}">{{ $label }}</label><select class="form-select" name="{{ $field }}" id="{{ $field }}" required>

@foreach($field==='offering_id'?$offerings:($field==='professor_id'?$professors:$rooms) as $record)<option value="{{ $record->id }}" data-subject="{{ $record->subject_id??'' }}">{{ $record->code??$record->name }}{{ isset($record->section)?' · '.$record->section:'' }}</option>

@endforeach
</select></div>

@endforeach
<div class="col-md-4"><label class="form-label" for="day">Day</label><select class="form-select" id="day" name="day">

@foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)<option>{{ $day }}</option>

@endforeach
</select></div><div class="col-md-4"><label class="form-label" for="start_time">Start time</label><input class="form-control" id="start_time" name="start_time" type="time" required></div><div class="col-md-4"><label class="form-label" for="end_time">End time</label><input class="form-control" id="end_time" name="end_time" type="time" required></div></div><button type="submit" class="btn btn-primary mt-3">Preview and validate</button><button type="button" class="btn btn-outline-secondary mt-3 ms-2" id="reset-meeting">New meeting</button></form><section id="meeting-preview" class="mt-4 border-top pt-4" aria-labelledby="preview-title" hidden><h3 id="preview-title" class="h5">Review meeting</h3><p id="preview-summary"></p><p id="preview-validation" class="text-secondary"></p><button type="button" id="confirm-meeting" class="btn btn-primary">Confirm and save meeting</button></section></div>
<style>.scheduler-grid{min-width:950px}.schedule-slot{min-width:125px;height:65px}.schedule-slot.drag-over{background:#d9eddf}.schedule-event{background:#e7f2e9}</style>

@endsection

@push('scripts')<script src="{{ asset('js/scheduler.js') }}"></script>

<script>
const scheduledMeetings=@json($schedules);
const teachingQualifications=@json($qualifications), professorAvailability=@json($availability), professorRecords=@json($professors);
function filterProfessors(){const subject=Number($('#offering_id option:selected').data('subject'));const eligible=teachingQualifications.filter(q=>Number(q.subject_id)===subject).map(q=>Number(q.professor_id));$('#professor_id option').each(function(){$(this).prop('disabled',!eligible.includes(Number(this.value)));});if(!eligible.includes(Number($('#professor_id').val())))$('#professor_id').val(eligible[0]??'');showProfessor();}
function showProfessor(){const id=Number($('#professor_id').val()),p=professorRecords.find(p=>Number(p.id)===id);const windows=professorAvailability.filter(w=>Number(w.professor_id)===id).map(w=>w.day+' '+w.start_time.slice(0,5)+'–'+w.end_time.slice(0,5));$('#professor-details').text(p?p.name+' · '+p.employment_type.replaceAll('_',' ')+' · '+p.department+' · Availability: '+(windows.join(', ')||'None recorded'):'No qualified professor is currently available.');}
$('#offering_id').on('change',filterProfessors);$('#professor_id').on('change',showProfessor);filterProfessors();
$('#program-filter,#year-filter,#period-filter').on('change',function(){$('#section-filter option[value!=""]').each(function(){const match=(!$('#program-filter').val()||String($(this).data('program'))===$('#program-filter').val())&&(!$('#year-filter').val()||String($(this).data('year'))===$('#year-filter').val())&&String($(this).data('period'))===$('#period-filter').val();$(this).prop('disabled',!match);});if($('#section-filter option:selected').prop('disabled'))$('#section-filter').val('').trigger('change');}).trigger('change');
</script>
@endpush
