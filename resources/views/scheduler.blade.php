

@extends('layouts.app')

@section('title','Visual scheduler')

@section('content')

<h1 class="h2">Visual class scheduler</h1><p class="text-secondary">Drag an offering onto a day and hour, then choose its professor, room and end time. All assignments are validated by the server. You can also enter the schedule directly.</p><div class="row g-4"><div class="col-xl-3"><div class="card border-0 shadow-sm p-3"><label for="period-filter" class="form-label">Academic period</label><select id="period-filter" class="form-select mb-3">

@foreach($periods as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>

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
<option value="{{ $s->id }}">{{ $s->name }}</option>

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
</select><h2 class="h6">Class offerings</h2>

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
<div class="card border-0 shadow-sm p-4 mt-4" id="schedule-form"><h2 class="h5" id="meeting-form-title">Create meeting</h2><div id="schedule-result" role="status"></div><form id="meeting-editor" method="post" action="/academic/schedules">@csrf<div class="row g-3">

@foreach(['offering_id'=>'Class offering','professor_id'=>'Professor','room_id'=>'Room'] as $field=>$label)<div class="col-md-4"><label class="form-label" for="{{ $field }}">{{ $label }}</label><select class="form-select" name="{{ $field }}" id="{{ $field }}" required>

@foreach($field==='offering_id'?$offerings:($field==='professor_id'?$professors:$rooms) as $record)<option value="{{ $record->id }}">{{ $record->code??$record->name }}{{ isset($record->section)?' · '.$record->section:'' }}</option>

@endforeach
</select></div>

@endforeach
<div class="col-md-4"><label class="form-label" for="day">Day</label><select class="form-select" id="day" name="day">

@foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)<option>{{ $day }}</option>

@endforeach
</select></div><div class="col-md-4"><label class="form-label" for="start_time">Start time</label><input class="form-control" id="start_time" name="start_time" type="time" required></div><div class="col-md-4"><label class="form-label" for="end_time">End time</label><input class="form-control" id="end_time" name="end_time" type="time" required></div></div><button class="btn btn-primary mt-3">Validate and save meeting</button><button type="button" class="btn btn-outline-secondary mt-3 ms-2" id="reset-meeting">New meeting</button></form></div>
<style>.scheduler-grid{min-width:950px}.schedule-slot{min-width:125px;height:65px}.schedule-slot.drag-over{background:#d9eddf}.schedule-event{background:#e7f2e9}</style>

@endsection

@push('scripts')<script src="{{ asset('js/scheduler.js') }}"></script>

@endpush