<h3 class="h6">HR / EMS work schedules</h3>
<p class="small text-secondary">Required hours come from the assigned schedule. Actual hours come from PMS punches. Official changes are made by HR in EMS.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>Effective dates</th><th>Workdays</th><th>Working hours</th><th>Lunch</th><th>Required hours/day</th><th>Grace</th><th>Source</th></tr></thead><tbody>
@forelse($selected?->schedules?->sortByDesc('effective_from')??[] as $shift)
@php($minutes=app(\App\Services\ScheduleService::class)->minutes($shift,$shift->effective_from,null)['scheduled_minutes'])
<tr><td>{{ $shift->effective_from }} to {{ $shift->effective_to??'until replaced' }}</td><td>{{ collect($shift->work_days??[])->map(fn($d)=>[1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'][$d]??$d)->join(', ') }}</td><td>{{ substr($shift->work_start,0,5) }} – {{ substr($shift->work_end,0,5) }}</td><td>{{ $shift->lunch_start?substr($shift->lunch_start,0,5):'None' }} {{ $shift->lunch_end?'– '.substr($shift->lunch_end,0,5):'' }}</td><td>{{ intdiv($minutes,60) }}h {{ $minutes%60 }}m</td><td>{{ $shift->grace_minutes }} min</td><td>{{ $shift->source_system==='EMS'?'EMS':'Local test / legacy' }}</td></tr>
@empty<tr><td colspan="7">No schedule synchronized. Contact HR/EMS; no default shift is assumed for live payroll.</td></tr>@endforelse
</tbody></table></div>
