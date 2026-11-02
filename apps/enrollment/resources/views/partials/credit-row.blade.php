<fieldset class="border rounded p-3 mb-3"><legend class="float-none w-auto px-2 fs-6">Subject equivalency</legend><div class="row g-3">
@foreach(['source_institution'=>'Original institution','source_subject'=>'Original subject code and title','source_grade'=>'Original grade (optional)','source_units'=>'Original units','evidence_reference'=>'Transcript / evidence reference'] as $field=>$label)
<div class="col-md-6"><label for="credit-{{ $index }}-{{ $field }}" class="form-label">{{ $label }}</label><input class="form-control" id="credit-{{ $index }}-{{ $field }}" name="credits[{{ $index }}][{{ $field }}]" value="{{ $credit[$field]??'' }}" type="{{ $field==='source_units'?'number':'text' }}" @if($field==='source_units') min="0.01" max="100" step="0.01" @endif @required($field!=='source_grade')></div>
@endforeach
<div class="col-md-6"><label for="credit-{{ $index }}-subject" class="form-label">Equivalent curriculum subject</label><select id="credit-{{ $index }}-subject" name="credits[{{ $index }}][subject_id]" class="form-select" required><option value="">Select subject</option>
@foreach($subjects as $subject)
<option value="{{ $subject->id }}" @selected(($credit['subject_id']??'')==$subject->id)>{{ $subject->code }} · {{ $subject->name }} ({{ $subject->units }} units)</option>
@endforeach
</select></div>
<div class="col-md-6"><label for="credit-{{ $index }}-decision" class="form-label">Subject decision</label><select id="credit-{{ $index }}-decision" name="credits[{{ $index }}][decision]" class="form-select"><option value="approved">Approve credit</option><option value="rejected" @selected(($credit['decision']??'')==='rejected')>Reject equivalency</option></select></div>
<div class="col-12"><label for="credit-{{ $index }}-reason" class="form-label">Academic basis and reason</label><textarea id="credit-{{ $index }}-reason" name="credits[{{ $index }}][reason]" class="form-control" required maxlength="2000">{{ $credit['reason']??'' }}</textarea></div>
</div><button type="button" class="btn btn-sm btn-outline-danger remove-credit mt-3">Remove unsaved row</button></fieldset>
